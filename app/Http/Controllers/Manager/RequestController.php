<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Mail\CredentialsMail;
use App\Mail\InfoRequestedMail;
use App\Models\ActivityLog;
use App\Models\RegistrationRequest;
use App\Models\User;
use App\Services\AccountProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->query('stato'), [RegistrationRequest::APPROVED, RegistrationRequest::REJECTED], true)
            ? $request->query('stato')
            : RegistrationRequest::PENDING;

        return view('manager.requests', [
            'status' => $status,
            'requests' => RegistrationRequest::query()->with(['manager', 'decider', 'messages'])
                ->where('status', $status)->orderBy('meeting_at')->get(),
            'counts' => RegistrationRequest::query()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status'),
        ]);
    }

    public function approve(RegistrationRequest $registration, AccountProvisioner $provisioner)
    {
        abort_unless($registration->status === RegistrationRequest::PENDING, 409, 'Richiesta già lavorata.');
        if (User::query()->where('email', $registration->email)->exists()) {
            return back()->withErrors(['approve' => 'Esiste già un accesso con l\'email '.$registration->email.'.']);
        }

        [$user, $password] = $provisioner->createFromRequest($registration);
        $registration->update([
            'status' => RegistrationRequest::APPROVED,
            'decided_by' => auth()->id(),
            'decided_at' => now(),
            'user_id' => $user->id,
        ]);
        ActivityLog::record('registration.approved', $registration, ['user_id' => $user->id]);
        Mail::to($user->email)->send(new CredentialsMail($user, $password));

        return back()->with('status', 'Richiesta approvata: credenziali inviate a '.$user->email.' (nome utente: '.$user->username.').');
    }

    public function askInfo(Request $request, RegistrationRequest $registration)
    {
        abort_unless($registration->status === RegistrationRequest::PENDING, 409, 'Richiesta già lavorata.');
        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);

        $registration->messages()->create(['manager_id' => auth()->id(), 'body' => $data['body']]);
        ActivityLog::record('registration.info_requested', $registration);
        Mail::to($registration->email)->send(new InfoRequestedMail($registration, $data['body']));

        return back()->with('status', 'Richiesta di informazioni inviata a '.$registration->email.'.');
    }

    public function reject(RegistrationRequest $registration)
    {
        abort_unless($registration->status === RegistrationRequest::PENDING, 409, 'Richiesta già lavorata.');
        $registration->update([
            'status' => RegistrationRequest::REJECTED,
            'decided_by' => auth()->id(),
            'decided_at' => now(),
        ]);
        ActivityLog::record('registration.rejected', $registration);

        return back()->with('status', 'Richiesta rifiutata.');
    }
}
