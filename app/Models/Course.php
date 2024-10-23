<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;


class Course extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'short_description', 'description', 'course_category_id', 'requirements', 'outcomes',
        'faq', 'tags', 'meta_title', 'meta_description', 'meta_keywords', 'meta_author', 'meta_image',
        'thumbnail', 'course_overview_type', 'video_url', 'language', 'course_type', 'is_admin', 'price',
        'is_discount', 'discount_type', 'discount_price', 'discount_start_date', 'discount_end_date',
        'instructor_id', 'is_multiple_instructor', 'partner_instructors', 'is_free', 'level_id', 'status_id',
        'visibility_id', 'last_modified', 'rating', 'total_review', 'total_sales', 'course_duration', 'point',
        'created_by', 'updated_by', 'deleted_by', 'live_class_link', 'pre_recorded_videos' // Fixed
    ];

    // Cast pre_recorded_videos to array
    protected $casts = [
        'pre_recorded_videos' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(CourseCategory::class, 'course_category_id');
    }

    public function sections()
    {
        return $this->hasMany(Section::class, 'course_id');
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
       // Accessor to return full URL for thumbnail
// Relationship with Uploads model for the thumbnail
// Relationship with Uploads model for the thumbnail
public function thumbnailUpload()
{
    return $this->belongsTo(Uploads::class, 'thumbnail');
}

// Accessor to return the full URL for the thumbnail
public function getThumbnailUrlAttribute()
{
    if ($this->thumbnailUpload) {
        // Generate the full URL for the stored image
        return Storage::url($this->thumbnailUpload->file_path);
    }
    return null;
}
public function trials()
{
    return $this->hasMany(Trial::class);
}
public function studyMaterials()
    {
        return $this->hasMany(StudyMaterial::class);
    }
}

