<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventCategory extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'description',
        'has_prelims',
        'current_phase',
        'current_prelims_registration_id',
    ];

    protected $casts = [
        'has_prelims' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function registrationFields(): HasMany
    {
        return $this->hasMany(RegistrationField::class, 'event_category_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'category_id');
    }

    public function battles(): HasMany
    {
        return $this->hasMany(Battle::class, 'category_id');
    }

    public function currentPrelimsRegistration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'current_prelims_registration_id');
    }
}