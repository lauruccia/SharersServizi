<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);
        $field = str_contains($data['login'], '@') ? 'email' : 'username';

        if (! Auth::attempt([$field => $data['login'], 'password' => $data['password'], 'active' => true], $request->boolean('remember'))) {
            return back()->withErrors(['login' => 'Nome utente o password non corretti.'])->onlyInput('login');
        }
        $request->session()->regenerate();
        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->save();

        return $user->must_change_password
            ? redirect()->route('password.change')
            : redirect()->intended(route($user->homeRoute()));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /** Cambio password: obbligatorio al primo accesso con la password provvisoria. */
    public function showChange()
    {
        return view('auth.change-password');
    }

    public function change(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', $this->passwordRule()],
        ]);
        $user->forceFill(['password' => $data['password'], 'must_change_password' => false])->save();
        ActivityLog::record('password.changed', $user);

        return redirect()->route($user->homeRoute())->with('status', 'Password aggiornata.');
    }

    public function showForgot()
    {
        return view('auth.forgot-password');
    }

    public function sendReset(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink(['email' => $request->input('email'), 'active' => true]);

        // Stessa risposta in ogni caso, per non rivelare quali email sono registrate.
        return back()->with('status', 'Se l\'indirizzo è registrato, riceverai un\'email con il link per reimpostare la password.');
    }

    public function showReset(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', $this->passwordRule()],
        ]);
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'must_change_password' => false,
                    'remember_token' => Str::random(60),
                ])->save();
                event(new PasswordReset($user));
            },
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Password reimpostata: ora puoi accedere.')
            : back()->withErrors(['email' => 'Il link non è valido o è scaduto. Richiedine uno nuovo.']);
    }

    private function passwordRule(): PasswordRule
    {
        return PasswordRule::min(10)->letters()->numbers();
    }
}
