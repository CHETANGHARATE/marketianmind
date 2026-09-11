<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'lesson_id',
    'title',
    'type',
    'file_path',
    'file_name',
    'file_size',
    'file_type',
    'external_url',
    'description',
    'sort_order',
])]
class LessonResource extends Model
{
    use HasFactory;

    /**
     * Default model attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'file',
        'sort_order' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'file_size' => 'integer',
        ];
    }

    /**
     * Lesson that owns this resource.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Check if this is a downloadable file.
     */
    public function isFile(): bool
    {
        return $this->type === 'file';
    }

    /**
     * Check if this is an external link.
     */
    public function isLink(): bool
    {
        return $this->type === 'link';
    }

    /**
     * Get a human-friendly formatted file size.
     */
    public function formattedSize(): string
    {
        if (! $this->file_size || $this->file_size <= 0) {
            return '';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = (float) $this->file_size;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1) . ' ' . $units[$i];
    }

    /**
     * Clean up stored file when deleting resource.
     */
    public function deleteStoredFile(): void
    {
        if ($this->isFile() && $this->file_path && Storage::disk('public')->exists($this->file_path)) {
            Storage::disk('public')->delete($this->file_path);
        }
    }
}