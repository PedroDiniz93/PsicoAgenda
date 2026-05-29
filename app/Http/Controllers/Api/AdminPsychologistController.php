<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Psychologist;
use App\Models\User;
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
            ->with('user:id,name,email,role,email_verified_at')
            ->orderBy('name')
            ->get();

        return response()->json([
            'psychologists' => $psychologists,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin($request);

        $data = $request->validate($this->rules());

        $psychologist = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['user_email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'] ?? 'psychologist',
                'email_verified_at' => now(),
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
            ])->load('user:id,name,email,role,email_verified_at');
        });

        return response()->json([
            'psychologist' => $psychologist,
        ], 201);
    }

    public function update(Request $request, Psychologist $psychologist)
    {
        $this->authorizeAdmin($request);

        $psychologist->loadMissing('user');
        $data = $request->validate($this->rules($psychologist->user_id, false));

        DB::transaction(function () use ($data, $psychologist) {
            $psychologist->user->fill([
                'name' => $data['name'],
                'email' => $data['user_email'],
                'role' => $data['role'] ?? 'psychologist',
            ]);

            if (!empty($data['password'])) {
                $psychologist->user->password = Hash::make($data['password']);
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

        return response()->json([
            'psychologist' => $psychologist->fresh()->load('user:id,name,email,role,email_verified_at'),
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
