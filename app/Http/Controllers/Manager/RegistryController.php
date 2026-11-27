<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Mail\CredentialsMail;
use App\Models\ActivityLog;
use App\Models\Dealer;
use App\Models\DealerDocument;
use App\Models\User;
use App\Services\AccountProvisioner;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/** Verifica delle anagrafiche inviate dagli agenti e attivazione dei dealer. */
class RegistryController extends Controller
{
    public function index()
    {
        $with = ['agent.user', 'user', 'documents'];

        return view('manager.registries', [
            'pending' => Dealer::query()->with($with)->whereNotNull('registry_submitted_at')->whereNull('registry_completed_at')->orderBy('registry_submitted_at')->get(),
            'completed' => Dealer::query()->with($with)->whereNotNull('registry_completed_at')->latest('registry_completed_at')->limit(50)->get(),
        ]);
    }

    public function document(DealerDocument $document)
    {
        abort_unless(Storage::disk('local')->exists($document->path), 404);
        ActivityLog::record('registry.document_opened', $document->dealer, ['type' => $document->type]);

        return Storage::disk('local')->response($document->path, $document->original_name);
    }

    public function activate(Dealer $dealer, AccountProvisioner $provisioner)
    {
        abort_if($dealer->registry_submitted_at === null, 409, 'Anagrafica non ancora inviata.');
        abort_if($dealer->registry_completed_at !== null, 409, 'Dealer già attivo.');

        $message = $dealer->business_name.' attivato: ora può ordinare.';
        if (! $dealer->user_id) {
            if (User::query()->where('email', $dealer->email)->exists()) {
                return back()->withErrors(['activate' => 'Esiste già un accesso con l\'email '.$dealer->email.': correggi l\'anagrafica prima di attivare.']);
            }
            // Dealer inserito direttamente dall'agente: gli serve un accesso.
            [$user, $password] = $provisioner->createForDealer($dealer);
            Mail::to($user->email)->send(new CredentialsMail($user, $password));
            $message .= ' Credenziali inviate a '.$user->email.' (nome utente: '.$user->username.').';
        }
        $dealer->update(['registry_completed_at' => now()]);
        ActivityLog::record('registry.activated', $dealer);

        return back()->with('status', $message);
    }
}
