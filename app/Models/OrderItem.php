<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $guarded = [];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** May be null: the product can be deleted long after the sale. */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
