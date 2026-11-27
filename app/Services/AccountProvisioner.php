<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AreaHead;
use App\Models\Dealer;
use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Crea l'accesso di un candidato approvato e genera le password provvisorie. */
class AccountProvisioner
{
    /**
     * @return array{0: User, 1: string} utente e password provvisoria in chiaro (da inviare una sola volta)
     */
    public function createFromRequest(RegistrationRequest $request): array
    {
        return DB::transaction(function () use ($request) {
            $password = $this->temporaryPassword();
            $user = User::query()->create([
                'name' => $request->displayName(),
                'username' => $this->uniqueUsername($request->displayName()),
                'email' => $request->email,
                'password' => $password,
                'role' => $request->isDealer() ? User::ROLE_DEALER : User::ROLE_AGENT,
                'must_change_password' => true,
                'active' => true,
            ]);

            if ($request->isDealer()) {
                Dealer::query()->create([
                    'user_id' => $user->id,
                    'business_name' => $request->business_name ?: $request->name,
                    'contact_name' => $request->name,
                    'phone' => $request->phone,
                    'email' => $request->email,
                    'activity_type' => $request->activity_type,
                    'region' => $request->region,
                    'municipality' => $request->municipality,
                ]);
            } else {
                Agent::query()->create([
                    'user_id' => $user->id,
                    'phone' => $request->phone,
                    'region' => $request->region,
                    'zones' => $request->zones,
                    'experience' => $request->experience,
                ]);
            }

            return [$user, $password];
        });
    }

    /**
     * Accesso per un dealer inserito dall'agente con la nuova anagrafica.
     *
     * @return array{0: User, 1: string}
     */
    public function createForDealer(Dealer $dealer): array
    {
        return DB::transaction(function () use ($dealer) {
            $password = $this->temporaryPassword();
            $user = User::query()->create([
                'name' => $dealer->business_name,
                'username' => $this->uniqueUsername($dealer->business_name),
                'email' => $dealer->email,
                'password' => $password,
                'role' => User::ROLE_DEALER,
                'must_change_password' => true,
                'active' => true,
            ]);
            $dealer->update(['user_id' => $user->id]);

            return [$user, $password];
        });
    }

    /**
     * Accesso interno (admin o capoarea), creato da chi sta sopra nella gerarchia.
     *
     * @return array{0: User, 1: string}
     */
    public function createStaff(string $role, string $name, string $email): array
    {
        return DB::transaction(function () use ($role, $name, $email) {
            $password = $this->temporaryPassword();
            $user = User::query()->create([
                'name' => $name,
                'username' => $this->uniqueUsername($name),
                'email' => $email,
                'password' => $password,
                'role' => $role,
                'must_change_password' => true,
                'active' => true,
            ]);
            if ($role === User::ROLE_AREA_HEAD) {
                AreaHead::query()->create(['user_id' => $user->id]);
            }

            return [$user, $password];
        });
    }

    /** Nuova password provvisoria per un utente esistente: dovrà cambiarla al primo accesso. */
    public function resetTemporaryPassword(User $user): string
    {
        $password = $this->temporaryPassword();
        $user->forceFill(['password' => $password, 'must_change_password' => true])->save();

        return $password;
    }

    public function uniqueUsername(string $name): string
    {
        $base = Str::of($name)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '.')->trim('.')->limit(30, '')->trim('.')->value() ?: 'utente';
        $username = $base;
        $n = 1;
        while (User::query()->where('username', $username)->exists()) {
            $username = $base.(++$n);
        }

        return $username;
    }

    /** 12 caratteri senza simboli ambigui (0/O, 1/l/I). */
    private function temporaryPassword(): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $out = '';
        for ($i = 0; $i < 12; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $out;
    }
}
