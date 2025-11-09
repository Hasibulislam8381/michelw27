<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchRatingCaption extends Model
{
    use HasFactory;
    public $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'rated_by');
    }

    public function reactions()
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'caption_id');
    }

    public function ratings()
    {
        return $this->hasMany(MatchRating::class, 'caption_id');
    }
}
