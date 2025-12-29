<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'first_name' => 'Admin',
            'last_name' => 'System',
            'phone_number' => '0911111111',
            'password' => Hash::make('password'),
            'user_role' => 'admin',
            'birth_date' => '1990-01-01',
            'profile_image' => 'https://ui-avatars.com/api/?name=Admin+System&background=0D8ABC&color=fff',
            'id_image' => 'https://placehold.co/600x400/png?text=Admin+ID',
            'status' => 'active',
        ]);

        $owners = [
            [
                'first_name' => 'سامر',
                'last_name' => 'العقاري',
                'phone_number' => '0933333333',
                'birth_date' => '1985-05-15',
                'profile_image' => 'https://i.pravatar.cc/150?u=samer',
            ],
            [
                'first_name' => 'رنا',
                'last_name' => 'الساحلي',
                'phone_number' => '0944444444',
                'birth_date' => '1992-10-20',
                'profile_image' => 'https://i.pravatar.cc/150?u=rana',
            ],
            [
                'first_name' => 'مازن',
                'last_name' => 'الشيخ',
                'phone_number' => '0955555555',
                'birth_date' => '1980-03-10',
                'profile_image' => 'https://i.pravatar.cc/150?u=mazen',
            ],
        ];

        foreach ($owners as $owner) {
            User::create(array_merge($owner, [
                'password' => Hash::make('12345678'),
                'user_role' => 'owner',
                'id_image' => 'https://placehold.co/600x400/png?text=Identity+Card',
                'status' => 'active', // حذف is_approved
            ]));
        }

        $tenants = [
            [
                'first_name' => 'أحمد',
                'last_name' => 'طالب',
                'phone_number' => '0966666666',
                'birth_date' => '1998-07-07',
                'profile_image' => 'https://i.pravatar.cc/150?u=ahmad',
            ],
            [
                'first_name' => 'سارة',
                'last_name' => 'مسافر',
                'phone_number' => '0977777777',
                'birth_date' => '1995-12-12',
                'profile_image' => 'https://i.pravatar.cc/150?u=sara',
            ],
            [
                'first_name' => 'كريم',
                'last_name' => 'الجار',
                'phone_number' => '0988888888',
                'birth_date' => '2000-01-20',
                'profile_image' => 'https://i.pravatar.cc/150?u=karim',
            ],
        ];

        foreach ($tenants as $tenant) {
            User::create(array_merge($tenant, [
                'password' => Hash::make('12345678'),
                'user_role' => 'tenant',
                'id_image' => 'https://placehold.co/600x400/png?text=Identity+Card',
                'status' => 'active',
            ]));
        }
    }
}
