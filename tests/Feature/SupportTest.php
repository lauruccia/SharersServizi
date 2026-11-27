<?php

namespace Tests\Feature;

use App\Mail\TicketMail;
use App\Models\Agent;
use App\Models\Dealer;
use App\Models\Faq;
use App\Models\Resource;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupportTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $agentUser;
    private Agent $agent;
    private User $dealerUser;
    private Dealer $dealer;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('uploads');
        $this->manager = User::factory()->create(['role' => 'admin']);
        $this->agentUser = User::factory()->create(['role' => 'agent']);
        $this->agent = Agent::query()->create(['user_id' => $this->agentUser->id]);
        $this->dealerUser = User::factory()->create(['role' => 'dealer']);
        $this->dealer = Dealer::query()->create(['user_id' => $this->dealerUser->id, 'agent_id' => $this->agent->id, 'business_name' => 'Negozio Uno', 'registry_completed_at' => now()]);
    }

    private function openTicket(): Ticket
    {
        $this->actingAs($this->dealerUser)->post('/segnalazioni', [
            'category' => 'ordini', 'subject' => 'Ordine non arrivato', 'body' => 'Aspetto da una settimana.',
        ])->assertRedirect();

        return Ticket::query()->firstOrFail();
    }

    public function test_dealer_opens_a_ticket_and_back_office_is_notified(): void
    {
        $ticket = $this->openTicket();

        $this->assertSame($this->dealer->id, $ticket->dealer_id);
        $this->assertSame($this->agent->id, $ticket->agent_id);
        $this->assertSame(Ticket::OPEN, $ticket->status);
        $this->assertCount(1, $ticket->messages);
        Mail::assertSent(TicketMail::class, fn ($m) => $m->hasTo(config('effepi.mail.backoffice')) && $m->toStaff);
    }

    public function test_manager_reply_notifies_the_author_and_marks_answered(): void
    {
        $ticket = $this->openTicket();

        $this->actingAs($this->manager)->post("/segnalazioni/{$ticket->id}/rispondi", ['body' => 'Spedito oggi.'])->assertRedirect();

        $this->assertSame(Ticket::ANSWERED, $ticket->fresh()->status);
        $this->assertTrue($ticket->messages()->get()->last()->is_staff);
        Mail::assertSent(TicketMail::class, fn ($m) => $m->hasTo($this->dealerUser->email) && ! $m->toStaff);
    }

    public function test_user_reply_reopens_a_closed_ticket(): void
    {
        $ticket = $this->openTicket();
        $this->actingAs($this->dealerUser)->post("/segnalazioni/{$ticket->id}/chiudi")->assertRedirect();
        $this->assertSame(Ticket::CLOSED, $ticket->fresh()->status);

        $this->actingAs($this->dealerUser)->post("/segnalazioni/{$ticket->id}/rispondi", ['body' => 'Ancora niente.'])->assertRedirect();
        $this->assertSame(Ticket::OPEN, $ticket->fresh()->status);
    }

    public function test_ticket_visibility_is_limited_to_author_portfolio_agent_and_managers(): void
    {
        $ticket = $this->openTicket();

        $this->actingAs($this->agentUser)->get("/segnalazioni/{$ticket->id}")->assertOk();
        $this->actingAs($this->manager)->get("/segnalazioni/{$ticket->id}")->assertOk();

        $other = User::factory()->create(['role' => 'dealer']);
        Dealer::query()->create(['user_id' => $other->id, 'business_name' => 'Altro', 'registry_completed_at' => now()]);
        $this->actingAs($other)->get("/segnalazioni/{$ticket->id}")->assertForbidden();
        $this->actingAs($other)->post("/segnalazioni/{$ticket->id}/rispondi", ['body' => 'x'])->assertForbidden();

        $otherAgent = User::factory()->create(['role' => 'agent']);
        Agent::query()->create(['user_id' => $otherAgent->id]);
        $this->actingAs($otherAgent)->get("/segnalazioni/{$ticket->id}")->assertForbidden();
    }

    public function test_agent_can_only_link_a_ticket_to_own_dealers(): void
    {
        $foreign = Dealer::query()->create(['user_id' => User::factory()->create(['role' => 'dealer'])->id, 'business_name' => 'Non mio']);

        $this->actingAs($this->agentUser)->post('/segnalazioni', [
            'category' => 'altro', 'subject' => 'X', 'body' => 'Y', 'dealer_id' => $foreign->id,
        ])->assertSessionHasErrors('dealer_id');

        $this->actingAs($this->agentUser)->post('/segnalazioni', [
            'category' => 'altro', 'subject' => 'X', 'body' => 'Y', 'dealer_id' => $this->dealer->id,
        ])->assertRedirect();
        $this->assertSame($this->dealer->id, Ticket::query()->latest('id')->first()->dealer_id);
    }

    public function test_manager_publishes_a_resource_file_and_a_link_and_dealers_see_them(): void
    {
        $this->actingAs($this->manager)->post('/manager/materiali', [
            'kind' => 'moduli', 'title' => 'Modulo recesso', 'file' => UploadedFile::fake()->create('recesso.pdf', 100, 'application/pdf'),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($this->manager)->post('/manager/materiali', [
            'kind' => 'video', 'title' => 'Come si attiva Optima', 'url' => 'https://www.youtube.com/watch?v=abc',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $file = Resource::query()->where('kind', 'moduli')->firstOrFail();
        Storage::disk('uploads')->assertExists($file->file_path);

        $this->actingAs($this->dealerUser)->get('/dealer')->assertOk()->assertSee('Modulo recesso')->assertSee('Come si attiva Optima');

        $this->actingAs($this->manager)->post("/manager/materiali/{$file->id}/elimina")->assertRedirect();
        Storage::disk('uploads')->assertMissing($file->file_path);
    }

    public function test_resource_needs_a_file_or_a_link_and_is_manager_only(): void
    {
        $this->actingAs($this->manager)->post('/manager/materiali', ['kind' => 'moduli', 'title' => 'Vuoto'])->assertSessionHasErrors(['file', 'url']);
        $this->actingAs($this->dealerUser)->get('/manager/materiali')->assertForbidden();
        $this->actingAs($this->dealerUser)->post('/manager/faq', ['question' => 'a', 'answer' => 'b'])->assertForbidden();
    }

    public function test_assistant_finds_the_best_faq_and_falls_back_to_a_ticket(): void
    {
        Faq::query()->create(['question' => 'Come si paga un ordine?', 'answer' => 'Con bonifico bancario indicando il numero ordine come causale.', 'keywords' => 'iban, pagamento']);
        Faq::query()->create(['question' => 'Quando arrivano le SIM?', 'answer' => 'Entro cinque giorni lavorativi.']);
        Faq::query()->create(['question' => 'Domanda nascosta pagamento', 'answer' => 'pagamento', 'active' => false]);

        $this->actingAs($this->dealerUser)->get('/assistente?q='.urlencode('Come posso fare il pagamento?'))
            ->assertOk()->assertSee('Con bonifico bancario')->assertDontSee('nascosta');

        $this->actingAs($this->dealerUser)->get('/assistente?q='.urlencode('zzzz qqqq'))
            ->assertOk()->assertSee('Non ho trovato una risposta');
    }

    public function test_manager_manages_faqs_including_hiding_one(): void
    {
        $this->actingAs($this->manager)->post('/manager/faq', ['question' => 'Domanda?', 'answer' => 'Risposta.'])->assertRedirect();
        $faq = Faq::query()->firstOrFail();
        $this->assertTrue($faq->active);

        $this->actingAs($this->manager)->post("/manager/faq/{$faq->id}", ['question' => 'Domanda?', 'answer' => 'Nuova.'])->assertRedirect();
        $this->assertFalse($faq->fresh()->active);
        $this->assertSame('Nuova.', $faq->fresh()->answer);
    }
}
