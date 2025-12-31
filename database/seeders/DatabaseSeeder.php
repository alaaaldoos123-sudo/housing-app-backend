<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Apartment;
use App\Models\Booking;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1️⃣ إنشاء حساباتكم الثابتة
        $admin = User::create([
            'first_name'   => 'ALAA',
            'last_name'    => 'Admin',
            'phone_number' => '0947989738',
            'password'     => Hash::make('1234567'),
            'user_role'    => 'admin',
            'birth_date'   => Carbon::parse('2003-07-10'),
            'profile_image' => null,
            'status'        => 'active',
        ]);

        $tenant = User::create([
            'first_name'   => 'sdrah',
            'last_name'    => 'safar',
            'phone_number' => '0900000000',
            'password'     => Hash::make('12345678'),
            'user_role'    => 'tenant', // ✅ صحيح
            'birth_date'   => Carbon::parse('2003-07-10'),
            'profile_image' => null,
            'status'        => 'active',
        ]);

        // 2️⃣ إنشاء 10 مالكين جدد (Owners)
        $owners = User::factory()->count(100)->state(['user_role' => 'owner'])->create();

        // 3️⃣ إنشاء الشقق
        $apartments = Apartment::factory()->count(500)->make()->each(function ($apartment) use ($owners) {
            $apartment->owner_id = $owners->random()->id;
            $apartment->save();
        });


        $randomUsers = User::factory()->count(200)->state(['user_role' => 'tenant'])->create();

        // دمج سدرة مع المستخدمين العشوائيين
        $allTenants = $randomUsers->push($tenant);

        // 5️⃣ إنشاء الحجوزات
        if ($apartments->count() > 0) {
            for ($i = 0; $i < 300; $i++) {
                Booking::factory()->create([
                    'user_id' => $allTenants->random()->id,
                    'apartment_id' => $apartments->random()->id,
                ]);
            }
        }
    }
}
