<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registration extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'category_id',
        'user_id',
        'name',
        'email',
        'responses',
        'status',
        'seed',
        'bracket_position',
        'order_column',
    ];

    protected $casts = [
        'responses' => 'array',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'category_id');
    }

    public function battleMatchesAsPlayer1()
    {
        return $this->hasMany(BattleMatch::class, 'registration1_id');
    }

    public function battleMatchesAsPlayer2()
    {
        return $this->hasMany(BattleMatch::class, 'registration2_id');
    }

    public function wonMatches()
    {
        return $this->hasMany(BattleMatch::class, 'winner_id');
    }
}