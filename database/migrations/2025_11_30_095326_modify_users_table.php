<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');


            $table->string('phone_number')->unique();


            $table->string('password');


            $table->enum('user_role', ['tenant', 'owner', 'admin'])->default('tenant');

            $table->string('avatar')->nullable();

            $table->date('birth_date');


            $table->string('identity_image')->nullable();


            $table->boolean('is_approved')->default(false);
            $table->string('status')->default('pending');

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
