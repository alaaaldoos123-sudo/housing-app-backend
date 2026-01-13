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
                'name_ar' => 'شقة ديلوكس إطلالة قاسيون',
                'name_en' => 'Deluxe Apartment Qasioun View',

                'description_ar' => 'شقة فاخرة جداً في أرقى مناطق دمشق، فرش حديث وتكييف مركزي وإطلالة بانورامية.',
                'description_en' => 'Very luxurious apartment in the finest area of Damascus, modern furniture, central AC, and panoramic view.',

                'location_ar' => 'دمشق - أبو رمانة - شارع الجلاء',
                'location_en' => 'Damascus - Abu Rummaneh - Al Jalaa St.',

                'city_ar' => 'أبو رمانة',
                'city_en' => 'Abu Rummaneh',

                'province_ar' => 'دمشق',
                'province_en' => 'Damascus',

                'price' => 850000,
                'price_unit' => 'night',
                'bedrooms' => 3,
                'bathrooms' => 2,
                'area' => '180',
                'image_url' => 'apartments/img_6.png',
                'image_urls' => ['apartments/img_1.png', 'apartments/img_2.png'],
                'amenities' => ['واي فاي', 'تكييف', 'مصعد', 'باركينغ'],
                'rating' => 4.8,
                'review_count' => 15,
                'is_published' => true,
                'status' => 'active',
            ],
            [
                'name_ar' => 'استديو طلابي مرتب',
                'name_en' => 'Tidy Student Studio',

                'description_ar' => 'استديو نظيف وقريب من المواصلات والجامعة، مثالي للطلاب.',
                'description_en' => 'Clean studio, close to transportation and the university, ideal for students.',

                'location_ar' => 'دمشق - المزة - فيلات غربية',
                'location_en' => 'Damascus - Mezzeh - Western Villas',

                'city_ar' => 'المزة',
                'city_en' => 'Mezzeh',

                'province_ar' => 'دمشق',
                'province_en' => 'Damascus',

                'price' => 350000,
                'price_unit' => 'night',
                'bedrooms' => 1,
                'bathrooms' => 1,
                'area' => '60',
                'image_url' => 'apartments/img_4.png',
                'image_urls' => ['apartments/img_1.png'],
                'amenities' => ['واي فاي', 'مطبخ صغير'],
                'rating' => 4.0,
                'review_count' => 8,
                'is_published' => true,
                'status' => 'active',
            ],
            [
                'name_ar' => 'فيلا مع مسبح خاص',
                'name_en' => 'Luxury Villa with Private Pool',

                'description_ar' => 'فيلا للمناسبات والعائلات مع مسبح وحديقة كبيرة وجلسات خارجية.',
                'description_en' => 'Villa for events and families with a private pool, large garden, and outdoor seating.',

                'location_ar' => 'ريف دمشق - يعفور - المجمع السياحي',
                'location_en' => 'Rif Dimashq - Yaafour - Tourist Complex',

                'city_ar' => 'يعفور',
                'city_en' => 'Yaafour',

                'province_ar' => 'ريف دمشق',
                'province_en' => 'Rif Dimashq',

                'price' => 2500000,
                'price_unit' => 'night',
                'bedrooms' => 5,
                'bathrooms' => 4,
                'area' => '500',
                'image_url' => 'apartments/img_6.png',
                'image_urls' => ['apartments/img_7.png'],
                'amenities' => ['مسبح', 'حديقة', 'حراسة', 'مولدة'],
                'rating' => 5.0,
                'review_count' => 42,
                'is_published' => true,
                'status' => 'active',
            ],
            [
                'name_ar' => 'شاليه الشاطئ الأزرق',
                'name_en' => 'Blue Beach Chalet',

                'description_ar' => 'إطلالة بحرية خلابة، دقيقة واحدة عن الشاطئ، مجهز بالكامل.',
                'description_en' => 'Breathtaking sea view, one minute from the beach, fully equipped.',

                'location_ar' => 'اللاذقية - الشاطئ الأزرق',
                'location_en' => 'Latakia - Blue Beach',

                'city_ar' => 'اللاذقية',
                'city_en' => 'Latakia',

                'province_ar' => 'اللاذقية',
                'province_en' => 'Latakia',

                'price' => 950000,
                'price_unit' => 'night',
                'bedrooms' => 2,
                'bathrooms' => 1,
                'area' => '95',
                'image_url' => 'apartments/img_4.png',
                'image_urls' => ['apartments/img_6.png'],
                'amenities' => ['إطلالة بحرية', 'تكييف', 'بلكونة'],
                'rating' => 4.6,
                'review_count' => 22,
                'is_published' => true,
                'status' => 'active',
            ],
            [
                'name_ar' => 'بيت عربي تراثي',
                'name_en' => 'Traditional Arabic House',

                'description_ar' => 'تجربة سكن مميزة في منزل عربي دمشقي مرمم مع أرض ديار.',
                'description_en' => 'A unique living experience in a restored traditional Arabic house with a courtyard.',

                'location_ar' => 'حلب - المدينة القديمة - قرب القلعة',
                'location_en' => 'Aleppo - Old City - Near Citadel',

                'city_ar' => 'حلب',
                'city_en' => 'Aleppo',

                'province_ar' => 'حلب',
                'province_en' => 'Aleppo',

                'price' => 600000,
                'price_unit' => 'night',
                'bedrooms' => 4,
                'bathrooms' => 2,
                'area' => '220',
                'image_url' => 'apartments/img_3.png',
                'image_urls' => ['apartments/img_5.png'],
                'amenities' => ['أرض ديار', 'مدفأة'],
                'rating' => 4.9,
                'review_count' => 30,
                'is_published' => true,
                'status' => 'active',
            ],
            [
                'name_ar' => 'شقة عائلية وسط المدينة',
                'name_en' => 'Family Apartment City Center',

                'description_ar' => 'شقة واسعة قريبة من الأسواق والمطاعم والخدمات.',
                'description_en' => 'Spacious apartment close to markets, restaurants, and services.',

                'location_ar' => 'حمص - مركز المدينة - شارع الدبلان',
                'location_en' => 'Homs - City Center - Dablan St.',

                'city_ar' => 'حمص',
                'city_en' => 'Homs',

                'province_ar' => 'حمص',
                'province_en' => 'Homs',

                'price' => 300000,
                'price_unit' => 'night',
                'bedrooms' => 3,
                'bathrooms' => 1,
                'area' => '120',
                'image_url' => 'apartments/img_2.png',
                'image_urls' => ['apartments/img_2.png'],
                'amenities' => ['مصعد', 'تدفئة'],
                'rating' => 4.2,
                'review_count' => 5,
                'is_published' => true,
                'status' => 'active',
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
