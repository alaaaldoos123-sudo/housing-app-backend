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

            $table->enum('status', ['active', 'pending', 'rejected', 'banned'])->default('pending');
            $table->string('profile_image')->nullable();
            $table->string('id_image')->nullable();

            $table->date('birth_date')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });
    }

      public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
