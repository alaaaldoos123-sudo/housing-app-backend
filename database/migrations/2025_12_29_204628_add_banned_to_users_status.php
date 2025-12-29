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
        // نستخدم أمر SQL مباشر لتعديل العمود وإضافة 'banned' للقائمة
        // هذا يحل مشكلة Duplicate column ويحافظ على البيانات القديمة
        DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'pending', 'rejected', 'banned') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // في حال التراجع، نعود للقائمة القديمة (بدون banned)
        // انتبه: إذا كان هناك مستخدمون حالتهم banned ستحصل مشكلة هنا، لذلك التراجع يكون بحذر
        DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'pending', 'rejected') NOT NULL DEFAULT 'pending'");
    }
};
