<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Mail\TemporaryPasswordMail;
use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\AreaHead;
use App\Models\Dealer;
use App\Models\User;
use App\Services\AccountProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/** La rete: dealer, agenti e assegnazione dei dealer agli agenti. */
class NetworkController extends Controller
{
    public function index()
    {
        return view('manager.network', [
            'dealers' => Dealer::query()->with(['user', 'agent.user'])->orderBy('business_name')->get(),
            'agents' => Agent::query()->with(['user', 'areaHead.user'])->withCount('dealers')->get()->sortBy('user.name'),
            'areaHeads' => AreaHead::query()->with('user')->withCount('agents')->get()->sortBy('user.name'),
            'staff' => User::query()->whereIn('role', [User::ROLE_SUPERADMIN, User::ROLE_ADMIN])->orderBy('name')->get(),
            'creatableRoles' => collect(request()->user()->creatableStaffRoles())
                ->mapWithKeys(fn ($role) => [$role => User::ROLE_LABELS[$role]]),
        ]);
    }

    public function assign(Request $request, Dealer $dealer)
    {
        $data = $request->validate(['agent_id' => ['nullable', 'exists:agents,id']]);
        $dealer->update(['agent_id' => $data['agent_id'] ?? null]);
        ActivityLog::record('dealer.assigned', $dealer, ['agent_id' => $dealer->agent_id]);

        return back()->with('status', 'Assegnazione di '.$dealer->business_name.' aggiornata.');
    }

    public function assignAreaHead(Request $request, Agent $agent)
    {
        $data = $request->validate(['area_head_id' => ['nullable', 'exists:area_heads,id']]);
        $agent->update(['area_head_id' => $data['area_head_id'] ?? null]);
        ActivityLog::record('agent.area_head_assigned', $agent, ['area_head_id' => $agent->area_head_id]);

        return back()->with('status', 'Capoarea di '.$agent->user->name.' aggiornato.');
    }

    /** Crea un accesso interno: il superadmin crea admin e capoarea, l'admin solo capoarea. */
    public function storeStaff(Request $request, AccountProvisioner $provisioner)
    {
        $allowed = $request->user()->creatableStaffRoles();
        abort_if($allowed === [], 403);
        $data = $request->validate([
            'role' => ['required', Rule::in($allowed)],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
        ]);

        [$user, $password] = $provisioner->createStaff($data['role'], $data['name'], $data['email']);
        ActivityLog::record('user.staff_created', $user, ['role' => $user->role]);
        Mail::to($user->email)->send(new TemporaryPasswordMail($user, $password));

        return back()->with('status', $user->roleLabel().' creato: password provvisoria inviata a '.$user->email.'.');
    }

    /** Nuova password provvisoria, per chi l'ha persa o non ha mai fatto il primo accesso. */
    public function resendPassword(User $user, AccountProvisioner $provisioner)
    {
        abort_unless(request()->user()->canManageUser($user), 403);
        $password = $provisioner->resetTemporaryPassword($user);
        ActivityLog::record('user.temporary_password', $user);
        Mail::to($user->email)->send(new TemporaryPasswordMail($user, $password));

        return back()->with('status', 'Nuova password provvisoria inviata a '.$user->email.'.');
    }

    public function toggleActive(User $user)
    {
        abort_unless(request()->user()->canManageUser($user), 403);
        $user->update(['active' => ! $user->active]);
        ActivityLog::record($user->active ? 'user.enabled' : 'user.disabled', $user);

        return back()->with('status', 'Accesso di '.$user->name.' '.($user->active ? 'riattivato' : 'sospeso').'.');
    }
}
