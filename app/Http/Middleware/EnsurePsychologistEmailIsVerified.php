<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePsychologistEmailIsVerified
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->requiresEmailVerification()) {
            return response()->json([
                'message' => 'Valide seu e-mail para continuar.',
                'requires_email_verification' => true,
            ], 403);
        }

        return $next($request);
    }
}
