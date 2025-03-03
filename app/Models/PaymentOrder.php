<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentOrder extends Model
{
    use HasFactory;

    protected $table = 'payment_orders';

    protected $fillable = [
        'order_id',
        'amount',
        'currency',
        'user_id',
        'course_id',
        'number_of_classes',
        'class_frequency_id',
        'class',
    ];

    // Define relationships if needed
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
