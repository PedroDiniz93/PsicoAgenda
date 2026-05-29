<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request, EmailVerificationService $emailVerificationService)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Credenciais inválidas.'], 422);
        }

        // remove tokens antigos (opcional)
        $user->tokens()->delete();

        $token = $user->createToken('web')->plainTextToken;
        $user->load('psychologist');

        if ($user->requiresEmailVerification()) {
            $emailVerificationService->ensureActiveCode($user);
            $user->refresh()->load('psychologist');
        }

        return response()->json([
            'token' => $token,
            'user' => $user,
            'requires_email_verification' => $user->requiresEmailVerification(),
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
