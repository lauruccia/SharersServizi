<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CommissionAdjustment;
use App\Models\Dealer;
use App\Models\ProductionRow;
use App\Services\ProductionImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Correzioni manuali dell'admin sui conteggi: modifica di qualsiasi dato del contratto, contratti inseriti a mano,
 * eliminazione e rettifiche ai compensi. Le righe corrette a mano sono "bloccate": un nuovo import non le sovrascrive.
 */
class ProductionRowController extends Controller
{
    public const SERVICES = [
        'MOBILE_C' => 'SIM consumer', 'MOBILE' => 'SIM business',
        'ENERGIA_C' => 'Luce consumer', 'ENERGIA' => 'Luce business',
        'GAS_C' => 'Gas consumer', 'GAS' => 'Gas business',
        'ADSL_C' => 'Fibra consumer', 'ADSL' => 'Fibra business',
        'VOIP_C' => 'VoIP consumer (non conteggiato)', 'VOIP' => 'VoIP business (non conteggiato)',
        'H24_C' => 'H24', 'TUTELALEGALE_C' => 'Tutela legale',
        'VIDEOCONSULTO_MEDICO_C' => 'Videoconsulto medico', 'VIDEOCONSULTO_VETERINARIO_C' => 'Videoconsulto veterinario',
    ];

    public function create(Request $request)
    {
        $row = new ProductionRow(['signed_at' => now()->toDateString(), 'service_raw' => 'MOBILE_C', 'status' => ProductionImporter::STATUS_OK,
            'sims_sold' => 1, 'sims_portability' => 0, 'dealer_id' => $request->integer('dealer') ?: null]);

        return view('manager.production.row-form', $this->formData($row, false));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $dealer = Dealer::query()->findOrFail($data['dealer_id']);
        $row = new ProductionRow($this->attributes($data) + [
            'contract_id' => 'MAN-'.Str::upper(Str::random(8)),
            'external_dealer_id' => $dealer->external_code ?: 'manuale',
            'dealer_name' => $dealer->business_name,
            'is_manual' => true,
            'locked' => true,
        ]);
        $row->save();
        ActivityLog::record('production.row_created', $row, ['contract' => $row->contract_id, 'dealer' => $dealer->business_name]);

        return $this->back($row, 'Contratto inserito a mano ('.$row->contract_id.').');
    }

    public function edit(ProductionRow $row)
    {
        return view('manager.production.row-form', $this->formData($row, true));
    }

    public function update(Request $request, ProductionRow $row)
    {
        $before = $row->only(['dealer_id', 'service_raw', 'signed_at', 'status', 'sims_sold', 'sims_portability', 'force_count', 'fibra_mnp_force', 'payment_method', 'invoice_delivery']);
        $data = $this->validated($request);
        $row->fill($this->attributes($data))->forceFill(['locked' => true, 'dealer_name' => Dealer::query()->find($data['dealer_id'])?->business_name ?? $row->dealer_name])->save();
        ActivityLog::record('production.row_edited', $row, ['contract' => $row->contract_id, 'before' => $before]);

        return $this->back($row, 'Contratto '.$row->contract_id.' corretto: il prossimo import non lo sovrascrive.');
    }

    public function destroy(ProductionRow $row)
    {
        ActivityLog::record('production.row_deleted', $row, ['contract' => $row->contract_id, 'manual' => $row->is_manual]);
        if ($row->is_manual) {
            $row->delete();

            return $this->back($row, 'Contratto manuale eliminato.');
        }
        // Una riga importata tornerebbe con il prossimo import: la si esclude e la si blocca.
        $row->forceFill(['force_count' => false, 'locked' => true, 'edit_note' => 'Eliminata a mano'])->save();

        return $this->back($row, 'Contratto '.$row->contract_id.' escluso dai conteggi (resta nello storico e un nuovo import non lo riporta).');
    }

    // --- Rettifiche ai compensi ------------------------------------------------

