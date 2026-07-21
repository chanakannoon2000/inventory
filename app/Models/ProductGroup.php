<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductGroup extends Model
{
    protected $fillable = ['name', 'image_url'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
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
}
