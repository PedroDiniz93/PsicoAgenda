<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Patient;

class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'email' => $this->faker->optional()->safeEmail(),
            'cpf' => $this->faker->optional()->numerify('###.###.###-##'),
            'birth_date' => $this->faker->optional()->dateTimeBetween('-70 years', '-12 years')?->format('Y-m-d'),
            'emergency_contacts' => [
                [
                    'name' => $this->faker->name(),
                    'phone' => $this->faker->phoneNumber(),
                    'relationship' => $this->faker->randomElement(['Família', 'Amigo', 'Responsável']),
                ],
            ],
            'status' => $this->faker->randomElement(['active', 'paused', 'closed']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
