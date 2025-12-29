<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    // 1. الحقول المسموح تعبئتها
    protected $fillable = [
        'user_id',
        'title',
        'body',
        'type',    // booking, offer, alert
        'is_read', // 0 or 1
    ];

    // 2. تحويل البيانات (مهم جداً للفلتر)
    // عشان is_read ترجع true/false بدل 1/0
    protected $casts = [
        'is_read' => 'boolean',
    ];

    // 3. العلاقة مع المستخدم
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