    public function storeAdjustment(Request $request)
    {
        $data = $request->validate([
            'dealer_id' => ['required', 'exists:dealers,id'],
            'month' => ['required', 'date_format:Y-m'],
            'amount' => ['required', 'numeric', 'between:-99999,99999', 'not_in:0'],
            'note' => ['required', 'string', 'max:190'],
        ], ['amount.not_in' => 'L\'importo non può essere zero.']);
        $month = Carbon::createFromFormat('!Y-m', $data['month']);
        $adj = CommissionAdjustment::query()->create([
            'dealer_id' => $data['dealer_id'], 'month' => $month->toDateString(),
            'amount_cents' => (int) round($data['amount'] * 100), 'note' => $data['note'], 'created_by' => $request->user()->id,
        ]);
        ActivityLog::record('commission.adjusted', $adj, ['dealer_id' => $adj->dealer_id, 'cents' => $adj->amount_cents]);

        return redirect()->route('production.dealer', ['dealer' => $adj->dealer_id, 'period' => 'month', 'month' => $data['month']])->with('status', 'Rettifica registrata.');
    }

    public function destroyAdjustment(CommissionAdjustment $adjustment)
    {
        ActivityLog::record('commission.adjustment_removed', $adjustment, ['dealer_id' => $adjustment->dealer_id, 'cents' => $adjustment->amount_cents]);
        $adjustment->delete();

        return redirect()->route('production.dealer', ['dealer' => $adjustment->dealer_id, 'period' => 'month', 'month' => $adjustment->month->format('Y-m')])->with('status', 'Rettifica rimossa.');
    }

    // --- Interni -----------------------------------------------------------

    private function formData(ProductionRow $row, bool $exists): array
    {
        return ['row' => $row, 'exists' => $exists, 'services' => self::SERVICES, 'dealers' => Dealer::query()->orderBy('business_name')->get(['id', 'business_name', 'external_code'])];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'dealer_id' => ['required', 'exists:dealers,id'],
            'service_raw' => ['required', 'in:'.implode(',', array_keys(self::SERVICES))],
            'signed_at' => ['required', 'date'],
            'customer_name' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', 'string', 'max:120'],
            'discard_reason' => ['nullable', 'string', 'max:190'],
            'sims_sold' => ['nullable', 'integer', 'min:0', 'max:999'],
            'sims_portability' => ['nullable', 'integer', 'min:0', 'max:999'],
            'offer_code' => ['nullable', 'string', 'max:40'],
            'activation_type' => ['nullable', 'string', 'max:40'],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'invoice_delivery' => ['nullable', 'string', 'max:40'],
            'fibra_mnp' => ['required', 'in:auto,si,no'],
            'force' => ['required', 'in:auto,count,exclude'],
            'edit_note' => ['nullable', 'string', 'max:190'],
        ]);
    }

    /** Dati validati → colonne; tipo servizio, mese ed esito automatico si ricalcolano come all'import. */
    private function attributes(array $d): array
    {
        [$type, $consumer] = ProductionImporter::classifyService($d['service_raw']);
        $signed = Carbon::parse($d['signed_at']);
        $sims = $type === 'sim' ? (int) ($d['sims_sold'] ?? 0) : 0;
        $portab = (int) ($d['sims_portability'] ?? 0);
        $blank = fn ($v) => ($v === null || $v === '') ? null : $v;

        return [
            'dealer_id' => $d['dealer_id'],
            'service_raw' => $d['service_raw'], 'service_type' => $type, 'is_consumer' => $consumer,
            'signed_at' => $signed->toDateString(), 'signed_month' => $signed->copy()->startOfMonth()->toDateString(),
            'customer_name' => $blank($d['customer_name'] ?? null),
            'status' => $blank($d['status'] ?? null), 'discard_reason' => $blank($d['discard_reason'] ?? null),
            'outcome' => ProductionImporter::outcome($blank($d['status'] ?? null), $blank($d['discard_reason'] ?? null)),
            'sims_sold' => $sims, 'sims_portability' => $type === 'sim' ? min($portab, $sims) : $portab,
            'offer_code' => $blank($d['offer_code'] ?? null), 'activation_type' => $blank($d['activation_type'] ?? null),
            'payment_method' => $blank($d['payment_method'] ?? null), 'invoice_delivery' => $blank($d['invoice_delivery'] ?? null),
            'fibra_mnp_force' => match ($d['fibra_mnp']) { 'si' => true, 'no' => false, default => null },
            'force_count' => match ($d['force']) { 'count' => true, 'exclude' => false, default => null },
            'edit_note' => $blank($d['edit_note'] ?? null),
        ];
    }

    private function back(ProductionRow $row, string $message)
    {
        return redirect()->route('production.dealer', ['dealer' => $row->dealer_id, 'period' => 'month', 'month' => $row->signed_month->format('Y-m')])->with('status', $message);
    }
}
