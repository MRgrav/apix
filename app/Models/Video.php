<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'title',
        'video_link',
        'play_limit',
       // 'times_played',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Check if the video can be played based on play limit.
     */
    public function canBePlayed()
    {
        return $this->times_played < $this->play_limit;
    }

    /**
     * Increment play count if the video can be played.
     */
    public function incrementPlayCount()
    {
        if ($this->canBePlayed()) {
            $this->times_played += 1;
            $this->save();
            return true;
        }
        return false;
    }
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}

