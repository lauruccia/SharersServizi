<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Contest;
use App\Services\DateRange;
use App\Services\ProductionReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Extra gara sulla portabilità: la avvia l'admin, la classifica la vede ognuno per i dealer sotto di sé. */
class ContestController extends Controller
{
    public function index(Request $request, ProductionReport $report)
    {
        $user = $request->user();
        $contests = Contest::query()->orderByDesc('active')->orderByDesc('ends_on')->limit(20)->get()->map(function (Contest $c) use ($report, $user) {
            $range = new DateRange('custom', $c->starts_on->copy(), $c->ends_on->copy(), []);
            $ranking = $report->build($user, $range)->map(function ($l) use ($c) {
                $m = $l['metrics'];
                $value = match ($c->metric) {
                    'mnp_sim' => $m['sim_mnp'],
                    'mnp_fibra' => $m['fibra_mnp'],
                    default => $m['sim_mnp'] + $m['fibra_mnp'],
                };

                return ['dealer' => $l['dealer'], 'value' => $value];
            })->where('value', '>', 0)->sortByDesc('value')->values();

            return ['contest' => $c, 'ranking' => $ranking];
        });

        return view('production.contests', ['contests' => $contests, 'metrics' => Contest::METRICS]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'metric' => ['required', 'in:'.implode(',', array_keys(Contest::METRICS))],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'target' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'prize' => ['nullable', 'string', 'max:190'],
        ]);
        $contest = Contest::query()->create($data + ['active' => true, 'created_by' => $request->user()->id]);
        ActivityLog::record('contest.created', $contest, ['name' => $contest->name]);

        return back()->with('status', 'Extra gara "'.$contest->name.'" avviata.');
    }

    public function close(Contest $contest)
    {
        $contest->update(['active' => false, 'ends_on' => max($contest->starts_on, min($contest->ends_on, Carbon::today()))]);
        ActivityLog::record('contest.closed', $contest);

        return back()->with('status', 'Extra gara "'.$contest->name.'" chiusa.');
    }
}
