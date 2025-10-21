<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class CertificateTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'html_template',
        'variables',
        'orientation',
        'page_size',
        'is_default',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get certificates using this template
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'template_id');
    }

    // Query Scopes

    /**
     * Scope to get active templates
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get default template
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Check if this is the default template
     */
    public function isDefault(): bool
    {
        return $this->is_default === true;
    }

    /**
     * Check if template is active
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Set as default template
     */
    public function setAsDefault(): void
    {
        // Remove default flag from other templates
        static::where('is_default', true)->update(['is_default' => false]);

        // Set this template as default
        $this->update(['is_default' => true]);
    }

    /**
     * Get the creator of this template
     */
    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Render template with data
     */
    public function render(array $data): string
    {
        $html = $this->html_template;

        // Replace variables in template
        foreach ($data as $key => $value) {
            $html = str_replace("{{" . $key . "}}", $value, $html);
        }

        return $html;
    }
}
