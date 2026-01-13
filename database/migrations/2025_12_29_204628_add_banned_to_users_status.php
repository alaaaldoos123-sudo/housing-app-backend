<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // ✅ ضروري جداً

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'pending', 'rejected', 'banned') NOT NULL DEFAULT 'pending'");
    }
 public function down(): void
    {

        DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'pending', 'rejected') NOT NULL DEFAULT 'pending'");
    }
};
