<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAccount extends Model
{
    protected $fillable = [
        'label',
        'type',
        'promptpay_id',
        'bank_name',
        'bank_account_name',
        'bank_account_no',
        'is_enabled',
        'is_default',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function isPromptPay(): bool
    {
        return $this->type === 'promptpay';
    }

    public function isBank(): bool
    {
        return $this->type === 'bank';
    }

    public function displayTitle(): string
    {
        if ($this->label) {
            return $this->label;
        }

        return $this->isPromptPay()
            ? 'พร้อมเพย์ '.($this->promptpay_id ?: '')
            : trim(($this->bank_name ?: 'บัญชีธนาคาร').' '.($this->bank_account_no ?: ''));
    }

    public static function defaultAccount(): ?self
    {
        return static::enabled()->where('is_default', true)->orderBy('id')->first()
            ?: static::enabled()->orderBy('sort_order')->orderBy('id')->first();
    }

    public static function makeDefault(self $account): void
    {
        static::query()->update(['is_default' => false]);
        $account->update([
            'is_default' => true,
            'is_enabled' => true,
        ]);
    }
}
