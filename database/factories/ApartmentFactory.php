<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;
class ApartmentFactory extends Factory
{
    public function definition()
    {
        $systemPath = storage_path('app/public/apartments');

        // جلب الملفات
        $allFiles = [];
        if (File::exists($systemPath)) {
            $files = File::files($systemPath);
            foreach ($files as $file) {
                $allFiles[] = $file->getFilename();
            }
        }

        if (empty($allFiles)) {
            // صورة احتياطية في حال كان المجلد فارغ
            $randomImage = 'default.png';
        } else {
            $randomImage = fake()->randomElement($allFiles);
        }
        $governorates = [
            ['ar' => 'دمشق', 'en' => 'Damascus'],
            ['ar' => 'ريف دمشق', 'en' => 'Rif Dimashq'],
            ['ar' => 'درعا', 'en' => 'Daraa'],
            ['ar' => 'حلب', 'en' => 'Aleppo'],
            ['ar' => 'حمص', 'en' => 'Homs'],
            ['ar' => 'حماة', 'en' => 'Hama'],
            ['ar' => 'اللاذقية', 'en' => 'Latakia'],
            ['ar' => 'طرطوس', 'en' => 'Tartus'],
            ['ar' => 'إدلب', 'en' => 'Idlib'],
            ['ar' => 'السويداء', 'en' => 'As-Suwayda'],
            ['ar' => 'القنيطرة', 'en' => 'Quneitra'],
            ['ar' => 'دير الزور', 'en' => 'Deir ez-Zor'],
            ['ar' => 'الرقة', 'en' => 'Ar-Raqqa'],
            ['ar' => 'الحسكة', 'en' => 'Al-Hasakah'],
        ];

        $selectedLocation = fake()->randomElement($governorates);

        // ✅ توليد رقم عشوائي للصورة الرئيسية من 1 إلى 40

        return [
            'owner_id' => \App\Models\User::factory(),

            'name_ar' => 'شقة ' . fake()->randomElement(['مميزة', 'واسعة', 'حديثة', 'مفروشة', 'للعائلات']) . ' في ' . $selectedLocation['ar'],
            'name_en' => fake()->randomElement(['Luxury', 'Cozy', 'Modern', 'Furnished', 'Family']) . ' Apartment in ' . $selectedLocation['en'],

            'description_ar' => 'عقار مميز يتمتع بإطلالة رائعة وقريب من الخدمات العامة والأسواق. مناسب جداً للإقامة المريحة في ' . $selectedLocation['ar'],
            'description_en' => 'A distinctive property with a great view, close to public services and markets. Very suitable for a comfortable stay in ' . $selectedLocation['en'],

            'city_ar' => $selectedLocation['ar'],
            'city_en' => $selectedLocation['en'],
            'province_ar' => $selectedLocation['ar'],
            'province_en' => $selectedLocation['en'],

            'location_ar' => 'حي ' . fake()->randomElement(['الزهور', 'النهضة', 'السلام', 'اليرموك', 'البلد']) . ' - شارع ' . fake()->numberBetween(1, 50),
            'location_en' => 'Al-' . fake()->randomElement(['Zohour', 'Nahda', 'Salam', 'Yarmouk']) . ' District - Street ' . fake()->numberBetween(1, 50),

            'price' => fake()->numberBetween(75000, 800000),
            'price_unit' => fake()->randomElement(['night', 'month']),

            'area' => fake()->numberBetween(70, 350),
            'bedrooms' => fake()->numberBetween(1, 5),
            'bathrooms' => fake()->numberBetween(1, 3),

            'image_url' => "apartments/$randomImage",

            'image_urls' => ["apartments/$randomImage"],
            'amenities' => fake()->randomElements(
                ['واي فاي', 'تكييف', 'تدفئة', 'مصعد', 'باركينغ', 'مولدة', 'مسبح', 'شرفة', 'حراسة'],
                fake()->numberBetween(3, 7)
            ),

            'status' => 'active',
            'is_published' => true,
        ];
    }
}
