<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Apartment;
use App\Models\User;

class ApartmentSeeder extends Seeder
{
    public function run(): void
    {
        $owners = User::where('user_role', 'owner')->get();

        if ($owners->isEmpty()) {
            $this->command->info('No owners found! Please run UserSeeder first.');
            return;
        }

        $apartments = [
            [
                'name' => 'شقة ديلوكس إطلالة قاسيون',
                'location' => 'دمشق - أبو رمانة',
                'city' => 'أبو رمانة',
                'province' => 'دمشق',
                'price' => 850000,
                'price_unit' => 'ليلة',
                'description' => 'شقة فاخرة جداً في أرقى مناطق دمشق، فرش حديث وتكييف مركزي.',
                'bedrooms' => 3,
                'bathrooms' => 2,
                'area' => '180m²',
                'image_url' => 'apartments/img_6.png',
                'image_urls' => ['apartments/img_1.png'],
                'amenities' => ['واي فاي', 'تكييف', 'مصعد', 'باركينغ'],
                'rating' => 4.8,
                'review_count' => 15,
                'is_published' => true,
            ],
            [
                'name' => 'استديو طلابي مرتب',
                'location' => 'دمشق - المزة',
                'city' => 'المزة',
                'province' => 'دمشق',
                'price' => 350000,
                'price_unit' => 'ليلة',
                'description' => 'استديو نظيف وقريب من المواصلات والجامعة.',
                'bedrooms' => 1,
                'bathrooms' => 1,
                'area' => '60m²',
                'image_url' => 'apartments/img_4.png',
                'image_urls' => ['apartments/img_1.png'],
                'amenities' => ['واي فاي', 'مطبخ صغير'],
                'rating' => 4.0,
                'review_count' => 8,
                'is_published' => true,
            ],

            [
                'name' => 'فيلا مع مسبح خاص',
                'location' => 'ريف دمشق - يعفور',
                'city' => 'يعفور',
                'province' => 'ريف دمشق',
                'price' => 2500000,
                'price_unit' => 'ليلة',
                'description' => 'فيلا للمناسبات والعائلات مع مسبح وحديقة كبيرة.',
                'bedrooms' => 5,
                'bathrooms' => 4,
                'area' => '500m²',
                'image_url' => 'apartments/img_6.png',
                'image_urls' => ['apartments/img_7.png'],
                'amenities' => ['مسبح', 'حديقة', 'حراسة', 'مولدة'],
                'rating' => 5.0,
                'review_count' => 42,
                'is_published' => true,
            ],

            [
                'name' => 'شاليه الشاطئ الأزرق',
                'location' => 'اللاذقية - الشاطئ الأزرق',
                'city' => 'اللاذقية',
                'province' => 'اللاذقية',
                'price' => 950000,
                'price_unit' => 'ليلة',
                'description' => 'إطلالة بحرية خلابة، دقيقة واحدة عن الشاطئ.',
                'bedrooms' => 2,
                'bathrooms' => 1,
                'area' => '95m²',
                'image_url' => 'apartments/img_4.png',
                'image_urls' => ['apartments/img_6.png'],
                'amenities' => ['إطلالة بحرية', 'تكييف', 'بلكونة'],
                'rating' => 4.6,
                'review_count' => 22,
                'is_published' => true,
            ],

            [
                'name' => 'بيت عربي تراثي',
                'location' => 'حلب - المدينة القديمة',
                'city' => 'حلب',
                'province' => 'حلب',
                'price' => 600000,
                'price_unit' => 'ليلة',
                'description' => 'تجربة سكن مميزة في منزل عربي دمشقي مرمم.',
                'bedrooms' => 4,
                'bathrooms' => 2,
                'area' => '220m²',
                'image_url' => 'apartments/img_3.png',
                'image_urls' => ['apartments/img_5.png'],
                'amenities' => ['أرض ديار', 'مدفأة'],
                'rating' => 4.9,
                'review_count' => 30,
                'is_published' => true,
            ],

            [
                'name' => 'شقة عائلية وسط المدينة',
                'location' => 'حمص - مركز المدينة',
                'city' => 'حمص',
                'province' => 'حمص',
                'price' => 300000,
                'price_unit' => 'ليلة',
                'description' => 'شقة واسعة قريبة من الأسواق والمطاعم.',
                'bedrooms' => 3,
                'bathrooms' => 1,
                'area' => '120m²',
                'image_url' => 'apartments/img_2.png',
                'image_urls' => ['apartments/img_2.png'],
                'amenities' => ['مصعد', 'تدفئة'],
                'rating' => 4.2,
                'review_count' => 5,
                'is_published' => true,
            ],
        ];

        foreach ($apartments as $index => $apartmentData) {

            $owner = $owners[$index % $owners->count()];

            Apartment::create(array_merge($apartmentData, [
                'owner_id' => $owner->id
            ]));
        }
    }
}
