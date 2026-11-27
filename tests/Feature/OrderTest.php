<?php

namespace Tests\Feature;

use App\Mail\OrderPlacedMail;
use App\Models\Agent;
use App\Models\Dealer;
use App\Models\Order;
use App\Models\Promo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $agentUser;

    private User $dealerUser;

    private Dealer $dealer;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->agentUser = User::factory()->create(['role' => 'agent', 'name' => 'Anna Bianchi']);
        $agent = Agent::query()->create(['user_id' => $this->agentUser->id]);
        $this->dealerUser = User::factory()->create(['role' => 'dealer']);
        $this->dealer = Dealer::query()->create([
            'user_id' => $this->dealerUser->id, 'agent_id' => $agent->id, 'business_name' => 'Telefonia Rossi',
            'email' => 'rossi@example.com', 'operating_address' => 'Via Roma 1, Reggio Calabria', 'registry_completed_at' => now(),
        ]);
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'dealer_id' => $this->dealer->id,
            'business_name' => 'Telefonia Rossi',
            'delivery_address' => 'Via Roma 1, Reggio Calabria',
            'customer_email' => 'rossi@example.com',
            'order_date' => now()->toDateString(),
            'payment_method' => 'bonifico',
        ], $extra);
    }

    public function test_all_four_order_forms_open(): void
    {
        foreach (['optima', 'sim', 'promo', 'altro'] as $slug) {
            $this->actingAs($this->dealerUser)->get('/ordini/nuovo/'.$slug)->assertOk();
            $this->actingAs($this->agentUser)->get('/ordini/nuovo/'.$slug)->assertOk()->assertSee('Dealer per cui ordini');
        }
    }

    public function test_optima_order_is_priced_by_the_server_and_emailed_with_copy_to_the_customer(): void
    {
        $this->actingAs($this->dealerUser)
            ->post('/ordini/nuovo/optima', $this->payload(['plafond' => 400, 'sim_quantity' => 60, 'total' => 1]))
            ->assertRedirect();

        $order = Order::query()->with('items')->sole();
        $this->assertSame('ORD-'.now()->year.'-00001', $order->number);
        $this->assertSame('optima', $order->type);
        $this->assertSame('376.00', $order->items[0]->amount); // 400 € - 6%
        $this->assertSame('150.00', $order->items[1]->amount); // 60 SIM a 2,50 €
        $this->assertSame('6.00', $order->shipping_cost);
        $this->assertSame('532.00', $order->total);
        $this->assertSame('Anna Bianchi', $order->agent_name);
        $this->assertSame('in_attesa', $order->payment_status);

        Mail::assertSent(OrderPlacedMail::class, fn (OrderPlacedMail $m) => $m->hasTo('gestioneordini@sharers.it')
            && $m->hasCc('rossi@example.com') && str_contains($m->render(), '532,00'));
        $this->actingAs($this->dealerUser)->get('/ordini/'.$order->id)->assertOk()->assertSee($order->number)->assertSee('532,00');
    }

    public function test_plafond_only_has_no_shipping(): void
    {
        $this->actingAs($this->dealerUser)->post('/ordini/nuovo/optima', $this->payload(['plafond' => 100]));
        $order = Order::query()->sole();
        $this->assertSame('0.00', $order->shipping_cost);
        $this->assertSame('96.00', $order->total);
    }

    public function test_invalid_optima_orders_are_refused(): void
    {
        $this->actingAs($this->dealerUser);
        $this->post('/ordini/nuovo/optima', $this->payload())->assertSessionHasErrors('order');
        $this->post('/ordini/nuovo/optima', $this->payload(['sim_quantity' => 30]))->assertSessionHasErrors('order');
        $this->post('/ordini/nuovo/optima', $this->payload(['plafond' => 250]))->assertSessionHasErrors('order');
        $this->post('/ordini/nuovo/optima', $this->payload(['plafond' => 100, 'payment_method' => 'carta']))->assertSessionHasErrors('payment_method');
        $this->assertSame(0, Order::query()->count());
        Mail::assertNothingSent();
    }

    public function test_other_operators_order(): void
    {
        $this->actingAs($this->dealerUser)
            ->post('/ordini/nuovo/sim', $this->payload(['packages' => [0 => 30, 1 => 0, 2 => 200]]))->assertRedirect();
        $order = Order::query()->with('items')->sole();
        $this->assertCount(2, $order->items);
        $this->assertSame('8.00', $order->shipping_cost);
        $this->assertSame('1878.00', $order->total); // 270 + 1600 + 8
    }

    public function test_promo_order_uses_the_promo_price_and_refuses_expired_promos(): void
    {
        $promo = Promo::query()->create(['name' => 'Smart TV', 'price' => 279, 'duration_hours' => 24, 'starts_at' => now(), 'ends_at' => now()->addDay()]);

        $this->actingAs($this->dealerUser)
            ->post('/ordini/nuovo/promo', $this->payload(['promos' => [$promo->id => 2]]))->assertRedirect();
        $order = Order::query()->with('items')->sole();
        $this->assertSame('558.00', $order->total);
        $this->assertNull($order->shipping_cost);
        $this->assertSame($promo->id, $order->items[0]->promo_id);

        $this->travel(25)->hours();
        $this->actingAs($this->dealerUser)
            ->post('/ordini/nuovo/promo', $this->payload(['promos' => [$promo->id => 1]]))->assertSessionHasErrors('order');
        $this->assertSame(1, Order::query()->count());
    }

    public function test_generic_order_needs_complete_rows_and_goods_type(): void
    {
        $this->actingAs($this->dealerUser);
        $rows = [['quantity' => 2, 'description' => 'Espositore da banco', 'unit_price' => '35.50'], ['quantity' => '', 'description' => '', 'unit_price' => '']];

        $this->post('/ordini/nuovo/altro', $this->payload(['rows' => $rows]))->assertSessionHasErrors('goods_type');
        $this->post('/ordini/nuovo/altro', $this->payload(['goods_type' => 'Materiale', 'rows' => [['quantity' => 2, 'description' => '', 'unit_price' => 5]]]))->assertSessionHasErrors('order');
        $this->post('/ordini/nuovo/altro', $this->payload(['goods_type' => 'Materiale', 'rows' => $rows]))->assertRedirect();
        $this->assertSame('71.00', Order::query()->sole()->total);
    }

    public function test_dealer_without_complete_registry_cannot_order_even_by_posting_directly(): void
    {
        $this->dealer->update(['registry_completed_at' => null]);
        $this->actingAs($this->dealerUser)->get('/ordini/nuovo/optima')->assertRedirect('/dealer');
        $this->actingAs($this->dealerUser)->post('/ordini/nuovo/optima', $this->payload(['plafond' => 100]))->assertSessionHasErrors('dealer_id');
        $this->assertSame(0, Order::query()->count());
    }

    public function test_agent_orders_only_for_dealers_of_his_portfolio(): void
    {
        $otherAgentUser = User::factory()->create(['role' => 'agent']);
        $otherAgent = Agent::query()->create(['user_id' => $otherAgentUser->id]);
        $foreign = Dealer::query()->create(['agent_id' => $otherAgent->id, 'business_name' => 'Negozio Altrui', 'registry_completed_at' => now()]);

        $this->actingAs($this->agentUser)->post('/ordini/nuovo/optima', $this->payload(['dealer_id' => $foreign->id, 'plafond' => 100]))
            ->assertSessionHasErrors('dealer_id');
        $this->actingAs($this->agentUser)->post('/ordini/nuovo/optima', $this->payload(['plafond' => 100]))->assertRedirect();

        $order = Order::query()->sole();
        $this->assertSame($this->dealer->id, $order->dealer_id);
        $this->assertSame($this->agentUser->id, $order->user_id);

        // Chi vede l'ordine: il dealer, il suo agente, non gli altri.
        $this->actingAs($this->dealerUser)->get('/ordini/'.$order->id)->assertOk();
        $this->actingAs($this->agentUser)->get('/ordini')->assertOk()->assertSee($order->number);
        $this->actingAs($otherAgentUser)->get('/ordini/'.$order->id)->assertForbidden();
        $this->actingAs($otherAgentUser)->get('/ordini')->assertOk()->assertDontSee($order->number);
    }

    public function test_manager_sees_all_orders_and_marks_them_paid(): void
    {
        $this->actingAs($this->dealerUser)->post('/ordini/nuovo/optima', $this->payload(['plafond' => 100]));
        $order = Order::query()->sole();
        $manager = User::factory()->create(['role' => 'admin']);

        $this->actingAs($manager)->get('/manager/ordini')->assertOk()->assertSee($order->number);
        $this->actingAs($manager)->post("/manager/ordini/{$order->id}/pagato")->assertRedirect();
        $this->assertTrue($order->fresh()->isPaid());
        $this->assertSame($manager->id, $order->fresh()->paid_marked_by);
        $this->actingAs($this->dealerUser)->post("/manager/ordini/{$order->id}/pagato")->assertForbidden();
    }
}
