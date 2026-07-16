<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BattleMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'battle_id',
        'round',
        'position',
        'registration1_id',
        'registration2_id',
        'winner_id',
        'score1',
        'score2',
        'status',
    ];

    public function battle(): BelongsTo
    {
        return $this->belongsTo(Battle::class);
    }

    public function registration1(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration1_id');
    }

    public function registration2(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration2_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'winner_id');
    }

    public function isBye(): bool
    {
        return $this->registration1_id === null || $this->registration2_id === null;
    }

    public function getOpponentOf(Registration $registration): ?Registration
    {
        if ($this->registration1_id === $registration->id) {
            return $this->registration2;
        }

        if ($this->registration2_id === $registration->id) {
            return $this->registration1;
        }

        return null;
    }
}
