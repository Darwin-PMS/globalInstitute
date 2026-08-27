<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GalleryImage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'title',
        'description',
        'image',
        'alt_text',
        'status',
        'is_featured',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_featured' => 'boolean',
        ];
    }

    public function galleryCategory(): BelongsTo
    {
        return $this->belongsTo(GalleryCategory::class, 'category_id');
    }

    /**
     * Alias matching the category_id column name. The admin API eager-loads
     * `category`, which did not exist and threw RelationNotFoundException.
     */
    public function category(): BelongsTo
    {
        return $this->galleryCategory();
    }
}
