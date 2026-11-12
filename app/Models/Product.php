<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'price',
        'compare_at_price',
        'stock_quantity',
        'image',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function activeVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->where('is_active', true);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function getHasVariantsAttribute(): bool
    {
        return $this->relationLoaded('activeVariants')
            ? $this->activeVariants->isNotEmpty()
            : $this->activeVariants()->exists();
    }

    public function getAvailableStockAttribute(): int
    {
        if ($this->has_variants) {
            return (int) ($this->relationLoaded('activeVariants')
                ? $this->activeVariants->sum('stock_quantity')
                : $this->activeVariants()->sum('stock_quantity'));
        }

        return (int) $this->stock_quantity;
    }

    public function getAllImagesAttribute(): Collection
    {
        $list = collect();

        if ($this->image) {
            $list->push([
                'id' => 'primary',
                'url' => Str::startsWith($this->image, ['http://', 'https://'])
                    ? $this->image
                    : Storage::disk(config('filesystems.default', 'public'))->url($this->image),
                'is_primary' => true,
            ]);
        }

        foreach ($this->images as $galleryImg) {
            $list->push([
                'id' => $galleryImg->id,
                'url' => $galleryImg->url,
                'is_primary' => false,
            ]);
        }

        return $list;
    }

    public function getAverageRatingAttribute(): float
    {
        return (float) round($this->approvedReviews()->avg('rating') ?: 0, 1);
    }

    public function getReviewsCountAttribute(): int
    {
        return $this->approvedReviews()->count();
    }
}
