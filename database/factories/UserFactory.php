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

            // ❌ تم حذف الإيميل من هنا لأنه غير موجود في الداتابيز
            // 'email' => fake()->unique()->safeEmail(),
            // 'email_verified_at' => now(),

            'phone_number' => fake()->phoneNumber(),

            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password

            // ✅ تأكدنا من استخدام tenant بدلاً من user لتجنب الخطأ السابق
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
            // 'email' => 'admin@admin.com', // ❌ احذف هذا أيضاً إذا كان الأدمن لا يملك إيميل
            'first_name' => 'Super',
            'last_name' => 'Admin',
        ]);
    }
}
