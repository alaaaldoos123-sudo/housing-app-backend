<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->foreignId('apartment_id')->constrained('apartments')->onDelete('cascade');


            $table->date('check_in');
            $table->date('check_out');

            $table->decimal('total_price', 15, 2);

            $table->enum('status', [
                'pending',
                'accepted',
                'rejected',
                'cancelled',
                'completed'
            ])->default('pending');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['apartment_id', 'check_in', 'check_out']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
