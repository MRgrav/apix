<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_id',
        // 'group_id',
        'no_of_classes',
        'per_class_payment',
        'total_amount', 
        'transaction',
        'group_student_name',
        'month',
        'year',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'instructor_id');
    }
}
