<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudyMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'photo',
        'pdf',
        'audio'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
