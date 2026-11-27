<?php

namespace Tests\Feature;

use App\Mail\TemporaryPasswordMail;
use App\Models\Agent;
use App\Models\AreaHead;
use App\Models\Dealer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Gerarchia: superadmin > admin > capoarea > agente > dealer. Ognuno vede chi sta sotto, mai chi sta sopra. */
class HierarchyTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    private User $admin;

    private User $headUser;

    private User $otherHeadUser;

    private array $agents = [];

    private array $dealers = [];

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->superadmin = User::factory()->create(['role' => 'superadmin']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->headUser = User::factory()->create(['role' => 'capoarea']);
        $this->otherHeadUser = User::factory()->create(['role' => 'capoarea']);
        $head = AreaHead::query()->create(['user_id' => $this->headUser->id]);
        $other = AreaHead::query()->create(['user_id' => $this->otherHeadUser->id]);

        // a1, a2 sotto il capoarea; a3 sotto un altro capoarea.
        foreach (['a1' => $head, 'a2' => $head, 'a3' => $other] as $key => $areaHead) {
            $user = User::factory()->create(['role' => 'agent']);
            $agent = Agent::query()->create(['user_id' => $user->id, 'area_head_id' => $areaHead->id]);
            $dealerUser = User::factory()->create(['role' => 'dealer']);
            $dealer = Dealer::query()->create([
                'user_id' => $dealerUser->id, 'agent_id' => $agent->id, 'business_name' => 'Negozio '.$key,
                'registry_completed_at' => now(),
            ]);
            Order::query()->create([
                'number' => 'ORD-'.$key, 'type' => 'generico', 'user_id' => $dealerUser->id, 'dealer_id' => $dealer->id,
                'agent_id' => $agent->id, 'business_name' => $dealer->business_name, 'delivery_address' => 'Via Test 1', 'customer_email' => 'x@example.com', 'order_date' => now()->toDateString(),
                'shipping_cost' => 0, 'total' => 10, 'payment_method' => 'bonifico', 'payment_status' => 'in_attesa',
            ]);
            $this->agents[$key] = [$user, $agent];
            $this->dealers[$key] = [$dealerUser, $dealer];
        }
    }

    public function test_every_role_lands_on_its_own_area(): void
    {
        $this->assertSame('manager.requests', $this->superadmin->homeRoute());
        $this->assertSame('manager.requests', $this->admin->homeRoute());
        $this->assertSame('areahead.area', $this->headUser->homeRoute());
        $this->assertSame('agent.area', $this->agents['a1'][0]->homeRoute());
        $this->assertSame('dealer.area', $this->dealers['a1'][0]->homeRoute());
    }

    public function test_back_office_sees_everything(): void
    {
        foreach ([$this->superadmin, $this->admin] as $user) {
            $this->assertSame(3, Order::query()->visibleTo($user)->count());
            $this->assertSame(3, Dealer::query()->visibleTo($user)->count());
            $this->assertSame(3, Agent::query()->visibleTo($user)->count());
        }
    }

    public function test_area_head_sees_only_agents_dealers_and_orders_below_them(): void
    {
        $this->assertEqualsCanonicalizing([$this->agents['a1'][1]->id, $this->agents['a2'][1]->id], Agent::query()->visibleTo($this->headUser)->pluck('id')->all());
        $this->assertEqualsCanonicalizing(['Negozio a1', 'Negozio a2'], Dealer::query()->visibleTo($this->headUser)->pluck('business_name')->all());
        $this->assertEqualsCanonicalizing(['ORD-a1', 'ORD-a2'], Order::query()->visibleTo($this->headUser)->pluck('number')->all());

        $this->actingAs($this->headUser)->get('/capoarea')->assertOk()
            ->assertSee('Negozio a1')->assertSee('Negozio a2')->assertDontSee('Negozio a3');
        $this->actingAs($this->headUser)->get('/ordini')->assertOk()
            ->assertSee('ORD-a1')->assertDontSee('ORD-a3');
    }

    public function test_nobody_sees_data_of_those_above_or_beside_them(): void
    {
        // L'agente vede solo i suoi dealer e ordini.
        $this->assertSame(['Negozio a1'], Dealer::query()->visibleTo($this->agents['a1'][0])->pluck('business_name')->all());
        $this->assertSame(['ORD-a1'], Order::query()->visibleTo($this->agents['a1'][0])->pluck('number')->all());
        // Il dealer solo se stesso; non vede agenti.
        $this->assertSame(['ORD-a1'], Order::query()->visibleTo($this->dealers['a1'][0])->pluck('number')->all());
        $this->assertSame(0, Agent::query()->visibleTo($this->dealers['a1'][0])->count());

        // Un ordine di un altro ramo dà 403 sulla scheda.
        $foreign = Order::query()->where('number', 'ORD-a3')->first();
        $this->actingAs($this->headUser)->get('/ordini/'.$foreign->id)->assertForbidden();
        $this->actingAs($this->agents['a1'][0])->get('/ordini/'.$foreign->id)->assertForbidden();
        $this->actingAs($this->otherHeadUser)->get('/ordini/'.$foreign->id)->assertOk();
        // Il capoarea vede l'ordine di un dealer sotto di lui.
        $own = Order::query()->where('number', 'ORD-a2')->first();
        $this->actingAs($this->headUser)->get('/ordini/'.$own->id)->assertOk();
    }

    public function test_area_head_is_read_only_and_stays_out_of_other_areas(): void
    {
        $this->actingAs($this->headUser)->get('/manager')->assertForbidden();
        $this->actingAs($this->headUser)->get('/agente')->assertForbidden();
        $this->actingAs($this->headUser)->get('/dealer')->assertForbidden();
        $this->actingAs($this->headUser)->get('/ordini/nuovo/optima')->assertForbidden();
        $this->actingAs($this->agents['a1'][0])->get('/capoarea')->assertForbidden();
        $this->actingAs($this->admin)->get('/capoarea')->assertForbidden();
    }

    public function test_admin_cannot_touch_a_superadmin_but_superadmin_can_manage_admin(): void
    {
        $this->assertFalse($this->admin->canManageUser($this->superadmin));
        $this->assertTrue($this->superadmin->canManageUser($this->admin));
        $this->assertFalse($this->superadmin->canManageUser($this->superadmin), 'nessuno agisce su se stesso');
        $this->assertFalse($this->headUser->canManageUser($this->agents['a1'][0]));

        $this->actingAs($this->admin)->post('/manager/rete/utenti/'.$this->superadmin->id.'/stato')->assertForbidden();
        $this->actingAs($this->admin)->post('/manager/rete/utenti/'.$this->superadmin->id.'/password')->assertForbidden();
        $this->assertTrue($this->superadmin->fresh()->active);

        $this->actingAs($this->admin)->post('/manager/rete/utenti/'.$this->headUser->id.'/stato')->assertRedirect();
        $this->assertFalse($this->headUser->fresh()->active);
        $this->actingAs($this->superadmin)->post('/manager/rete/utenti/'.$this->admin->id.'/stato')->assertRedirect();
        $this->assertFalse($this->admin->fresh()->active);
    }

    public function test_staff_creation_follows_the_hierarchy(): void
    {
        $this->actingAs($this->admin)->post('/manager/rete/staff', ['role' => 'admin', 'name' => 'Nuovo Admin', 'email' => 'na@example.com'])
            ->assertSessionHasErrors('role');
        $this->actingAs($this->admin)->post('/manager/rete/staff', ['role' => 'superadmin', 'name' => 'X', 'email' => 'x@example.com'])
            ->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'na@example.com']);

        $this->actingAs($this->admin)->post('/manager/rete/staff', ['role' => 'capoarea', 'name' => 'Marco Verdi', 'email' => 'mv@example.com'])
            ->assertRedirect();
        $created = User::query()->where('email', 'mv@example.com')->firstOrFail();
        $this->assertSame('capoarea', $created->role);
        $this->assertTrue($created->must_change_password);
        $this->assertNotNull($created->areaHead);
        Mail::assertSent(TemporaryPasswordMail::class, fn ($m) => $m->hasTo('mv@example.com'));

        $this->actingAs($this->superadmin)->post('/manager/rete/staff', ['role' => 'admin', 'name' => 'Nuovo Admin', 'email' => 'na@example.com'])
            ->assertRedirect();
        $this->assertSame('admin', User::query()->where('email', 'na@example.com')->value('role'));

        // Chi sta sotto l'admin non crea accessi.
        $this->actingAs($this->headUser)->post('/manager/rete/staff', ['role' => 'capoarea', 'name' => 'Y', 'email' => 'y@example.com'])->assertForbidden();
    }

    public function test_back_office_assigns_an_agent_to_an_area_head(): void
    {
        $agent = $this->agents['a3'][1];
        $head = $this->headUser->areaHead;
        $this->actingAs($this->admin)->post('/manager/rete/agenti/'.$agent->id.'/capoarea', ['area_head_id' => $head->id])->assertRedirect();
        $this->assertSame($head->id, $agent->fresh()->area_head_id);
        $this->assertSame(3, Dealer::query()->visibleTo($this->headUser)->count());

        $this->actingAs($this->admin)->post('/manager/rete/agenti/'.$agent->id.'/capoarea', ['area_head_id' => null])->assertRedirect();
        $this->assertNull($agent->fresh()->area_head_id);

        $this->actingAs($this->headUser)->post('/manager/rete/agenti/'.$agent->id.'/capoarea', ['area_head_id' => $head->id])->assertForbidden();
    }

    public function test_network_page_opens_for_admin_and_superadmin(): void
    {
        $this->actingAs($this->admin)->get('/manager/rete')->assertOk()->assertSee('Capoarea');
        $this->actingAs($this->superadmin)->get('/manager/rete')->assertOk()->assertSee('Accessi interni');
    }
}
