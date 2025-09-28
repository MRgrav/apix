<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Ensure this is included

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'otp',
        'role_id',
        'is_nri',
        'whatsapp',
        'gender',
        'country',
        'state',
        'address',
        'district',
        'demo_class_url',
        'is_demo_active',
        'demo_class_time',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'demo_class_time' => 'datetime',
        'is_demo_active' => 'boolean',
    ];

    public function trials()
    {
        return $this->hasMany(Trial::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_user', 'user_id', 'group_id');
    }

    public function purchasedCourses()
    {
        return $this->belongsToMany(Course::class, 'purchases', 'user_id', 'course_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
