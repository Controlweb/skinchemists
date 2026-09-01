<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** Usable right now, ignoring cart contents. */
    public function isRedeemable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return $this->max_uses === null || $this->uses < $this->max_uses;
    }

    /** Discount in centimes for a given subtotal. Shipping promos discount nothing here. */
    public function discountFor(int $subtotalCents): int
    {
        if (! $this->isRedeemable() || $subtotalCents < $this->min_subtotal_cents) {
            return 0;
        }

        return match ($this->type) {
            'percent' => (int) round($subtotalCents * $this->value / 100),
            'fixed' => min($this->value, $subtotalCents),
            default => 0,
        };
    }

    public function grantsFreeShipping(int $subtotalCents): bool
    {
        return $this->type === 'free_shipping'
            && $this->isRedeemable()
            && $subtotalCents >= $this->min_subtotal_cents;
    }
}
