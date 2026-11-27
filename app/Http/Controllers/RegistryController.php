<?php

namespace App\Http\Controllers;

use App\Mail\RegistrySubmittedMail;
use App\Models\ActivityLog;
use App\Models\Dealer;
use App\Models\DealerDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/** Nuova anagrafica dealer: la compila sempre l'agente, anche per i dealer arrivati dalla home. */
class RegistryController extends Controller
{
    public function create(Request $request, ?Dealer $dealer = null)
    {
        $dealer = $this->authorizeDealer($request, $dealer);

        return view('registry.form', [
            'dealer' => $dealer ?? new Dealer,
            'existing' => $dealer?->documents->keyBy('type') ?? collect(),
        ]);
    }

    public function store(Request $request, ?Dealer $dealer = null)
    {
        $dealer = $this->authorizeDealer($request, $dealer);
        $agent = $request->user()->agent;
        $existing = $dealer?->documents->keyBy('type') ?? collect();
        $maxKb = config('effepi.upload_max_kb');

        $rules = [
            'business_name' => ['required', 'string', 'max:255'],
            'vat_number' => ['required', 'digits:11'],
            'sdi_code' => ['required', 'alpha_num', 'size:7'],
            'iban' => ['required', 'string', 'regex:/^IT\d{2}[A-Z]\d{10}[0-9A-Z]{12}$/i'],
            'pec' => ['required', 'email', 'max:255'],
            'legal_address' => ['required', 'string', 'max:255'],
            'operating_address' => ['required', 'string', 'max:255'],
            // L'email diventa quella di accesso del dealer: non può essere già di un altro utente.
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($dealer?->user_id)],
            'legal_rep_mobile' => ['required', 'string', 'max:40'],
            'expected_sim' => ['required', 'integer', 'min:0', 'max:100000'],
            'expected_energy' => ['required', 'integer', 'min:0', 'max:100000'],
            'expected_fiber' => ['required', 'integer', 'min:0', 'max:100000'],
            'billing_regime' => ['required', Rule::in(array_keys(Dealer::BILLING_REGIMES))],
            'privacy' => ['accepted'],
        ];
        foreach (array_keys(DealerDocument::TYPES) as $type) {
            // Un documento già caricato non va ricaricato.
            $rules['documents.'.$type] = [$existing->has($type) ? 'nullable' : 'required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$maxKb];
        }
        $data = $request->validate($rules, [
            'iban.regex' => 'L\'IBAN deve essere italiano, di 27 caratteri, senza spazi.',
        ], collect(DealerDocument::TYPES)->mapWithKeys(fn ($label, $type) => ['documents.'.$type => mb_strtolower($label)])->all());

        $dealer = DB::transaction(function () use ($request, $dealer, $agent, $data) {
            $dealer ??= new Dealer(['agent_id' => $agent->id]);
            $dealer->fill([
                'business_name' => $data['business_name'],
                'vat_number' => $data['vat_number'],
                'sdi_code' => strtoupper($data['sdi_code']),
                'iban' => strtoupper($data['iban']),
                'pec' => $data['pec'],
                'legal_address' => $data['legal_address'],
                'operating_address' => $data['operating_address'],
                'email' => $data['email'],
                'legal_rep_mobile' => $data['legal_rep_mobile'],
                'expected_sim' => $data['expected_sim'],
                'expected_energy' => $data['expected_energy'],
                'expected_fiber' => $data['expected_fiber'],
                'billing_regime' => $data['billing_regime'],
                'privacy_confirmed_at' => now(),
                'registry_submitted_at' => now(),
                'registry_submitted_by' => $request->user()->id,
            ])->save();

            foreach ($request->file('documents', []) as $type => $file) {
                $old = $dealer->documents()->where('type', $type)->get();
                // Archivio privato: i documenti non stanno mai nella cartella pubblica.
                $dealer->documents()->create([
                    'type' => $type,
                    'path' => $file->store('dealer-documents/'.$dealer->id, 'local'),
                    'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
                    'uploaded_by' => $request->user()->id,
                ]);
                $old->each->delete();
            }

            return $dealer;
        });

        ActivityLog::record('registry.submitted', $dealer);
        Mail::to(config('effepi.mail.affiliations'))->send(new RegistrySubmittedMail($dealer->load('agent.user')));

        return redirect()->route('agent.area')->with('status', 'Anagrafica di '.$dealer->business_name.' inviata: il back office la verifica e attiva il dealer.');
    }

    /** L'agente lavora solo sui dealer del suo portafoglio, e solo finché l'anagrafica non è attiva. */
    private function authorizeDealer(Request $request, ?Dealer $dealer): ?Dealer
    {
        if ($dealer === null) {
            return null;
        }
        abort_unless($dealer->agent_id === $request->user()->agent?->id, 403);
        abort_if($dealer->registry_completed_at !== null, 409, 'Anagrafica già verificata dal back office.');

        return $dealer->load('documents');
    }
}
