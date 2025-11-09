<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchRating extends Model
{
    use HasFactory;
    public $guarded = [];

    public function caption()
    {
        return $this->belongsTo(MatchRatingCaption::class, 'caption_id');
    }
}
