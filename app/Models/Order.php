<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** Lifecycle statuses, in the order they normally occur. */
    public const STATUSES = [
        'nouvelle' => 'Nouvelle',
        'confirmee' => 'Confirmée',
        'preparation' => 'En préparation',
        'expediee' => 'Expédiée',
        'livree' => 'Livrée',
        'annulee' => 'Annulée',
    ];

    /** Statuses that mean the stock is no longer committed to this order. */
    public const RELEASED_STATUSES = ['annulee'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['cancelled_at' => 'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->latest();
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isCancelled(): bool
    {
        return $this->status === 'annulee';
    }

    public function customerName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /** Record a timeline entry. Every status change must leave one behind. */
    public function recordEvent(string $label, string $actor = 'Site web', ?int $userId = null): OrderEvent
    {
        return $this->events()->create([
            'label' => $label,
            'actor' => $actor,
            'user_id' => $userId,
        ]);
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }
}
