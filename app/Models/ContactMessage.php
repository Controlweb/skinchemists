<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    public const SUBJECTS = [
        'commande' => 'Ma commande',
        'produit' => 'Conseil produit',
        'livraison' => 'Livraison',
        'retour' => 'Retour ou échange',
        'autre' => 'Autre',
    ];

    public const STATUSES = [
        'nouveau' => 'Nouveau',
        'traite' => 'Traité',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /** The order this enquiry refers to, when the number matches one. */
    public function order(): ?Order
    {
        return $this->order_number
            ? Order::where('number', $this->order_number)->first()
            : null;
    }

    public function subjectLabel(): string
    {
        return self::SUBJECTS[$this->subject] ?? $this->subject;
    }

    public function isHandled(): bool
    {
        return $this->status === 'traite';
    }

    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', 'nouveau');
    }
}
