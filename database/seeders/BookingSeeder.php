<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\Apartment;
use App\Models\User;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $renter = User::where('user_role', 'user')->first() ?? User::first();
        $apartments = Apartment::all();

        if ($apartments->isEmpty()) {
            $this->command->error("لا توجد شقق لتعبئة الحجوزات. يرجى تشغيل ApartmentSeeder أولاً.");
            return;
        }

        $bookingsData = [
            [
                'apartment_index' => 0,
                'days_from_now'   => 2,
                'duration'        => 3,
                'status'          => 'accepted',
                'notes'           => 'يرجى تأكيد وجود إنترنت سريع كما في الوصف.'
            ],
            [
                'apartment_index' => 1,
                'days_from_now'   => 10,
                'duration'        => 5,
                'status'          => 'pending',
                'notes'           => 'سنصل في وقت متأخر من الليل.'
            ],
            [
                'apartment_index' => 0,
                'days_from_now'   => -10,
                'duration'        => 4,
                'status'          => 'completed',
                'notes'           => 'كانت تجربة رائعة شكراً لكم.'
            ]
        ];

        foreach ($bookingsData as $data) {
            $apartment = $apartments[$data['apartment_index']] ?? $apartments->first();

            $checkIn = Carbon::now()->addDays($data['days_from_now']);
            $checkOut = (clone $checkIn)->addDays($data['duration']);

            $totalPrice = $data['duration'] * $apartment->price;

            Booking::create([
                'user_id'      => $renter->id,
                'apartment_id' => $apartment->id,
                'check_in'     => $checkIn->format('Y-m-d'),
                'check_out'    => $checkOut->format('Y-m-d'),
                'total_price'  => $totalPrice,
                'status'       => $data['status'],
                'notes'        => $data['notes'],
            ]);
        }

        $this->command->info("تمت تعبئة جدول الحجوزات بنجاح!");
    }
}
