<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassFrequency extends Model
{
    use HasFactory;

    protected $fillable = [
        'classes_per_month',
        'description'
    ];
}
