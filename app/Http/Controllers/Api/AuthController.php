<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Services\EmailVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\User;

class AuthController extends Controller
{

    public function forgotPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if ($user) {
            $token = Str::random(64);
            $expiresAt = now()->addHour();

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now(),
                ]
            );

            $resetUrl = url('/reset-password?' . http_build_query([
                'token' => $token,
                'email' => $user->email,
            ]));

            Mail::to($user->email)->send(new PasswordResetMail($user, $resetUrl, $expiresAt));
        }

        return response()->json([
            'message' => 'Se o e-mail estiver cadastrado, enviaremos um link para redefinir sua senha.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $reset = DB::table('password_reset_tokens')->where('email', $data['email'])->first();

        if (!$reset || Carbon::parse($reset->created_at)->addHour()->isPast() || !Hash::check($data['token'], $reset->token)) {
            return response()->json([
                'message' => 'Link de redefinição inválido ou expirado.',
            ], 422);
        }

        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'Link de redefinição inválido ou expirado.',
            ], 422);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
        ])->save();

        $user->tokens()->delete();
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        return response()->json([
            'message' => 'Senha redefinida com sucesso. Entre novamente para continuar.',
        ]);
    }

    public function login(Request $request, EmailVerificationService $emailVerificationService)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Credenciais inválidas.'], 422);
        }

        // remove tokens antigos (opcional)
        $user->tokens()->delete();

        $remember = (bool) ($data['remember'] ?? false);
        $expiresAt = $remember ? now()->addDays(30) : null;
        $token = $user->createToken('web', ['*'], $expiresAt)->plainTextToken;
        $user->load('psychologist');

        if ($user->requiresEmailVerification()) {
            $emailVerificationService->ensureActiveCode($user);
            $user->refresh()->load('psychologist');
        }

        return response()->json([
            'token' => $token,
            'user' => $user,
            'requires_email_verification' => $user->requiresEmailVerification(),
            'remember' => $remember,
            'expires_at' => $expiresAt?->toISOString(),
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('psychologist'));
    }

    public function verifyEmail(Request $request, EmailVerificationService $emailVerificationService)
    {
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = $request->user();

        if (!$emailVerificationService->verify($user, $data['code'])) {
            return response()->json([
                'message' => 'Código inválido ou expirado.',
            ], 422);
        }

        return response()->json([
            'message' => 'E-mail validado com sucesso.',
            'user' => $user->fresh()->load('psychologist'),
        ]);
    }

    public function resendEmailVerification(Request $request, EmailVerificationService $emailVerificationService)
    {
        $user = $request->user();

        if (!$user->requiresEmailVerification()) {
            return response()->json([
                'message' => 'Este e-mail já foi validado.',
                'user' => $user->load('psychologist'),
            ]);
        }

        $emailVerificationService->send($user);

        return response()->json([
            'message' => 'Código de validação reenviado.',
            'user' => $user->fresh()->load('psychologist'),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout realizado.']);
    }
}
