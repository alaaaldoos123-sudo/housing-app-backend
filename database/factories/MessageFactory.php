<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sender_id' => 1, // افترضنا اليوزر رقم 1 هو المرسل
            'receiver_id' => 2, // افترضنا اليوزر رقم 2 هو المستقبل
            'property_id' => 1, // رقم عقار موجود
            'text' => $this->faker->sentence(),
            'is_read' => false,
        ];
    }
}
