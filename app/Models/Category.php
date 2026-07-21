<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'barcode_prefix', 'icon', 'color'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function barcodePrefixLetter(): string
    {
        $prefix = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $this->barcode_prefix) ?: '');

        return $prefix !== '' ? substr($prefix, 0, 1) : '';
    }
}
