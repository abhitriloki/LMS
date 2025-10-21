<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'enrollment_id',
        'template_id',
        'certificate_number',
        'issued_at',
        'expires_at',
        'file_path',
        'qr_code_path',
        'is_valid',
        'is_emailed',
        'emailed_at',
        'download_count',
        'last_downloaded_at',
        'metadata',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'emailed_at' => 'datetime',
        'last_downloaded_at' => 'datetime',
        'is_valid' => 'boolean',
        'is_emailed' => 'boolean',
        'download_count' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the user who earned this certificate
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course this certificate is for
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the enrollment this certificate is for
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Get the template used for this certificate
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id');
    }

    // Query Scopes

    /**
     * Scope to get valid certificates
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope to get expired certificates
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope to search by certificate number
     */
    public function scopeByCertificateNumber(Builder $query, string $number): Builder
    {
        return $query->where('certificate_number', $number);
    }

    // Helper Methods

    /**
     * Check if certificate is valid
     */
    public function isValid(): bool
    {
        return !$this->expires_at || $this->expires_at->isFuture();
    }

    /**
     * Check if certificate is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get days until expiration
     */
    public function getDaysUntilExpiration(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return now()->diffInDays($this->expires_at, false);
    }

    /**
     * Check if certificate is expiring soon
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        $daysUntilExpiration = $this->getDaysUntilExpiration();
        
        return $daysUntilExpiration !== null && 
               $daysUntilExpiration > 0 && 
               $daysUntilExpiration <= $days;
    }

    /**
     * Generate unique certificate number
     */
    public static function generateCertificateNumber(): string
    {
        do {
            $number = 'CERT-' . strtoupper(uniqid()) . '-' . rand(1000, 9999);
        } while (static::where('certificate_number', $number)->exists());

        return $number;
    }

    /**
     * Get download URL for PDF
     */
    public function getDownloadUrl(): string
    {
        return route('certificates.download', $this->id);
    }

    /**
     * Get verification URL
     */
    public function getVerificationUrl(): string
    {
        return $this->verification_url ?? route('certificates.verify', $this->certificate_number);
    }
}
