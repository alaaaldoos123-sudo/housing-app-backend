<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('apartments', function (Blueprint $table) {
            // نضيف عمود الحالة إذا لم يكن موجوداً
            if (!Schema::hasColumn('apartments', 'status')) {
                $table->string('status')->default('pending')->after('image_urls');
                // القيم: pending (قيد المراجعة), active (مقبول), rejected (مرفوض)
            }

            // ونتأكد من وجود عمود النشر أيضاً
            if (!Schema::hasColumn('apartments', 'is_published')) {
                $table->boolean('is_published')->default(false)->after('status');
            }
        });
    }

    public function down()
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropColumn(['status', 'is_published']);
        });
    }
};
