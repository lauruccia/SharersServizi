<?php

namespace App\Models;

use App\Mail\ResetPasswordMail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // Dal livello più alto al più basso: superadmin > admin > capoarea > agente > dealer.
    public const ROLE_SUPERADMIN = 'superadmin';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_AREA_HEAD = 'capoarea';

    public const ROLE_AGENT = 'agent';

    public const ROLE_DEALER = 'dealer';

    public const ROLE_LABELS = [
        self::ROLE_SUPERADMIN => 'Superadmin',
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_AREA_HEAD => 'Capoarea',
        self::ROLE_AGENT => 'Agente',
        self::ROLE_DEALER => 'Dealer',
    ];

    protected $fillable = ['name', 'username', 'email', 'password', 'role', 'must_change_password', 'active'];

    protected $attributes = ['active' => true, 'must_change_password' => false];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function dealer(): HasOne
    {
        return $this->hasOne(Dealer::class);
    }

    public function agent(): HasOne
    {
        return $this->hasOne(Agent::class);
    }

    public function areaHead(): HasOne
    {
        return $this->hasOne(AreaHead::class);
    }

    public function roleLabel(): string
    {
        return self::ROLE_LABELS[$this->role] ?? $this->role;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    /** Back office: admin e superadmin vedono e gestiscono tutta la rete. */
    public function isBackOffice(): bool
    {
        return in_array($this->role, [self::ROLE_SUPERADMIN, self::ROLE_ADMIN], true);
    }

    /**
     * Può agire su un altro account (password, sospensione, assegnazioni)?
     * L'admin controlla tutto tranne i superadmin; nessuno agisce su se stesso (niente blocco accidentale).
     */
    public function canManageUser(User $target): bool
    {
        if ($target->is($this) || ! $this->isBackOffice()) {
            return false;
        }

        return $this->isSuperAdmin() || ! $target->isSuperAdmin();
    }

    /** Ruoli interni che questo utente può creare: l'admin solo capoarea, il superadmin anche admin. */
    public function creatableStaffRoles(): array
    {
        return match ($this->role) {
            self::ROLE_SUPERADMIN => [self::ROLE_ADMIN, self::ROLE_AREA_HEAD],
            self::ROLE_ADMIN => [self::ROLE_AREA_HEAD],
            default => [],
        };
    }

    /** Nome della route dell'area riservata del ruolo. */
    public function homeRoute(): string
    {
        return match ($this->role) {
            self::ROLE_SUPERADMIN, self::ROLE_ADMIN => 'manager.requests',
            self::ROLE_AREA_HEAD => 'areahead.area',
            self::ROLE_AGENT => 'agent.area',
            default => 'dealer.area',
        };
    }

    public function sendPasswordResetNotification($token): void
    {
        Mail::to($this->email)->send(new ResetPasswordMail($this, $token));
    }
}
