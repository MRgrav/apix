<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trial extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'trial_start',
        'trial_end',
    ];

    /**
     * Relationship to the User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship to the Course.
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Check if the trial is active.
     *
     * @return bool
     */
    public function isActive()
    {
        return now()->between($this->trial_start, $this->trial_end);
    }
}
