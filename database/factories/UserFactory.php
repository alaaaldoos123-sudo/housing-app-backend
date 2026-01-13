<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition()
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),


            'phone_number' => fake()->unique()->numerify('09########'),

            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password

            'user_role' => fake()->randomElement(['tenant', 'tenant', 'tenant', 'owner']),

            'status' => 'active',
            'remember_token' => Str::random(10),

            'profile_image' => 'https://placehold.co/200x200/png?text=User',
        ];
    }

    public function admin()
    {
        return $this->state(fn (array $attributes) => [
            'user_role' => 'admin',
            'phone_number' => '0999999999',
            'first_name' => 'Super',
            'last_name' => 'Admin',
        ]);
    }
}
