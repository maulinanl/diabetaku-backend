<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $table = 'doctors';
    protected $primaryKey = 'doctor_id';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'specialization_id',
        'str_number',
        'institution',
        'verification_status',
        'verified_by_admin_id',
        'verified_at',
        'rejection_reason',
    ];
}
