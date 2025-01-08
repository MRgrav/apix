<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'description', 
        'course_id', 
        'created_by',
        'live_class_link'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'group_user','group_id','user_id');
    }
    public function videos()
{
    return $this->hasMany(Video::class);
}

}

