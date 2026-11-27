<?php

namespace App\Http\Controllers;

use App\Mail\OrderPlacedMail;
use App\Models\ActivityLog;
use App\Models\Dealer;
use App\Models\Order;
use App\Models\Promo;
use App\Models\User;
use App\Services\OrderBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class OrderController extends Controller
{
    /** Indirizzo → tipo d'ordine. */
    public const SLUGS = ['optima' => 'optima', 'sim' => 'altri_operatori', 'promo' => 'promo', 'altro' => 'generico'];

    public function index(Request $request)
    {
        $user = $request->user();
        $orders = Order::query()->with(['dealer', 'agent.user'])
            ->visibleTo($user)
            ->latest('id')->limit(200)->get();

        return view('orders.index', ['orders' => $orders]);
    }

    public function create(Request $request, string $slug)
    {
        $type = self::SLUGS[$slug];
        $dealers = $this->orderableDealers($request->user());
        if ($dealers->isEmpty()) {
            return redirect()->route($request->user()->homeRoute())->withErrors([
                'order' => $request->user()->role === User::ROLE_DEALER
                    ? 'Potrai ordinare appena la tua anagrafica sarà completa: se ne occupa il tuo agente di riferimento.'
                    : 'Non hai ancora dealer con anagrafica completa per cui ordinare.',
            ]);
        }

        return view('orders.form', [
            'type' => $type,
            'slug' => $slug,
            'title' => ['optima' => 'Nuovo ordine Optima', 'altri_operatori' => 'Ordine SIM altri operatori', 'promo' => 'Ordine Promo Dedicate', 'generico' => 'Nuovo ordine'][$type],
            'dealers' => $dealers,
            'isAgent' => $request->user()->role === User::ROLE_AGENT,
            'promos' => $type === 'promo' ? Promo::query()->active()->orderBy('ends_at')->get() : collect(),
        ]);
    }

    public function store(Request $request, string $slug, OrderBuilder $builder)
    {
        $type = self::SLUGS[$slug];
        $user = $request->user();
        $dealers = $this->orderableDealers($user);

        $data = $request->validate([
            // L'agente sceglie il dealer solo dal proprio portafoglio; il dealer ordina per sé.
            'dealer_id' => ['required', Rule::in($dealers->pluck('id')->all())],
            'business_name' => ['required', 'string', 'max:255'],
            'delivery_address' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'order_date' => ['required', 'date_format:Y-m-d'],
            'payment_method' => ['required', Rule::in(config('effepi.payment_methods'))],
            'notes' => ['nullable', 'string', 'max:2000'],
            'goods_type' => [Rule::requiredIf($type === 'generico'), 'nullable', 'string', 'max:120'],
            'plafond' => ['nullable', 'numeric'],
            'sim_quantity' => ['nullable', 'integer'],
            'packages' => ['nullable', 'array'],
            'promos' => ['nullable', 'array'],
            'rows' => ['nullable', 'array', 'max:5'],
            'rows.*.description' => ['nullable', 'string', 'max:255'],
        ]);
        $dealer = $dealers->firstWhere('id', (int) $data['dealer_id']);

        try {
            $built = $builder->build($type, $request->only(['plafond', 'sim_quantity', 'packages', 'promos', 'rows', 'goods_type']));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['order' => $e->getMessage()])->withInput();
        }

        $order = DB::transaction(function () use ($data, $dealer, $user, $type, $built) {
            $order = Order::query()->create([
                'type' => $type,
                'user_id' => $user->id,
                'dealer_id' => $dealer->id,
                'agent_id' => $dealer->agent_id,
                'business_name' => $data['business_name'],
                'delivery_address' => $data['delivery_address'],
                'customer_email' => $data['customer_email'],
                'agent_name' => $dealer->agent?->user?->name,
                'order_date' => $data['order_date'],
                'goods_type' => $built['goods_type'],
                'shipping_cost' => $built['shipping'],
                'total' => $built['total'],
                'vat_note' => $built['vat_note'],
                'payment_method' => $data['payment_method'],
                'notes' => $data['notes'] ?? null,
            ]);
            $order->update(['number' => 'ORD-'.$order->created_at->format('Y').'-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT)]);
            $order->items()->createMany($built['items']);

            return $order;
        });

        ActivityLog::record('order.placed', $order, ['total' => $order->total]);
        Mail::to(config('effepi.mail.orders'))->cc($order->customer_email)->send(new OrderPlacedMail($order->load('items')));
        $order->update(['emailed_at' => now()]);

        return redirect()->route('orders.show', $order)->with('status', 'Ordine '.$order->number.' inviato.');
    }

    public function show(Request $request, Order $order)
    {
        abort_unless($order->visibleTo($request->user()), 403);

        return view('orders.show', ['order' => $order->load('items')]);
    }

    /**
     * I dealer per cui l'utente può ordinare: sé stesso (dealer) o il proprio
     * portafoglio (agente), in entrambi i casi solo con anagrafica completa.
     *
     * @return Collection<int, Dealer>
     */
    private function orderableDealers(User $user): Collection
    {
        if ($user->role === User::ROLE_AGENT) {
            return $user->agent?->dealers()->with('agent.user')->whereNotNull('registry_completed_at')->orderBy('business_name')->get() ?? collect();
        }
        $dealer = $user->dealer()->with('agent.user')->first();

        return $dealer && $dealer->canOrder() ? collect([$dealer]) : collect();
    }
}
