<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;


class MessageFactory extends Factory
{

    public function definition(): array
    {
        return [
            'sender_id' => 1,
            'receiver_id' => 2,
            'property_id' => 1,
            'text' => $this->faker->sentence(),
            'is_read' => false,
        ];
    }
}
