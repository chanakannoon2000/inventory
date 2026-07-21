<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'product_group_id',
        'size_label',
        'barcode',
        'category_id',
        'unit_id',
        'supplier_id',
        'cost_price',
        'sell_price',
        'image_url',
        'stock',
        'min_stock',
        'max_stock',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'sell_price' => 'decimal:2',
            'stock' => 'decimal:2',
            'min_stock' => 'decimal:2',
            'max_stock' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function productGroup(): BelongsTo
    {
        return $this->belongsTo(ProductGroup::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeSearch($query, ?string $keyword)
    {
        if (! $keyword) {
            return $query;
        }

        return $query->where(function ($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('barcode', 'like', "%{$keyword}%");
        });
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<=', 'min_stock');
    }

    public function scopeOverStock($query)
    {
        return $query->whereColumn('stock', '>=', 'max_stock');
    }

    public function isLowStock(): bool
    {
        return (float) $this->stock <= (float) $this->min_stock;
    }

    public function isOverStock(): bool
    {
        return (float) $this->stock >= (float) $this->max_stock;
    }

    public function displayName(): string
    {
        $groupName = $this->productGroup?->name;
        if ($groupName && $this->size_label) {
            return trim($groupName.' '.$this->size_label);
        }

        return $this->name;
    }

    public function isGrouped(): bool
    {
        return (bool) $this->product_group_id;
    }

    /**
     * ค่าเรียงไซส์จากน้อย→มาก (หน่วยหุน: 1 นิ้ว = 8 หุน)
     */
    public function sizeSortValue(): float
    {
        return self::parseSizeSortValue($this->size_label ?: $this->name);
    }

    public static function parseSizeSortValue(?string $label): float
    {
        $raw = trim((string) $label);
        if ($raw === '') {
            return PHP_FLOAT_MAX;
        }

        $text = mb_strtolower($raw);
        $text = str_replace(['″', '"', '”', '“', '’', "'"], ' นิ้ว', $text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        // 1½ / 1.5 / 1 1/2 / 1-1/2
        if (preg_match('/(\d+)\s*[½]/u', $text, $m)) {
            return ((float) $m[1] + 0.5) * 8;
        }
        if (preg_match('/(\d+)\s*[¼]/u', $text, $m)) {
            return ((float) $m[1] + 0.25) * 8;
        }
        if (preg_match('/(\d+)\s*[¾]/u', $text, $m)) {
            return ((float) $m[1] + 0.75) * 8;
        }
        if (preg_match('/(\d+)\s*[- ]\s*1\s*\/\s*2/u', $text, $m)) {
            return ((float) $m[1] + 0.5) * 8;
        }
        if (preg_match('/(\d+)\s*[- ]\s*1\s*\/\s*4/u', $text, $m)) {
            return ((float) $m[1] + 0.25) * 8;
        }
        if (preg_match('/(\d+)\s*[- ]\s*3\s*\/\s*4/u', $text, $m)) {
            return ((float) $m[1] + 0.75) * 8;
        }

        // 3 นิ้วครึ่ง / 1นิ้วครึ่ง
        if (preg_match('/(\d+(?:\.\d+)?)\s*นิ้ว\s*ครึ่ง/u', $text, $m)) {
            return ((float) $m[1] + 0.5) * 8;
        }

        // 4 หุน / 6หุน
        if (preg_match('/(\d+(?:\.\d+)?)\s*หุน/u', $text, $m)) {
            return (float) $m[1];
        }

        // 1 นิ้ว / 2นิ้ว / 4"
        if (preg_match('/(\d+(?:\.\d+)?)\s*นิ้ว/u', $text, $m)) {
            return (float) $m[1] * 8;
        }

        // ตัวเลขล้วน เช่น 0.5, 1, 2
        if (preg_match('/^(\d+(?:\.\d+)?)$/u', $text, $m)) {
            $n = (float) $m[1];
            // ค่าเล็กกว่า 8 น่าจะเป็นนิ้ว; ถ้าเป็นเลขใหญ่แบบบาร์โค้ดไม่ใช้
            return $n < 20 ? $n * 8 : $n;
        }

        // fallback: ดึงเลขแรก แล้วถ้ามีคำว่านิ้วให้คูณ 8
        if (preg_match('/(\d+(?:\.\d+)?)/u', $text, $m)) {
            $n = (float) $m[1];
            if (str_contains($text, 'นิ้ว') || str_contains($text, 'inch')) {
                return $n * 8;
            }
            if (str_contains($text, 'หุน')) {
                return $n;
            }

            return $n * 8;
        }

        return PHP_FLOAT_MAX;
    }

    public function imageSrc(): ?string
    {
        if (! $this->image_url) {
            return null;
        }

        if (str_starts_with($this->image_url, 'http://') || str_starts_with($this->image_url, 'https://')) {
            return $this->image_url;
        }

        return asset('storage/'.$this->image_url);
    }

    public function placeholderIcon(): string
    {
        return $this->category?->icon ?: '📦';
    }

    public function placeholderColor(): string
    {
        return $this->category?->color ?: '#E3DFD3';
    }
}
