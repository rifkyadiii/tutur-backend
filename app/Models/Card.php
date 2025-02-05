<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Card extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'spelling',
        'picture',
        'voice',
        'album_id',
    ];

    protected $nullable = ['album_id'];

    public function cards()
    {
        return $this->hasMany(Card::class);
    }

    public function sentenceTemplates(): BelongsToMany
    {
        return $this->belongsToMany(SentenceTemplate::class, 'sentence_template_card');
    }

    /**
     * Get the album that owns the card.
     */
    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }
}
