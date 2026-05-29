<?php

namespace Database\Seeders;

use App\Models\Psychologist;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'pedro.ramos@infobase.com.br');
        $password = env('ADMIN_PASSWORD', 'teste');
        $name = env('ADMIN_NAME', 'Pedro Ramos');

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        Psychologist::updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $name,
                'phone' => null,
                'email' => $email,
                'timezone' => 'America/Sao_Paulo',
                'session_duration' => 50,
                'allow_online' => true,
                'allow_in_person' => true,
            ]
        );
    }
}
