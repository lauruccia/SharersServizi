<?php

namespace Tests\Feature;

use App\Mail\CredentialsMail;
use App\Mail\RegistrySubmittedMail;
use App\Models\Agent;
use App\Models\Dealer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistryTest extends TestCase
{
    use RefreshDatabase;

    private User $agentUser;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
        $this->agentUser = User::factory()->create(['role' => 'agent', 'name' => 'Anna Bianchi']);
        Agent::query()->create(['user_id' => $this->agentUser->id]);
    }

    private function payload(array $extra = [], bool $documents = true): array
    {
        return array_merge([
            'business_name' => 'CAF Verdi Srl',
            'vat_number' => '01234567890',
            'sdi_code' => 'ABC1234',
            'iban' => 'IT60X0542811101000000123456',
            'pec' => 'cafverdi@pec.example.com',
            'legal_address' => 'Via Milano 3, Villa San Giovanni',
            'operating_address' => 'Corso Garibaldi 10, Villa San Giovanni',
            'email' => 'verdi@example.com',
            'legal_rep_mobile' => '3331112222',
            'expected_sim' => 40, 'expected_energy' => 10, 'expected_fiber' => 5,
            'billing_regime' => 'forfettario',
            'privacy' => '1',
        ], $documents ? ['documents' => [
            'visura' => UploadedFile::fake()->create('visura.pdf', 200, 'application/pdf'),
            'documento' => UploadedFile::fake()->image('carta.jpg'),
            'foto_interna' => UploadedFile::fake()->image('interno.jpg'),
            'foto_esterna' => UploadedFile::fake()->image('esterno.png'),
        ]] : [], $extra);
    }

    public function test_agent_submits_a_new_dealer_and_manager_activates_it_with_credentials(): void
    {
        $this->actingAs($this->agentUser)->get('/agente/anagrafica')->assertOk();
        $this->actingAs($this->agentUser)->post('/agente/anagrafica', $this->payload())->assertRedirect('/agente')->assertSessionHasNoErrors();

        $dealer = Dealer::query()->with('documents')->sole();
        $this->assertSame($this->agentUser->agent->id, $dealer->agent_id);
        $this->assertSame('in_verifica', $dealer->registryState());
        $this->assertFalse($dealer->canOrder());
        $this->assertNull($dealer->user_id);
        $this->assertCount(4, $dealer->documents);
        foreach ($dealer->documents as $document) {
            Storage::disk('local')->assertExists($document->path);
        }
        Mail::assertSent(RegistrySubmittedMail::class, fn ($m) => $m->hasTo('affiliazioni@effepimaster.it') && ! str_contains($m->render(), 'IT60X0542811101000000123456'));

        $manager = User::factory()->create(['role' => 'admin']);
        $this->actingAs($manager)->get('/manager/anagrafiche')->assertOk()->assertSee('CAF Verdi Srl')->assertSee('IT60X0542811101000000123456');
        $this->actingAs($manager)->get('/manager/anagrafiche/documenti/'.$dealer->documents[0]->id)->assertOk();
        $this->actingAs($manager)->post("/manager/anagrafiche/{$dealer->id}/attiva")->assertRedirect()->assertSessionHasNoErrors();

        $dealer->refresh();
        $this->assertTrue($dealer->canOrder());
        $this->assertSame('dealer', $dealer->user->role);
        $this->assertTrue($dealer->user->must_change_password);
        Mail::assertSent(CredentialsMail::class, fn ($m) => $m->hasTo('verdi@example.com'));
    }

    public function test_agent_completes_the_registry_of_a_dealer_who_registered_from_the_home(): void
    {
        $dealerUser = User::factory()->create(['role' => 'dealer', 'email' => 'verdi@example.com']);
        $dealer = Dealer::query()->create(['user_id' => $dealerUser->id, 'agent_id' => $this->agentUser->agent->id, 'business_name' => 'CAF Verdi']);

        $this->actingAs($this->agentUser)->get('/agente/anagrafica/'.$dealer->id)->assertOk()->assertSee('CAF Verdi');
        $this->actingAs($this->agentUser)->post('/agente/anagrafica/'.$dealer->id, $this->payload())->assertRedirect('/agente')->assertSessionHasNoErrors();
        $this->assertSame(1, Dealer::query()->count());

        $manager = User::factory()->create(['role' => 'admin']);
        $this->actingAs($manager)->post("/manager/anagrafiche/{$dealer->id}/attiva")->assertRedirect();

        $this->assertTrue($dealer->fresh()->canOrder());
        $this->assertSame($dealerUser->id, $dealer->fresh()->user_id);
        Mail::assertNotSent(CredentialsMail::class); // aveva già il suo accesso
        $this->actingAs($dealerUser)->get('/ordini/nuovo/optima')->assertOk();
    }

    public function test_registry_validation(): void
    {
        $this->actingAs($this->agentUser);
        $this->post('/agente/anagrafica', $this->payload(['vat_number' => '123']))->assertSessionHasErrors('vat_number');
        $this->post('/agente/anagrafica', $this->payload(['iban' => 'IT60 X054']))->assertSessionHasErrors('iban');
        $this->post('/agente/anagrafica', $this->payload(['billing_regime' => 'altro']))->assertSessionHasErrors('billing_regime');
        $this->post('/agente/anagrafica', $this->payload(['privacy' => null]))->assertSessionHasErrors('privacy');
        $this->post('/agente/anagrafica', $this->payload([], false))->assertSessionHasErrors('documents.visura');
        $this->post('/agente/anagrafica', $this->payload(['documents' => ['visura' => UploadedFile::fake()->create('virus.exe', 10)]]))->assertSessionHasErrors('documents.visura');
        $this->assertSame(0, Dealer::query()->count());
    }

    public function test_only_the_dealers_own_agent_can_fill_the_registry_and_only_managers_see_documents(): void
    {
        $other = User::factory()->create(['role' => 'agent']);
        Agent::query()->create(['user_id' => $other->id]);
        $this->actingAs($this->agentUser)->post('/agente/anagrafica', $this->payload());
        $dealer = Dealer::query()->with('documents')->sole();

        $this->actingAs($other)->get('/agente/anagrafica/'.$dealer->id)->assertForbidden();
        $this->actingAs($other)->post('/agente/anagrafica/'.$dealer->id, $this->payload())->assertForbidden();
        $this->actingAs($this->agentUser)->get('/manager/anagrafiche/documenti/'.$dealer->documents[0]->id)->assertForbidden();
        $this->actingAs($this->agentUser)->post("/manager/anagrafiche/{$dealer->id}/attiva")->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'dealer']))->get('/agente/anagrafica')->assertForbidden();
    }

    public function test_resubmission_keeps_existing_documents(): void
    {
        $this->actingAs($this->agentUser)->post('/agente/anagrafica', $this->payload());
        $dealer = Dealer::query()->sole();

        $this->actingAs($this->agentUser)
            ->post('/agente/anagrafica/'.$dealer->id, $this->payload(['legal_rep_mobile' => '3339998888', 'documents' => ['visura' => UploadedFile::fake()->create('nuova-visura.pdf', 100, 'application/pdf')]], false))
            ->assertRedirect('/agente')->assertSessionHasNoErrors();

        $this->assertSame('3339998888', $dealer->fresh()->legal_rep_mobile);
        $this->assertSame(4, $dealer->documents()->count());
        $this->assertSame('nuova-visura.pdf', $dealer->documents()->where('type', 'visura')->sole()->original_name);
    }
}
