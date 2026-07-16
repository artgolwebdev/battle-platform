<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationField extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'event_category_id',
        'field_name',
        'field_type',
        'required',
        'options',
    ];

    protected $casts = [
        'required' => 'boolean',
        'options' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (RegistrationField $field) {
            if (! $field->event_id && $field->event_category_id) {
                $field->event_id = $field->category?->event_id ?? EventCategory::find($field->event_category_id)?->event_id;
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id');
    }
}