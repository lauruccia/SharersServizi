<?php

namespace App\Http\Controllers;

use App\Models\Dealer;
use App\Models\ProductionRow;
use App\Services\DateRange;
use App\Services\ProductionReport;
use Illuminate\Http\Request;

/** Conteggi di attivazioni e compensi: ognuno vede i dealer che stanno sotto di lui (scope visibleTo). */
class ProductionController extends Controller
{
    public function index(Request $request, ProductionReport $report)
    {
        $user = $request->user();
        $range = DateRange::fromRequest($request);
        $by = $request->query('group', 'dealer');
        $by = array_key_exists($by, ProductionReport::GROUPS) ? $by : 'dealer';
        // Il dealer vede solo se stesso: raggruppare per altro non ha senso.
        if ($user->role === 'dealer') {
            $by = 'dealer';
        }

        $lines = $report->build($user, $range);

        return view('production.index', [
            'range' => $range,
            'by' => $by,
            'groups' => $user->role === 'dealer' ? ['dealer' => 'Dealer'] : ProductionReport::GROUPS,
            'rows' => $report->groupBy($lines, $by),
            'totals' => $report->totals($lines),
        ]);
    }

    public function dealer(Request $request, Dealer $dealer, ProductionReport $report)
    {
        $user = $request->user();
        abort_unless(Dealer::query()->visibleTo($user)->whereKey($dealer->id)->exists(), 404);
        $range = DateRange::fromRequest($request);

        $line = $report->build($user, $range)->firstWhere(fn ($l) => $l['dealer']->id === $dealer->id);
        $rows = ProductionRow::query()->where('dealer_id', $dealer->id)
            ->whereBetween('signed_at', [$range->from->toDateString(), $range->to->toDateString()])
            ->orderByDesc('signed_at')->orderBy('service_type')->get();

        return view('production.dealer', [
            'dealer' => $dealer->load(['agent.user', 'area', 'ambassador']),
            'range' => $range,
            'line' => $line,
            'rows' => $rows,
            'adjustments' => \App\Models\CommissionAdjustment::query()->with('author')->where('dealer_id', $dealer->id)
                ->whereBetween('month', [$range->from->copy()->startOfMonth()->toDateString(), $range->to->toDateString()])->orderBy('month')->get(),
        ]);
    }
}
