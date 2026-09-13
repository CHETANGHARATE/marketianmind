<?php

namespace App\Models;

use App\Enums\WhatsAppTemplateCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WhatsAppTemplate extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_message_templates';

    protected $fillable = [
        'name',
        'slug',
        'provider_template_name',
        'template_name',
        'language',
        'category',
        'body_text',
        'body',
        'variables',
        'status',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'category' => WhatsAppTemplateCategory::class,
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    public function getBodyAttribute(): ?string
    {
        return $this->attributes['body_text'] ?? null;
    }

    public function setBodyAttribute($value): void
    {
        $this->attributes['body_text'] = $value;
    }

    public function getTemplateNameAttribute(): ?string
    {
        return $this->attributes['provider_template_name'] ?? null;
    }

    public function setTemplateNameAttribute($value): void
    {
        $this->attributes['provider_template_name'] = $value;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
            if (empty($template->provider_template_name)) {
                $template->provider_template_name = str_replace('-', '_', Str::slug($template->name));
            }
            if (empty($template->body_text) && !empty($template->attributes['body'])) {
                $template->body_text = $template->attributes['body'];
            }
        });
    }

    /**
     * Author / Creator of template.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Messages sent using this template.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'template_id');
    }

    /**
     * Automations using this WhatsApp template.
     */
    public function automations(): HasMany
    {
        return $this->hasMany(Automation::class, 'whatsapp_template_id');
    }

    /**
     * Check if this template is for marketing.
     */
    public function isMarketing(): bool
    {
        return $this->category === WhatsAppTemplateCategory::MARKETING;
    }

    /**
     * Scope query to active and approved templates.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('status', 'approved');
    }
}
