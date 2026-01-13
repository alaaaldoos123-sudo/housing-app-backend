<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Apartment;

class BookingFactory extends Factory
{
    public function definition()
    {
        $checkIn = fake()->dateTimeBetween('now', '+2 months');

        $nights = fake()->numberBetween(1, 14);

        $checkOut = (clone $checkIn)->modify("+$nights days");

        return [
            'user_id' => User::factory(),
            'apartment_id' => Apartment::factory(),

            'check_in' => $checkIn,
            'check_out' => $checkOut,

            'total_price' => fake()->numberBetween(100000, 2000000),

            'night_count' => $nights,

            'status' => fake()->randomElement(['pending', 'accepted', 'rejected', 'completed', 'cancelled']),

            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
