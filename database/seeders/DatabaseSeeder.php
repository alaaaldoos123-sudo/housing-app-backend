<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
// إنشاء 10 رسائل وهمية
class DatabaseSeeder extends Seeder
{
     public function run(): void
    {
        User::create([
            'first_name'   => 'ALAA',
            'last_name'    => 'Admin',
            'phone_number' => '0947989738',
            'password'     => Hash::make('1234567'),
            'user_role'    => 'admin',
            'birth_date'   => Carbon::parse('2003-07-10'),

            'profile_image' => null,
            'id_image'      => null,

            'status'        => 'active',
        ]);

        User::create([
            'first_name'   => 'sdrah',
            'last_name'    => 'safar',
            'phone_number' => '0900000000',
            'password'     => Hash::make('12345678'),
            'user_role'    => 'tenant',
            'birth_date'   => Carbon::parse('2003-07-10'),
            'profile_image' => null,
            'id_image'      => null,
            'status'        => 'active',
        ]);

        $this->call([
            UserSeeder::class,
             ApartmentSeeder::class,
            BookingSeeder::class,

        ]);
    }
}
