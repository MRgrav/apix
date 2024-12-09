<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'age',
        'class',
        'skill_level',
        'subject',
        'contact_number',
        'whatsapp_number',
        'email',
        'address',
    ];
}
