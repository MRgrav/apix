<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'course_id',
        'photo',
        'pdf',
        'audio'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the group that owns the StudyMaterial
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
}
