<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['benefits' => 'array', 'is_published' => 'boolean'];
    }

    /** Products are matched on the name, which is how the catalogue stores it. */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'ingredient', 'name');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
