<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CommissionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Piano compensi: l'admin varia importi e soglie. Ogni piano vale dal mese indicato in poi; i mesi passati restano allo storico. */
class CommissionPlanController extends Controller
{
    public function index(Request $request)
    {
        $month = $this->month($request->query('month')) ?? now()->startOfMonth();

        return view('manager.production.plan', [
            'month' => $month,
            'plan' => CommissionPlan::forMonth($month),
            'plans' => CommissionPlan::query()->orderByDesc('valid_from')->get(),
            'products' => CommissionPlan::PRODUCTS,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'valid_from' => ['required', 'date_format:Y-m'],
            'note' => ['nullable', 'string', 'max:190'],
            'sdd_bonus' => ['required', 'numeric', 'min:0', 'max:9999'],
            'email_bonus' => ['required', 'numeric', 'min:0', 'max:9999'],
            'sim_scaglione' => ['required', 'in:totale,per_offerta'],
            'fibra_mnp' => ['required', 'in:tipo_attivazione,portabilita'],
            'sim_695_codes' => ['nullable', 'string', 'max:500'],
            'rules' => ['required', 'array', 'min:1'],
            'rules.*.product' => ['required', 'in:'.implode(',', array_keys(CommissionPlan::PRODUCTS))],
            'rules.*.min_qty' => ['required', 'integer', 'min:1', 'max:65000'],
            'rules.*.max_qty' => ['nullable', 'integer', 'min:1', 'max:65000'],
            'rules.*.base_amount' => ['required', 'numeric', 'min:0', 'max:99999'],
            'rules.*.mnp_amount' => ['nullable', 'numeric', 'min:0', 'max:99999'],
        ]);

        foreach (collect($data['rules'])->groupBy('product') as $product => $rules) {
            $sorted = $rules->sortBy('min_qty')->values();
            foreach ($sorted as $i => $r) {
                if (isset($r['max_qty']) && $r['max_qty'] < $r['min_qty']) {
                    return back()->withInput()->withErrors(['rules' => 'Scaglione non valido per '.CommissionPlan::PRODUCTS[$product].': "a" è minore di "da".']);
                }
                $next = $sorted[$i + 1] ?? null;
                if ($next && (! isset($r['max_qty']) || $r['max_qty'] >= $next['min_qty'])) {
                    return back()->withInput()->withErrors(['rules' => 'Scaglioni sovrapposti per '.CommissionPlan::PRODUCTS[$product].'.']);
                }
            }
            if ((int) $sorted->first()['min_qty'] !== 1) {
                return back()->withInput()->withErrors(['rules' => 'Il primo scaglione di '.CommissionPlan::PRODUCTS[$product].' deve partire da 1.']);
            }
        }

        $params = [
            'sdd_bonus' => (float) $data['sdd_bonus'],
            'email_bonus' => (float) $data['email_bonus'],
            'sim_scaglione' => $data['sim_scaglione'],
            'fibra_mnp' => $data['fibra_mnp'],
            'sim_695_codes' => array_values(array_filter(array_map('trim', preg_split('/[\s,;]+/', (string) ($data['sim_695_codes'] ?? ''))))),
            'count_pending' => $request->boolean('count_pending'),
        ];

        $validFrom = Carbon::createFromFormat('!Y-m', $data['valid_from'])->toDateString();
        $plan = DB::transaction(function () use ($data, $params, $validFrom, $request) {
            // Stesso mese di validità: si corregge il piano; altrimenti ne nasce uno nuovo e il precedente resta come storico.
            $plan = CommissionPlan::query()->where('valid_from', $validFrom)->first()
                ?? new CommissionPlan(['valid_from' => $validFrom, 'created_by' => $request->user()->id]);
            $plan->fill(['note' => $data['note'] ?? null, 'params' => $params])->save();
            $plan->rules()->delete();
            foreach ($data['rules'] as $r) {
                $plan->rules()->create([
                    'product' => $r['product'], 'min_qty' => $r['min_qty'], 'max_qty' => $r['max_qty'] ?? null,
                    'base_amount' => $r['base_amount'], 'mnp_amount' => $r['mnp_amount'] ?? 0,
                ]);
            }

            return $plan;
        });
        ActivityLog::record('commission_plan.saved', $plan, ['valid_from' => $validFrom]);

        return redirect()->route('manager.plan', ['month' => $data['valid_from']])
            ->with('status', 'Piano compensi salvato: vale dal '.Carbon::parse($validFrom)->isoFormat('MMMM YYYY').' in poi.');
    }

    private function month(mixed $value): ?Carbon
    {
        return is_string($value) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) ? Carbon::createFromFormat('!Y-m', $value) : null;
    }
}
