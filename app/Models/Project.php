<?php

namespace App\Models;

use App\Enums\InvoicingType;
use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Project extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'company_id',
        'quote_id',
        'project_number',
        'status',
        'invoicing_type',
        'start_date',
        'completion_date',
        'project_value',
        'notes',
    ];

    protected $casts = [
        'status' => ProjectStatus::class,
        'invoicing_type' => InvoicingType::class,
        'start_date' => 'date',
        'completion_date' => 'date',
    ];

    /**
     * Register media collections for this model.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('project-photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

        $this->addMediaCollection('project-signature')
            ->singleFile()
            ->acceptsMimeTypes(['image/png']);

        $this->addMediaCollection('project-technician-signature')
            ->singleFile()
            ->acceptsMimeTypes(['image/png']);

        $this->addMediaCollection('project-completion-report')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf']);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (! $project->project_number) {
                $project->project_number = static::generateProjectNumber();
            }
        });
    }

    /**
     * Get the company this project is for.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the quote this project was created from.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Get the quotes that reference this project.
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    /**
     * Get all invoices for this project.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get all items in this project.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProjectItem::class)->orderBy('sort_order');
    }

    /**
     * Check if the project is active.
     */
    public function isActive(): bool
    {
        return $this->status === ProjectStatus::Active;
    }

    /**
     * Check if the project is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === ProjectStatus::Completed;
    }

    /**
     * Check if the project uses split invoicing (2 invoices).
     */
    public function usesSplitInvoicing(): bool
    {
        return in_array($this->invoicing_type, [
            InvoicingType::Split5050,
            InvoicingType::Split3070,
        ]);
    }

    /**
     * Check if the project uses single invoice.
     */
    public function usesSingleInvoice(): bool
    {
        return $this->invoicing_type === InvoicingType::Single;
    }

    /**
     * Get the percentage for the first invoice.
     */
    public function getFirstInvoicePercentage(): int
    {
        return $this->invoicing_type->getFirstInvoicePercentage();
    }

    /**
     * Get the percentage for the second invoice (null if single invoice).
     */
    public function getSecondInvoicePercentage(): ?int
    {
        return $this->invoicing_type->getSecondInvoicePercentage();
    }

    /**
     * Generate a unique project number.
     */
    public static function generateProjectNumber(): string
    {
        $year = now()->year;
        $lastProject = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastProject ? ((int) substr($lastProject->project_number, -4)) + 1 : 1;

        return sprintf('P%d-%04d', $year, $number);
    }

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['project_number', 'status', 'project_value', 'company.name'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
