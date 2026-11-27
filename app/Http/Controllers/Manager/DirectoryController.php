<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Mail\TemporaryPasswordMail;
use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\Ambassador;
use App\Models\Area;
use App\Models\AreaHead;
use App\Models\Dealer;
use App\Models\User;
use App\Services\AccountProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/** Elenchi della rete gestiti dall'admin: dealer, aree, ambassador, agenti, capoarea (inserimento, modifica, eliminazione). */
class DirectoryController extends Controller
{
    public const TABS = [
        'dealer' => 'Dealer',
        'aree' => 'Aree',
        'ambassador' => 'Ambassador',
        'agenti' => 'Agenti',
        'capoarea' => 'Capoarea',
    ];

    public function index(Request $request, string $tab = 'dealer')
    {
        abort_unless(isset(self::TABS[$tab]), 404);
        $q = trim((string) $request->query('q'));

        $data = ['tab' => $tab, 'tabs' => self::TABS, 'q' => $q,
            'areas' => Area::query()->orderBy('name')->get(),
            'ambassadors' => Ambassador::query()->orderBy('name')->get(),
            'agents' => Agent::query()->with('user')->get()->sortBy('user.name'),
            'areaHeads' => AreaHead::query()->with('user')->get()->sortBy('user.name')];

        $data['items'] = match ($tab) {
            'dealer' => Dealer::query()->with(['agent.user', 'area', 'ambassador'])
                ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('business_name', 'like', "%$q%")->orWhere('external_code', 'like', "%$q%")))
                ->orderBy('business_name')->paginate(50)->withQueryString(),
            'aree' => Area::query()->withCount(['dealers', 'ambassadors'])->orderBy('name')->get(),
            'ambassador' => Ambassador::query()->with('area')->withCount('dealers')->orderBy('name')->get(),
            'agenti' => Agent::query()->with(['user', 'areaHead.user'])->withCount('dealers')->get()->sortBy('user.name'),
            'capoarea' => AreaHead::query()->with('user')->withCount('agents')->get()->sortBy('user.name'),
        };

        return view('manager.directory', $data);
    }

    // --- Dealer ------------------------------------------------------------

    public function storeDealer(Request $request)
    {
        $dealer = Dealer::query()->create($this->dealerData($request));
        ActivityLog::record('dealer.created', $dealer);

        return back()->with('status', 'Dealer "'.$dealer->business_name.'" inserito.');
    }

    public function updateDealer(Request $request, Dealer $dealer)
    {
        $dealer->update($this->dealerData($request, $dealer));
        ActivityLog::record('dealer.updated', $dealer);

        return back()->with('status', 'Dealer "'.$dealer->business_name.'" aggiornato.');
    }

    public function destroyDealer(Dealer $dealer)
    {
        if ($dealer->user_id || $dealer->orders()->exists()) {
            return back()->withErrors(['delete' => 'Questo dealer ha un accesso o degli ordini: non si elimina. Sospendi l\'accesso dalla pagina Rete.']);
        }
        ActivityLog::record('dealer.deleted', $dealer, ['name' => $dealer->business_name]);
        $dealer->delete();

        return back()->with('status', 'Dealer eliminato.');
    }

    private function dealerData(Request $request, ?Dealer $dealer = null): array
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:190'],
            'external_code' => ['nullable', 'string', 'max:60', Rule::unique('dealers', 'external_code')->ignore($dealer?->id)],
            'area_id' => ['nullable', 'exists:areas,id'],
            'ambassador_id' => ['nullable', 'exists:ambassadors,id'],
            'agent_id' => ['nullable', 'exists:agents,id'],
            'region' => ['nullable', 'string', 'max:60'],
            'municipality' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);

        return array_map(fn ($v) => $v === '' ? null : $v, $data);
    }

    // --- Aree e ambassador -------------------------------------------------

    public function storeArea(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:190', 'unique:areas,name']]);
        $area = Area::query()->create($data);
        ActivityLog::record('area.created', $area);

        return back()->with('status', 'Area "'.$area->name.'" creata.');
    }

    public function updateArea(Request $request, Area $area)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:190', Rule::unique('areas', 'name')->ignore($area->id)]]);
        $area->update($data);

        return back()->with('status', 'Area aggiornata.');
    }

    public function destroyArea(Area $area)
    {
        ActivityLog::record('area.deleted', $area, ['name' => $area->name]);
        $area->delete(); // dealer e ambassador restano, senza area

        return back()->with('status', 'Area eliminata: i dealer collegati restano senza area.');
    }

    public function storeAmbassador(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:190', 'unique:ambassadors,name'], 'area_id' => ['nullable', 'exists:areas,id']]);
        $ambassador = Ambassador::query()->create($data);
        ActivityLog::record('ambassador.created', $ambassador);

        return back()->with('status', 'Ambassador "'.$ambassador->name.'" creato.');
    }

    public function updateAmbassador(Request $request, Ambassador $ambassador)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:190', Rule::unique('ambassadors', 'name')->ignore($ambassador->id)], 'area_id' => ['nullable', 'exists:areas,id']]);
        $ambassador->update(['name' => $data['name'], 'area_id' => $data['area_id'] ?? null]);

        return back()->with('status', 'Ambassador aggiornato.');
    }

    public function destroyAmbassador(Ambassador $ambassador)
    {
        ActivityLog::record('ambassador.deleted', $ambassador, ['name' => $ambassador->name]);
        $ambassador->delete();

        return back()->with('status', 'Ambassador eliminato: i dealer collegati restano senza ambassador.');
    }

    // --- Agenti e capoarea (hanno un accesso: si creano con password provvisoria) -----

    public function storeAgent(Request $request, AccountProvisioner $provisioner)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'region' => ['nullable', 'string', 'max:60'],
            'area_head_id' => ['nullable', 'exists:area_heads,id'],
        ]);

        [$user, $password] = DB::transaction(function () use ($provisioner, $data) {
            [$user, $password] = $provisioner->createStaff(User::ROLE_AGENT, $data['name'], $data['email']);
            Agent::query()->create(['user_id' => $user->id, 'phone' => $data['phone'] ?? null, 'region' => $data['region'] ?? null, 'area_head_id' => $data['area_head_id'] ?? null]);

            return [$user, $password];
        });
        ActivityLog::record('user.agent_created', $user);
        Mail::to($user->email)->send(new TemporaryPasswordMail($user, $password));

        return back()->with('status', 'Agente creato: password provvisoria inviata a '.$user->email.'.');
    }

    public function updateAgent(Request $request, Agent $agent)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($agent->user_id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'region' => ['nullable', 'string', 'max:60'],
            'area_head_id' => ['nullable', 'exists:area_heads,id'],
        ]);
        DB::transaction(function () use ($agent, $data) {
            $agent->user->update(['name' => $data['name'], 'email' => $data['email']]);
            $agent->update(['phone' => $data['phone'] ?? null, 'region' => $data['region'] ?? null, 'area_head_id' => $data['area_head_id'] ?? null]);
        });
        ActivityLog::record('agent.updated', $agent);

        return back()->with('status', 'Agente aggiornato.');
    }

    public function updateAreaHead(Request $request, AreaHead $areaHead)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($areaHead->user_id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'region' => ['nullable', 'string', 'max:60'],
        ]);
        DB::transaction(function () use ($areaHead, $data) {
            $areaHead->user->update(['name' => $data['name'], 'email' => $data['email']]);
            $areaHead->update(['phone' => $data['phone'] ?? null, 'region' => $data['region'] ?? null]);
        });
        ActivityLog::record('area_head.updated', $areaHead);

        return back()->with('status', 'Capoarea aggiornato.');
    }
}
