<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasApiTokens, Notifiable, MustVerifyEmail;

    protected $table = 'users';
    protected $primaryKey = 'user_id';

    public $timestamps = true;

    protected $fillable = [
        'role_id',
        'email',
        'password_hash',
        'full_name',
        'phone_number',
        'date_of_birth',
        'gender',
        'account_status',
        'login_attempts',
        'locked_until',
        'email_verified_at',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }
}
