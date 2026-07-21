<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'receipt_no',
        'user_id',
        'sold_at',
        'subtotal',
        'discount',
        'total',
        'paid',
        'change_amount',
        'payment_method',
        'customer_name',
        'customer_tax_id',
        'customer_address',
        'customer_phone',
        'vat_rate',
        'net_amount',
        'vat_amount',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'sold_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'vat_amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('cancelled_at');
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function recalculateTotals(): void
    {
        $this->load('items');
        $subtotal = round((float) $this->items->sum(fn ($item) => $item->lineTotal()), 2);
        $discount = min((float) $this->discount, $subtotal);
        $total = max(0, round($subtotal - $discount, 2));
        $vatParts = self::splitVat($total, (float) $this->vat_rate);

        $paid = (float) $this->paid;
        if (in_array($this->payment_method, ['promptpay', 'bank'], true)) {
            $paid = $total;
        } elseif ($paid < $total) {
            $paid = $total;
        }

        $this->update([
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'paid' => $paid,
            'change_amount' => max(0, round($paid - $total, 2)),
            'net_amount' => $vatParts['net'],
            'vat_amount' => $vatParts['vat'],
        ]);
    }

    public function costTotal(): float
    {
        return (float) $this->items->sum(fn ($item) => (float) $item->cost_price * (float) $item->qty);
    }

    /** คำนวณยอดก่อน VAT / VAT จากยอดรวมที่รวม VAT แล้ว */
    public static function splitVat(float $grossTotal, float $vatRate): array
    {
        $rate = max(0, $vatRate);
        if ($rate <= 0) {
            return ['net' => round($grossTotal, 2), 'vat' => 0.0];
        }

        $net = round($grossTotal / (1 + ($rate / 100)), 2);
        $vat = round($grossTotal - $net, 2);

        return ['net' => $net, 'vat' => $vat];
    }
}
