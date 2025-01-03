<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'otp', // Include OTP in mass-assignable fields
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp', // Hide OTP for security
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
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
        return $this->belongsToMany(Group::class, 'group_user','user_id','group_id');
    }
    public function purchasedCourses()
    {
        return $this->belongsToMany(Course::class, 'purchases', 'user_id', 'course_id');
    }

}
