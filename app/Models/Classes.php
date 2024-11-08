<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    protected $fillable = ['name'];
    use HasFactory;
    public function courses()
{
    return $this->hasMany(Course::class);
}
}


