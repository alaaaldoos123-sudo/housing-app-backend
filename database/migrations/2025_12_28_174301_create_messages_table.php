<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            // المرسل والمستقبل
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade');

            // الشقة التي يتم الحديث عنها (مهمة لفلترة المحادثات لاحقاً)
            // ملاحظة: افترضت اسم الجدول 'apartments'، إذا كان غير هيك خبرني
            $table->foreignId('property_id')->nullable()->constrained('apartments')->onDelete('set null');

            $table->text('text');
            $table->boolean('is_read')->default(false); // حالة القراءة
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
