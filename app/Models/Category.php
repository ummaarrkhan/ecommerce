<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    // Parent category
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Child categories
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get IDs of a category and all its active descendant subcategories.
     *
     * @return array<int>
     */
    public static function getSelfAndDescendantIds(int|string $categoryId): array
    {
        $id = (int) $categoryId;
        if ($id <= 0) {
            return [];
        }

        $ids = [$id];
        $childIds = static::where('parent_id', $id)
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($val) => (int) $val)
            ->all();

        if (! empty($childIds)) {
            $ids = array_merge($ids, $childIds);

            $grandChildIds = static::whereIn('parent_id', $childIds)
                ->where('is_active', true)
                ->pluck('id')
                ->map(fn ($val) => (int) $val)
                ->all();

            if (! empty($grandChildIds)) {
                $ids = array_merge($ids, $grandChildIds);
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Get count of all active products in this category and its subcategories.
     */
    public function getTotalProductsCountAttribute(): int
    {
        $directCount = (int) ($this->products_count ?? $this->products()->where('is_active', true)->count());

        if ($this->relationLoaded('children')) {
            $childrenCount = (int) $this->children->sum(function ($child) {
                return (int) ($child->products_count ?? $child->products()->where('is_active', true)->count());
            });

            return $directCount + $childrenCount;
        }

        $childIds = $this->children()->where('is_active', true)->pluck('id');
        if ($childIds->isNotEmpty()) {
            $childrenCount = (int) Product::whereIn('category_id', $childIds)->where('is_active', true)->count();

            return $directCount + $childrenCount;
        }

        return $directCount;
    }
}
