<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Ogni area è accessibile solo al proprio ruolo. */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user || ! $user->active || ! in_array($user->role, $roles, true)) {
            abort(403, 'Non hai accesso a questa area.');
        }

        return $next($request);
    }
}
