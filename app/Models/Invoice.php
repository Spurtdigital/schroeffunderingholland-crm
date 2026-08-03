<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Invoice extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'project_id',
        'company_id',
        'invoice_type',
        'status',
        'invoice_number',
        'invoice_date',
        'due_date',
        'subtotal',
        'vat_amount',
        'total',
        'notes',
        'include_terms',
        'deposit_percentage',
        'moneybird_invoice_id',
        'sent_at',
        'paid_at',
    ];

    protected $casts = [
        'invoice_type' => InvoiceType::class,
        'status' => InvoiceStatus::class,
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'deposit_percentage' => 'decimal:2',
        'include_terms' => 'boolean',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (!$invoice->invoice_number) {
                $invoice->invoice_number = static::generateInvoiceNumber();
            }
        });
    }

    /**
     * Get the project this invoice belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
    
    /**
     * Get the company this invoice is for.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Check if the invoice is a deposit invoice.
     */
    public function isDeposit(): bool
    {
        return $this->invoice_type === InvoiceType::Deposit;
    }

    /**
     * Check if the invoice is a final invoice.
     */
    public function isFinal(): bool
    {
        return $this->invoice_type === InvoiceType::Final;
    }

    /**
     * Check if the invoice has been sent.
     */
    public function isSent(): bool
    {
        return in_array($this->status, [
            InvoiceStatus::Sent,
            InvoiceStatus::Paid,
        ]);
    }

    /**
     * Check if the invoice has been paid.
     */
    public function isPaid(): bool
    {
        return $this->status === InvoiceStatus::Paid;
    }

    /**
     * Generate a unique invoice number.
     */
    public static function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $lastInvoice = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastInvoice ? ((int) substr($lastInvoice->invoice_number, -4)) + 1 : 1;

        return sprintf('INV%d-%04d', $year, $number);
    }

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['invoice_number', 'status', 'total', 'project.project_number'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
