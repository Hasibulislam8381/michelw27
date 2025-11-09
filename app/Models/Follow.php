<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Follow extends Model
{
    use HasFactory;

    // Auth guard use korbe, fillable na kora
    public $guarded = [];

    // Relation to follower user
    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    // Relation to following user
    public function following()
    {
        return $this->belongsTo(User::class, 'following_id');
    }
}
