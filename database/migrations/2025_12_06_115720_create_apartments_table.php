<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apartments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');

            // 👇 التعديل هنا: قسمنا الاسم والوصف والموقع للغتين
            $table->string('name_en');
            $table->string('name_ar');

            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();

            $table->string('location_en')->nullable(); // العنوان التفصيلي
            $table->string('location_ar')->nullable();

            $table->string('city_en');
            $table->string('city_ar');

            $table->string('province_en');
            $table->string('province_ar');

            // بقية الحقول كما هي (الأرقام لا تحتاج ترجمة)
            $table->decimal('price', 12, 2);
            $table->string('price_unit')->default('night');
            $table->string('image_url')->nullable();
            $table->json('image_urls')->nullable();
            $table->integer('bedrooms')->default(1);
            $table->integer('bathrooms')->default(1);
            $table->string('area');
            $table->json('amenities')->nullable(); // يفضل تخزين الميزات كـ IDs وتترجم في الفرونت، أو تخزينها كـ JSON مزدوج اللغة
            $table->double('rating')->default(0);
            $table->integer('review_count')->default(0);
            $table->boolean('is_published')->default(true);

            $table->timestamps();

            $table->index(['city_en', 'province_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apartments');
    }
};
