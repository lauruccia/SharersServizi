<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Dealer;
use App\Models\Order;
use App\Models\Promo;
use App\Models\ShowcaseProduct;
use Illuminate\Http\Request;

/** Aree riservate di dealer e agente: cinque schede ciascuna. */
class AreaController extends Controller
{
    public const TABS = [
        'optima' => 'Optima',
        'sharers' => 'Sharers',
        'rapicom' => 'Rapicom',
        'altri' => 'Altri operatori mobile',
        'promo' => 'Promo Dedicate',
    ];

    public function dealer(Request $request)
    {
        $dealer = $request->user()->dealer()->with('agent.user')->firstOrFail();

        return view('area.dealer', $this->shared($request) + ['dealer' => $dealer]);
    }

    public function agent(Request $request)
    {
        // Solo il proprio portafoglio: i dealer si leggono sempre dalla relazione dell'agente.
        $agent = $request->user()->agent()->firstOrFail();

        return view('area.agent', $this->shared($request) + [
            'agent' => $agent,
            'dealers' => $agent->dealers()->orderBy('business_name')->get(),
        ]);
    }

    /** Capoarea: i suoi agenti, i dealer sotto di loro e gli ordini, in sola lettura. */
    public function areaHead(Request $request)
    {
        $user = $request->user();
        $areaHead = $user->areaHead()->firstOrFail();
        $agents = Agent::query()->visibleTo($user)->with('user')->withCount('dealers')->get()->sortBy('user.name');

        return view('area.areahead', [
            'areaHead' => $areaHead,
            'agents' => $agents,
            'dealers' => Dealer::query()->visibleTo($user)->with(['agent.user', 'user'])->orderBy('business_name')->get(),
            'ordersCount' => Order::query()->visibleTo($user)->count(),
        ]);
    }

    /** Pagina provvisoria per le funzioni non ancora costruite (invio contratti). */
    public function comingSoon(Request $request, string $feature)
    {
        $titles = ['invia-contratto' => 'Invia contratto al back office'];
        abort_unless(isset($titles[$feature]), 404);

        return view('area.coming-soon', ['title' => $titles[$feature]]);
    }

    private function shared(Request $request): array
    {
        $tab = array_key_exists($request->query('scheda'), self::TABS) ? $request->query('scheda') : 'optima';

        return [
            'tab' => $tab,
            'tabs' => self::TABS,
            'promos' => $tab === 'promo' ? Promo::query()->active()->orderBy('ends_at')->get() : collect(),
            'showcase' => $tab === 'promo'
                ? ShowcaseProduct::query()->whereNotNull('name')->whereNotNull('url')->orderBy('position')->get()
                : collect(),
        ];
    }
}
