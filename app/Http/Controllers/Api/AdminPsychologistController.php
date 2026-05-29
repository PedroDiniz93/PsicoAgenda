<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Psychologist;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminPsychologistController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Acesso restrito ao administrador.');
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        $psychologists = Psychologist::query()
            ->with('user:id,name,email,role,email_verified_at,email_verification_sent_at,email_verification_expires_at')
            ->orderBy('name')
            ->get();

        return response()->json([
            'psychologists' => $psychologists,
        ]);
    }

    public function store(Request $request, EmailVerificationService $emailVerificationService)
    {
        $this->authorizeAdmin($request);

        $data = $request->validate($this->rules());
        $role = $data['role'] ?? 'psychologist';

        $psychologist = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['user_email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'] ?? 'psychologist',
                'email_verified_at' => ($data['role'] ?? 'psychologist') === 'admin' ? now() : null,
            ]);

            return Psychologist::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? $data['user_email'],
                'timezone' => $data['timezone'],
                'session_duration' => $data['session_duration'],
                'allow_online' => $data['allow_online'],
                'allow_in_person' => $data['allow_in_person'],
            ])->load('user:id,name,email,role,email_verified_at,email_verification_sent_at,email_verification_expires_at');
        });

        if ($role === 'psychologist') {
            $emailVerificationService->send($psychologist->user);
            $psychologist->load('user:id,name,email,role,email_verified_at,email_verification_sent_at,email_verification_expires_at');
        }

        return response()->json([
            'psychologist' => $psychologist,
        ], 201);
    }

    public function update(Request $request, Psychologist $psychologist, EmailVerificationService $emailVerificationService)
    {
        $this->authorizeAdmin($request);

        $psychologist->loadMissing('user');
        $data = $request->validate($this->rules($psychologist->user_id, false));
        $role = $data['role'] ?? 'psychologist';
        $wasPsychologist = $psychologist->user->role === 'psychologist';
        $emailChanged = $psychologist->user->email !== $data['user_email'];

        DB::transaction(function () use ($data, $psychologist) {
            $psychologist->user->fill([
                'name' => $data['name'],
                'email' => $data['user_email'],
                'role' => $data['role'] ?? 'psychologist',
            ]);

            if (!empty($data['password'])) {
                $psychologist->user->password = Hash::make($data['password']);
            }

            if (($data['role'] ?? 'psychologist') === 'admin') {
                $psychologist->user->email_verified_at = $psychologist->user->email_verified_at ?? now();
                $psychologist->user->email_verification_code_hash = null;
                $psychologist->user->email_verification_expires_at = null;
                $psychologist->user->email_verification_sent_at = null;
            }

            $psychologist->user->save();

            $psychologist->fill([
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? $data['user_email'],
                'timezone' => $data['timezone'],
                'session_duration' => $data['session_duration'],
                'allow_online' => $data['allow_online'],
                'allow_in_person' => $data['allow_in_person'],
            ])->save();
        });

        if ($role === 'psychologist' && (!$wasPsychologist || $emailChanged || is_null($psychologist->user->fresh()->email_verified_at))) {
            $emailVerificationService->send($psychologist->user->fresh());
        }

        return response()->json([
            'psychologist' => $psychologist->fresh()->load('user:id,name,email,role,email_verified_at,email_verification_sent_at,email_verification_expires_at'),
        ]);
    }

    public function resendEmailVerification(
        Request $request,
        Psychologist $psychologist,
        EmailVerificationService $emailVerificationService
    ) {
        $this->authorizeAdmin($request);

        $psychologist->loadMissing('user');

        abort_unless($psychologist->user, 404, 'Usuário do psicólogo não encontrado.');

        if (!$psychologist->user->requiresEmailVerification()) {
            return response()->json([
                'message' => 'Este e-mail já foi validado.',
                'psychologist' => $psychologist->fresh()->load('user:id,name,email,role,email_verified_at,email_verification_sent_at,email_verification_expires_at'),
            ]);
        }

        $emailVerificationService->send($psychologist->user);

        return response()->json([
            'message' => 'E-mail de validação reenviado.',
            'psychologist' => $psychologist->fresh()->load('user:id,name,email,role,email_verified_at,email_verification_sent_at,email_verification_expires_at'),
        ]);
    }

    private function rules(?int $ignoreUserId = null, bool $passwordRequired = true): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'user_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($ignoreUserId),
            ],
            'password' => [$passwordRequired ? 'required' : 'nullable', 'string', 'min:4', 'max:255'],
            'role' => ['sometimes', Rule::in(['admin', 'psychologist'])],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'timezone' => ['required', 'timezone:all'],
            'session_duration' => ['required', 'integer', 'min:15', 'max:240'],
            'allow_online' => ['required', 'boolean'],
            'allow_in_person' => ['required', 'boolean'],
        ];
    }
}
