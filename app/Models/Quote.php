<?php

namespace App\Models;

use App\Enums\QuoteProcessingMethod;
use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Quote extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'company_id',
        'lead_id',
        'project_id',
        'quote_number',
        'status',
        'processing_method',
        'valid_until',
        'subtotal',
        'vat_amount',
        'total',
        'notes',
        'pre_text',
        'post_text',
        'payment_term_id',
        'travel_distance',
        'travel_cost_per_km',
        'travel_total',
        'moneybird_quote_id',
        'sent_at',
        'approved_at',
        'show_item_breakdown',
    ];

    protected $casts = [
        'status' => QuoteStatus::class,
        'processing_method' => QuoteProcessingMethod::class,
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'travel_distance' => 'integer',
        'travel_cost_per_km' => 'decimal:2',
        'travel_total' => 'decimal:2',
        'sent_at' => 'datetime',
        'approved_at' => 'datetime',
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
     * Generate a unique quote number.
     */
    public static function generateQuoteNumber(): string
    {
        $year = now()->year;
        $lastQuote = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastQuote ? ((int) substr($lastQuote->quote_number, -4)) + 1 : 1;

        return sprintf('Q%d-%04d', $year, $number);
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
}
