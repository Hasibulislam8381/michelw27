<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailOtp extends Model
{
    use HasFactory;
    protected $casts = [
        'expires_at' => 'datetime',
    ];


    protected $fillable = [
        'name',
        'email',
        'password',
        'user_id',
        'verification_code',
        'expires_at',
        'phone_code',
        'phone',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public $timestamps = false;
}
