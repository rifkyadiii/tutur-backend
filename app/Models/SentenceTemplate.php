<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SentenceTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'user_id',
        'is_favorite',
    ];

    public function cards(): BelongsToMany
    {
        return $this->belongsToMany(Card::class, 'sentence_template_card');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
