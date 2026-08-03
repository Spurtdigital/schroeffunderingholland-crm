<?php

namespace App\Models;

use App\Enums\QuoteProcessingMethod;
use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Quote extends Model implements HasMedia
{
    use HasFactory, LogsActivity, InteractsWithMedia;

    protected $fillable = [
        'company_id',
        'lead_id',
        'machine_id',
        'project_id',
        'quote_number',
        'status',
        'processing_method',
        'valid_until',
        'subtotal',
        'vat_amount',
        'total',
        'rounding_adjustment',
        'notes',
        'reference',
        'pre_text',
        'post_text',
        'payment_term_id',
        'travel_distance',
        'travel_cost_per_km',
        'travel_total',
        'work_address_different',
        'work_street',
        'work_house_number',
        'work_postal_code',
        'work_city',
        'moneybird_quote_id',
        'sent_at',
        'approved_at',
        'show_item_breakdown',
        'signed_at',
        'signed_ip',
        'signed_by_name',
    ];

    protected $casts = [
        'status' => QuoteStatus::class,
        'processing_method' => QuoteProcessingMethod::class,
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'rounding_adjustment' => 'decimal:2',
        'travel_distance' => 'integer',
        'travel_cost_per_km' => 'decimal:2',
        'travel_total' => 'decimal:2',
        'work_address_different' => 'boolean',
        'sent_at' => 'datetime',
        'approved_at' => 'datetime',
        'signed_at' => 'datetime',
        'show_item_breakdown' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($quote) {
            if (! $quote->quote_number) {
                $quote->quote_number = static::generateQuoteNumber();
            }
        });
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    /**
     * Get the assumptions selected for this quote.
     */
    public function assumptions(): BelongsToMany
    {
        return $this->belongsToMany(QuoteAssumption::class, 'quote_assumption_quote')
            ->withPivot('pivot_value');
    }

    /**
     * Get the company this quote is for.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the lead this quote was created from.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    /**
     * Get the project this quote was converted to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get all items in this quote.
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order');
    }

    /**
     * Get the leads that reference this quote.
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Get stock reservations for this quote.
     */
    public function stockReservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    /**
     * Get view logs for this quote.
     */
    public function viewLogs(): HasMany
    {
        return $this->hasMany(QuoteViewLog::class)->orderByDesc('created_at');
    }

    /**
     * Register media collections for signature.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('quote-signature')
            ->singleFile()
            ->acceptsMimeTypes(['image/png']);
    }

    /**
     * Check if the quote has been signed.
     */
    public function isSigned(): bool
    {
        return $this->signed_at !== null;
    }

    /**
     * Check if the quote has been approved.
     */
    public function isApproved(): bool
    {
        return $this->status === QuoteStatus::Approved;
    }

    /**
     * Check if the quote has been sent.
     */
    public function isSent(): bool
    {
        return in_array($this->status, [
            QuoteStatus::Sent,
            QuoteStatus::MoneybirdPrepared,
            QuoteStatus::Approved,
            QuoteStatus::Converted,
        ]);
    }

    /**
     * Check if the quote is expired.
     */
    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    /**
     * Generate a temporary concept quote number.
     *
     * This is a placeholder used until the quote is sent to Moneybird,
     * at which point quote_number is replaced with the official Moneybird
     * estimate number (e.g. "E2026-0012").
     */
    public static function generateQuoteNumber(): string
    {
        // Use a microsecond-based suffix to guarantee uniqueness even
        // when multiple quotes are created in the same second.
        return 'CONCEPT-'.now()->format('YmdHisu');
    }

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['quote_number', 'status', 'total', 'company.name'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Calculate and update totals from items.
     */
    public function recalculateTotals(float $vatRate = 21.0): void
    {
        $subtotal = $this->items->sum('total');

        // Add travel costs to subtotal if present
        if ($this->travel_total) {
            $subtotal += $this->travel_total;
        }

        $vatAmount = round($subtotal * ($vatRate / 100), 2);
        $total = $subtotal + $vatAmount;

        $this->update([
            'subtotal' => $subtotal,
            'vat_amount' => $vatAmount,
            'total' => $total,
        ]);
    }

    /**
     * Calculate travel total based on distance and cost per km.
     */
    public function calculateTravelTotal(): void
    {
        if ($this->travel_distance && $this->travel_cost_per_km) {
            $this->travel_total = $this->travel_distance * $this->travel_cost_per_km;
            $this->save();
        }
    }

    /**
     * Return a display-safe quote number.
     * Hides the raw CONCEPT-... placeholder and returns null instead,
     * so it is never shown to end-users or sent externally.
     */
    public function displayNumber(): ?string
    {
        if (str_starts_with($this->quote_number ?? '', 'CONCEPT-')) {
            return null;
        }

        return $this->quote_number;
    }

    /**
     * Check whether this quote still has a temporary CONCEPT number.
     */
    public function isConcept(): bool
    {
        return str_starts_with($this->quote_number ?? '', 'CONCEPT-');
    }

    /**
     * Get the effective work address for this quote.
     * Returns the work address if different, otherwise falls back to the company address.
     */
    public function workFullAddress(): string
    {
        if ($this->work_address_different) {
            $street = trim(($this->work_street ?? '').' '.($this->work_house_number ?? ''));
            $postalCity = trim(($this->work_postal_code ?? '').' '.($this->work_city ?? ''));
        } else {
            $street = trim(($this->company->street ?? '').' '.($this->company->house_number ?? ''));
            $postalCity = trim(($this->company->postal_code ?? '').' '.($this->company->city ?? ''));
        }

        return trim(implode("\n", array_filter([$street, $postalCity])));
    }
}
