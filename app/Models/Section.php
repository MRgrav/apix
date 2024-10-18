<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Section extends Model
{
    use SoftDeletes;

    protected $fillable = ['title', 'course_id', 'order', 'status_id', 'created_by', 'updated_by'];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
