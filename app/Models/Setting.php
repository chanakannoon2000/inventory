<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'shop_name',
        'shop_logo',
        'shop_tax_id',
        'shop_address',
        'shop_phone',
        'cipher_key',
        'promptpay_id',
        'bank_name',
        'bank_account_name',
        'bank_account_no',
        'line_enabled',
        'line_channel_token',
        'line_target_id',
        'tax_rate',
        'receipt_running',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:2',
            'receipt_running' => 'integer',
            'line_enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'shop_name' => 'ร้านช่างระบบวัสดุก่อสร้าง',
            'shop_logo' => null,
            'shop_tax_id' => null,
            'shop_address' => null,
            'shop_phone' => null,
            'cipher_key' => 'MONEYCRAFT',
            'promptpay_id' => '0800562377',
            'bank_name' => null,
            'bank_account_name' => null,
            'bank_account_no' => null,
            'line_enabled' => false,
            'line_channel_token' => null,
            'line_target_id' => null,
            'tax_rate' => 7,
            'receipt_running' => 1000,
        ]);
    }

    public function hasBankAccount(): bool
    {
        return filled($this->bank_name) && filled($this->bank_account_no);
    }

    public function logoSrc(): ?string
    {
        if (! $this->shop_logo) {
            return null;
        }

        if (str_starts_with($this->shop_logo, 'http://') || str_starts_with($this->shop_logo, 'https://')) {
            return $this->shop_logo;
        }

        return asset('storage/'.$this->shop_logo);
    }
}
