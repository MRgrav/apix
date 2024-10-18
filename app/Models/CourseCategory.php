<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'icon',
        'thumbnail',
        'parent_id',
        'user_id',
        'status_id',
        'is_popular'
    ];

    public function courses()
    {
        return $this->hasMany(Course::class, 'course_category_id');
    }

    public function parent()
    {
        return $this->belongsTo(CourseCategory::class, 'parent_id');
    }


}
