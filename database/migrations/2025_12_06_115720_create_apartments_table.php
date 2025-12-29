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

            $table->string('name');
            $table->text('description')->nullable();


            $table->string('location')->nullable();
            $table->string('city');
            $table->string('province');


            $table->decimal('price', 12, 2);

            $table->string('price_unit')->default('night');

            $table->string('image_url')->nullable();
            $table->json('image_urls')->nullable();

            $table->integer('bedrooms')->default(1);
            $table->integer('bathrooms')->default(1);

            $table->string('area');

            $table->json('amenities')->nullable();

            $table->double('rating')->default(0);
            $table->integer('review_count')->default(0);
            $table->boolean('is_published')->default(true);

            $table->timestamps();

            $table->index(['city', 'province']);
            $table->index('price');
        });
    }

      public function down(): void
    {
        Schema::dropIfExists('apartments');
    }
};
