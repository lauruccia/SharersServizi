<?php

namespace Tests\Feature;

use App\Mail\CredentialsMail;
use App\Mail\InfoRequestedMail;
use App\Mail\NewRegistrationMail;
use App\Mail\RegistrationReceivedMail;
use App\Models\RegistrationRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Martedì 10 novembre 2026, ore 9.
        CarbonImmutable::setTestNow('2026-11-10 09:00:00');
        \Carbon\Carbon::setTestNow('2026-11-10 09:00:00');
        $this->seed(DatabaseSeeder::class);
        Mail::fake();
    }

    private function dealerPayload(array $override = []): array
    {
        return array_merge([
            'business_name' => 'Telefonia Rossi',
            'name' => 'Mario Rossi',
            'phone' => '3331234567',
            'email' => 'mario@example.com',
            'activity_type' => 'CAF',
            'region' => 'Calabria',
            'municipality' => 'Reggio Calabria',
            'meeting_date' => '2026-11-11', // mercoledì
            'meeting_time' => '14:30',
            'privacy' => '1',
        ], $override);
    }

    private function agentPayload(array $override = []): array
    {
        return array_merge([
            'name' => 'Anna Bianchi',
            'phone' => '3339876543',
            'email' => 'anna@example.com',
            'region' => 'Lazio',
            'zones' => 'Latina e Frosinone',
            'experience' => 'Dieci anni nella telefonia',
            'meeting_date' => '2026-11-13', // venerdì
            'meeting_time' => '18:30',
            'privacy' => '1',
        ], $override);
    }

    private function manager(string $username = 'roberto'): User
    {
        $user = User::query()->where('username', $username)->firstOrFail();
        $user->update(['must_change_password' => false]);

        return $user;
    }

    public function test_home_page_opens(): void
    {
        $this->get('/')->assertOk()->assertSee('Registrati come dealer')->assertSee('Registrati come agente');
    }

    public function test_slots_are_every_thirty_minutes_from_14_to_19_on_allowed_days(): void
    {
        $slots = $this->getJson('/videocall/orari?data=2026-11-11&regione=Calabria')->assertOk()->json('slots');
        $this->assertSame('14:00', $slots[0]);
        $this->assertSame('18:30', end($slots));
        $this->assertCount(10, $slots);

        // Giovedì: nessuna videocall.
        $this->getJson('/videocall/orari?data=2026-11-12&regione=Calabria')
            ->assertJson(['bookable' => false, 'slots' => []]);
    }

    public function test_dealer_registration_books_the_call_with_the_manager_of_the_region(): void
    {
        $this->post('/registrazione/dealer', $this->dealerPayload())->assertRedirect('/richiesta-inviata');

        $request = RegistrationRequest::query()->sole();
        $this->assertSame('dealer', $request->profile);
        $this->assertSame('in_attesa', $request->status);
        $this->assertSame('2026-11-11 14:30', $request->meeting_at->format('Y-m-d H:i'));
        $this->assertSame('roberto', $request->manager->username);
        Mail::assertSent(RegistrationReceivedMail::class, fn ($m) => $m->hasTo('mario@example.com'));
        Mail::assertSent(NewRegistrationMail::class, fn ($m) => $m->hasTo('roberto@effepimaster.it'));
    }

    public function test_lazio_and_sardegna_go_to_stefania(): void
    {
        $this->post('/registrazione/agente', $this->agentPayload())->assertRedirect('/richiesta-inviata');
        $this->assertSame('stefania', RegistrationRequest::query()->sole()->manager->username);
    }

    public function test_a_booked_slot_cannot_be_taken_twice_for_the_same_manager(): void
    {
        $this->post('/registrazione/dealer', $this->dealerPayload());
        $this->post('/registrazione/dealer', $this->dealerPayload(['email' => 'altro@example.com']))
            ->assertSessionHasErrorsIn('dealer', ['meeting_time']);
        $this->assertSame(1, RegistrationRequest::query()->count());

        $slots = $this->getJson('/videocall/orari?data=2026-11-11&regione=Calabria')->json('slots');
        $this->assertNotContains('14:30', $slots);
        // Stefania, che segue un'altra regione, a quell'ora è libera.
        $this->assertContains('14:30', $this->getJson('/videocall/orari?data=2026-11-11&regione=Lazio')->json('slots'));
    }

    public function test_registration_is_refused_on_wrong_day_time_or_without_privacy(): void
    {
        $this->post('/registrazione/dealer', $this->dealerPayload(['meeting_date' => '2026-11-12']))
            ->assertSessionHasErrorsIn('dealer', ['meeting_time']);
        $this->post('/registrazione/dealer', $this->dealerPayload(['meeting_time' => '10:00']))
            ->assertSessionHasErrorsIn('dealer', ['meeting_time']);
        $this->post('/registrazione/dealer', $this->dealerPayload(['privacy' => null]))
            ->assertSessionHasErrorsIn('dealer', ['privacy']);
        $this->assertSame(0, RegistrationRequest::query()->count());
    }

    public function test_approval_creates_the_account_and_sends_credentials(): void
    {
        $this->post('/registrazione/dealer', $this->dealerPayload());
        $request = RegistrationRequest::query()->sole();

        $this->actingAs($this->manager())->post("/manager/richieste/{$request->id}/approva")->assertRedirect();

        $user = User::query()->where('email', 'mario@example.com')->sole();
        $this->assertSame('dealer', $user->role);
        $this->assertSame('telefonia.rossi', $user->username);
        $this->assertTrue($user->must_change_password);
        $this->assertSame('Telefonia Rossi', $user->dealer->business_name);
        $this->assertFalse($user->dealer->canOrder());
        $this->assertSame('approvata', $request->fresh()->status);
        $this->assertSame('roberto', $request->fresh()->decider->username);

        Mail::assertSent(CredentialsMail::class, function (CredentialsMail $mail) use ($user) {
            return $mail->hasTo('mario@example.com')
                && Hash::check($mail->temporaryPassword, $user->password)
                && str_contains($mail->render(), $mail->temporaryPassword)
                && str_contains($mail->render(), 'telefonia.rossi');
        });
    }

    public function test_approved_agent_gets_the_agent_area(): void
    {
        $this->post('/registrazione/agente', $this->agentPayload());
        $request = RegistrationRequest::query()->sole();
        $this->actingAs($this->manager('stefania'))->post("/manager/richieste/{$request->id}/approva");

        $user = User::query()->where('email', 'anna@example.com')->sole();
        $this->assertSame('agent', $user->role);
        $this->assertNotNull($user->agent);
        $this->assertSame('agent.area', $user->homeRoute());
    }

    public function test_no_credentials_before_approval_and_none_on_rejection(): void
    {
        $this->post('/registrazione/dealer', $this->dealerPayload());
        $request = RegistrationRequest::query()->sole();
        $this->assertSame(0, User::query()->where('role', 'dealer')->count());

        $this->actingAs($this->manager())->post("/manager/richieste/{$request->id}/rifiuta")->assertRedirect();
        $this->assertSame('rifiutata', $request->fresh()->status);
        $this->assertSame(0, User::query()->where('role', 'dealer')->count());
        Mail::assertNotSent(CredentialsMail::class);

        // Una richiesta già lavorata non si può approvare.
        $this->post("/manager/richieste/{$request->id}/approva")->assertStatus(409);
        // E l'orario torna libero.
        auth()->logout();
        $this->assertContains('14:30', $this->getJson('/videocall/orari?data=2026-11-11&regione=Calabria')->json('slots'));
    }

    public function test_manager_can_ask_for_information(): void
    {
        $this->post('/registrazione/dealer', $this->dealerPayload());
        $request = RegistrationRequest::query()->sole();

        $this->actingAs($this->manager())
            ->post("/manager/richieste/{$request->id}/informazioni", ['body' => 'Da quanti anni è aperto il negozio?'])
            ->assertRedirect();

        $this->assertSame('in_attesa', $request->fresh()->status);
        $this->assertSame(1, $request->messages()->count());
        Mail::assertSent(InfoRequestedMail::class, fn ($m) => $m->hasTo('mario@example.com'));
    }

    public function test_spam_trap_blocks_bots(): void
    {
        $this->post('/registrazione/dealer', $this->dealerPayload(['website' => 'http://spam.example']))
            ->assertSessionHasErrorsIn('dealer', ['website']);
        $this->assertSame(0, RegistrationRequest::query()->count());
    }
}
