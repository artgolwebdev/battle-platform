<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Event extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'company_id',
        'title',
        'description',
        'location',
        'start_date',
        'end_date',
        'programme',
        'registration_open',
    ];

    protected $casts = [
        'programme' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'registration_open' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(EventCategory::class);
    }

    public function registrationFields(): HasManyThrough
    {
        return $this->hasManyThrough(
            RegistrationField::class,
            EventCategory::class,
            'event_id',
            'event_category_id'
        );
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function battles(): HasMany
    {
        return $this->hasMany(Battle::class);
    }

    public function activeBattle()
    {
        return $this->hasOne(Battle::class)->latestOfMany();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('banner')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(180)
            ->sharpen(10)
            ->nonQueued();
    }
}