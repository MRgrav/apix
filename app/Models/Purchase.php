<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'payment_id',
        'amount',
        'status',
        'plan_id',
        'expiry_date',
        'number_of_classes',
        'class_frequency_id',
        'class',
        'category',
    ];

    protected $casts = [
        'expiry_date' => 'datetime', // Automatically cast expiry_date to a Carbon instance
        'created_at' => 'datetime',   // Automatically cast created_at to a Carbon instance
    ];
    

    // Relationship to User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship to Course
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    // Relation to Plan
    public function plan(){
        return $this->belongsTo(CoursePlan::class, 'plan_id');
    }

}
