<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Add this import

class GroupUser extends Model
{
    use HasFactory;

    // Specify the table name explicitly
    protected $table = 'group_user'; // Add this line to specify the correct table

    protected $fillable = [
        'course_id',
        'group_id',
        'user_id',
        'plan_id',
        'class_counted',
        'total_classes',
        'class',
    ];

    /**
     * Get the course that owns the GroupUser
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Get the group that owns the GroupUser
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    /**
     * Get the user that owns the GroupUser
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the plan that owns the GroupUser
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(CoursePlan::class, 'plan_id');
    }
}
