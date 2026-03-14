<?php

namespace App\Models;

use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Lead extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'company_id',
        'converted_to_quote_id',
        'name',
        'email',
        'phone',
        'postal_code',
        'house_number',
        'city',
        'street',
        'request_data',
        'status',
        'notes',
        'internal_notes',
    ];

    protected $casts = [
        'request_data' => 'array',
        'status' => LeadStatus::class,
    ];

    /**
     * Route notifications for the mail channel.
     */
    public function routeNotificationForMail(): string
    {
        return $this->email;
    }

    /**
     * Get contact name (alias for InfoRequiredNotification compatibility).
     */
    public function getContactNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Get company name (alias for InfoRequiredNotification compatibility).
     */
    public function getCompanyNameAttribute(): string
    {
        return $this->company?->name ?? 'Uw bedrijf';
    }

    /**
     * Get the full address.
     */
    protected function fullAddress(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn () => trim("{$this->street} {$this->house_number}, {$this->postal_code} {$this->city}")
        );
    }

    /**
     * Get the company associated with this lead.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the quote this lead was converted to.
     */
    public function convertedToQuote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'converted_to_quote_id');
    }

    /**
     * Get all quotes created from this lead.
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    /**
     * Check if the lead has been converted to a quote.
     */
    public function isConverted(): bool
    {
        return $this->status === LeadStatus::Converted && $this->converted_to_quote_id !== null;
    }

}
