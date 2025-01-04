<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Album extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'picture',
        'user_id',
    ];

    public function cards()
    {
        return $this->hasMany(Card::class);
    }

    /**
     * Get the user that owns the album.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
