<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'product_name',
        'qty',
        'unit_price',
        'cost_price',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lineTotal(): float
    {
        return (float) $this->unit_price * (float) $this->qty;
    }
}
