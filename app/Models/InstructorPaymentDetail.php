<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorPaymentDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_payment_id',
        'group_student_name',
        'no_of_classes',
        'per_class_payment',
        'total_amount',
        'transaction',
    ];

    // public function user(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'foreign_key', 'other_key');
    // }
}
