<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Uploads extends Model
{
    protected $fillable = [
        'file_path', 'file_type', 'mime_type', 'uploaded_by'
    ];

    // Relationship to the User model (optional)
    public function user()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
