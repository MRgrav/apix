<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoursePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_name',
        'plan_details',
        'old_rate',
        'current_rate',
        'category',
        'is_NRI',
        'GST',
        'final_rate',
    ];

}
