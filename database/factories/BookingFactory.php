<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Apartment;

class BookingFactory extends Factory
{
    public function definition()
    {
        // توليد تاريخ دخول عشوائي خلال الشهرين القادمين
        $checkIn = fake()->dateTimeBetween('now', '+2 months');

        // عدد ليالي عشوائي بين 1 و 14 ليلة
        $nights = fake()->numberBetween(1, 14);

        // حساب تاريخ الخروج بناءً على الليالي
        $checkOut = (clone $checkIn)->modify("+$nights days");

        return [
            // إذا لم يتم تحديد مستخدم أو شقة في الـ Seeder، سيقوم بإنشاء جدد
            'user_id' => User::factory(),
            'apartment_id' => Apartment::factory(),

            'check_in' => $checkIn,
            'check_out' => $checkOut,

            // سعر عشوائي (سيتم استبداله غالباً في الـ Seeder ليكون دقيقاً حسب سعر الشقة)
            'total_price' => fake()->numberBetween(100000, 2000000),

            'night_count' => $nights,

            // حالات الحجز المتنوعة
            'status' => fake()->randomElement(['pending', 'accepted', 'rejected', 'completed', 'cancelled']),

            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
