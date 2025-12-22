<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Psychologist;
use App\Models\Patient;

class DevSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@teste.com'],
            [
                'name' => 'Psicólogo Admin',
                'password' => Hash::make('12345678'),
            ]
        );

        Psychologist::updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => 'Psicólogo Admin',
                'phone' => null,
                'email' => 'admin@teste.com',
                'timezone' => 'America/Sao_Paulo',
                'session_duration' => 50,
                'allow_online' => true,
                'allow_in_person' => true,
            ]
        );

        $psychologist = $user->psychologist()->firstOrFail();

        if ($psychologist->patients()->count() < 10) {
            Patient::factory()
                ->count(10)
                ->create([
                    'psychologist_id' => $psychologist->id,
                    'status' => 'active',
                ]);
        }
    }
}
