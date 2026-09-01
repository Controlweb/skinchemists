<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    public const STATUSES = [
        'en_attente' => 'En attente',
        'approuve' => 'Approuvé',
        'rejete' => 'Rejeté',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['verified' => 'boolean', 'featured' => 'boolean'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Only approved reviews are ever shown on the storefront. */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approuve');
    }
}
