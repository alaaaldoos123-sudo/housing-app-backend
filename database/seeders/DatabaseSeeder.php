<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;


    public function run(): void
    {

        User::create([
            'first_name' => 'ALAA',
            'last_name' => 'Admin',
            'phone_number' => '0947989738',
            'password' => Hash::make('12345678'),
            'user_role' => 'admin',

            'avatar' => null,
            'birth_date' => Carbon::parse('2003-7-10'),
            'identity_image' => null,
            'is_approved' => true,
            'status' => 'active',
        ]);
        User::create([
            'first_name' => 'sdrah',
            'last_name' => 'safar',
            'phone_number' => '0900000000',
            'password' => Hash::make('12345678'),
            'user_role' => 'tenant',

            'avatar' => null,
            'birth_date' => Carbon::parse('2003-7-10'),
            'identity_image' => null,
            'is_approved' => true,
            'status' => 'active',
        ]);



    }
}
