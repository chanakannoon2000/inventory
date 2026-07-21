<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'type',
        'qty',
        'ref_sale_id',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'ref_sale_id');
    }
}
