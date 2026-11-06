<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'code',
        'name',
        'cost_price',
        'selling_price',
        'description',
        'rack_location',
        'image',
    ];

    /**
     * Sembunyikan HPP Modal (cost_price) dari serialisasi publik/staf secara default.
     * Hanya Owner yang berhak melihat HPP Modal melalui makeVisible('cost_price').
     */
    protected $hidden = [
        'cost_price',
    ];

    protected $appends = [
        'image_url',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {
            if (str_starts_with($this->image, 'http') || Storage::disk('public')->exists($this->image)) {
                return str_starts_with($this->image, 'http')
                    ? $this->image
                    : '/storage/'.$this->image;
            }
        }

        if ($this->relationLoaded('images') && $this->images->isNotEmpty()) {
            $validImage = $this->images->first(function ($img) {
                return str_starts_with($img->image_path, 'http') || Storage::disk('public')->exists($img->image_path);
            });

            if ($validImage) {
                return $validImage->url;
            }
        }

        return null;
    }

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('order')->orderBy('id');
    }
}
