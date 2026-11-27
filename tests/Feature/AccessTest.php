<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Dealer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    private function dealerUser(array $user = [], array $dealer = []): User
    {
        $u = User::factory()->create(array_merge(['role' => 'dealer'], $user));
        Dealer::query()->create(array_merge(['user_id' => $u->id, 'business_name' => 'Negozio '.$u->id], $dealer));

        return $u;
    }

    private function agentUser(array $user = []): User
    {
        $u = User::factory()->create(array_merge(['role' => 'agent'], $user));
        Agent::query()->create(['user_id' => $u->id]);

        return $u;
    }

    public function test_reserved_areas_require_login(): void
    {
        foreach (['/dealer', '/agente', '/manager', '/manager/promo', '/manager/rete'] as $url) {
            $this->get($url)->assertRedirect('/accedi');
        }
    }

    public function test_login_with_username_or_email(): void
    {
        $user = $this->dealerUser(['username' => 'negozio.test', 'email' => 'n@example.com', 'password' => 'Segreta12345']);

        $this->post('/accedi', ['login' => 'negozio.test', 'password' => 'Segreta12345'])->assertRedirect('/dealer');
        $this->post('/esci');
        $this->post('/accedi', ['login' => 'n@example.com', 'password' => 'Segreta12345'])->assertRedirect('/dealer');
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_and_suspended_users_are_refused(): void
    {
        $this->dealerUser(['username' => 'attivo', 'password' => 'Segreta12345']);
        $this->dealerUser(['username' => 'sospeso', 'password' => 'Segreta12345', 'active' => false]);

        $this->post('/accedi', ['login' => 'attivo', 'password' => 'sbagliata'])->assertSessionHasErrors('login');
        $this->post('/accedi', ['login' => 'sospeso', 'password' => 'Segreta12345'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_temporary_password_must_be_changed_at_first_login(): void
    {
        $user = $this->dealerUser(['username' => 'nuovo', 'password' => 'Provvisoria99', 'must_change_password' => true]);

        $this->post('/accedi', ['login' => 'nuovo', 'password' => 'Provvisoria99'])->assertRedirect('/password/cambia');
        // Finché non la cambia, l'area riservata rimanda al cambio password.
        $this->get('/dealer')->assertRedirect('/password/cambia');

        // Non può riusare la stessa, né sceglierne una debole.
        $this->post('/password/cambia', ['current_password' => 'Provvisoria99', 'password' => 'Provvisoria99', 'password_confirmation' => 'Provvisoria99'])
            ->assertSessionHasErrors('password');
        $this->post('/password/cambia', ['current_password' => 'Provvisoria99', 'password' => 'corta1', 'password_confirmation' => 'corta1'])
            ->assertSessionHasErrors('password');

        $this->post('/password/cambia', ['current_password' => 'Provvisoria99', 'password' => 'NuovaPassword2026', 'password_confirmation' => 'NuovaPassword2026'])
            ->assertRedirect('/dealer');
        $this->assertFalse($user->fresh()->must_change_password);
        $this->get('/dealer')->assertOk();

        // La provvisoria non vale più.
        $this->post('/esci');
        $this->post('/accedi', ['login' => 'nuovo', 'password' => 'Provvisoria99'])->assertSessionHasErrors('login');
    }

    public function test_each_role_only_enters_its_own_area(): void
    {
        $dealer = $this->dealerUser();
        $agent = $this->agentUser();
        $manager = User::factory()->create(['role' => 'admin']);

        $this->actingAs($dealer)->get('/dealer')->assertOk();
        $this->actingAs($dealer)->get('/agente')->assertForbidden();
        $this->actingAs($dealer)->get('/manager')->assertForbidden();

        $this->actingAs($agent)->get('/agente')->assertOk();
        $this->actingAs($agent)->get('/dealer')->assertForbidden();
        $this->actingAs($agent)->get('/manager/promo')->assertForbidden();

        $this->actingAs($manager)->get('/manager')->assertOk();
        $this->actingAs($manager)->get('/manager/promo')->assertOk();
        $this->actingAs($manager)->get('/manager/rete')->assertOk();
        $this->actingAs($manager)->get('/dealer')->assertForbidden();
    }

    public function test_all_five_tabs_open_for_dealer_and_agent(): void
    {
        $dealer = $this->dealerUser();
        $agent = $this->agentUser();
        foreach (['optima', 'sharers', 'rapicom', 'altri', 'promo'] as $tab) {
            $this->actingAs($dealer)->get('/dealer?scheda='.$tab)->assertOk();
            $this->actingAs($agent)->get('/agente?scheda='.$tab)->assertOk();
        }
    }

    public function test_agent_sees_only_the_dealers_of_his_portfolio(): void
    {
        $agent = $this->agentUser();
        $other = $this->agentUser();
        $this->dealerUser([], ['business_name' => 'Negozio Mio', 'agent_id' => $agent->agent->id]);
        $this->dealerUser([], ['business_name' => 'Negozio Altrui', 'agent_id' => $other->agent->id]);
        $this->dealerUser([], ['business_name' => 'Negozio Libero']);

        $this->actingAs($agent)->get('/agente')
            ->assertOk()->assertSee('Negozio Mio')->assertDontSee('Negozio Altrui')->assertDontSee('Negozio Libero');
    }

    public function test_dealer_without_complete_registry_cannot_order(): void
    {
        $incomplete = $this->dealerUser();
        $complete = $this->dealerUser([], ['registry_completed_at' => now()]);

        $this->actingAs($incomplete)->get('/dealer')->assertOk()->assertDontSee('Ordina adesso')->assertSee('anagrafica sarà completa');
        $this->actingAs($complete)->get('/dealer')->assertOk()->assertSee('Ordina adesso');
    }

    public function test_manager_assigns_a_dealer_to_an_agent(): void
    {
        $manager = User::factory()->create(['role' => 'admin']);
        $agent = $this->agentUser();
        $dealer = $this->dealerUser()->dealer;

        $this->actingAs($manager)->post("/manager/rete/dealer/{$dealer->id}/agente", ['agent_id' => $agent->agent->id])->assertRedirect();
        $this->assertSame($agent->agent->id, $dealer->fresh()->agent_id);
        $this->assertDatabaseHas('activity_logs', ['action' => 'dealer.assigned', 'user_id' => $manager->id]);
    }
}
