<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    public const CATEGORIES = [
        'อาหาร/กับข้าว',
        'ของใช้ร้าน',
        'ค่าเดินทาง/น้ำมัน',
        'ค่าน้ำ/ค่าไฟ',
        'ค่าซ่อม/บำรุง',
        'อื่นๆ',
    ];

    protected $fillable = [
        'user_id',
        'spent_at',
        'category',
        'title',
        'amount',
        'payment_method',
        'paid_by_name',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'spent_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethodLabel(): string
    {
        return $this->payment_method === 'transfer' ? 'โอน' : 'เงินสด';
    }
}
