<?php

namespace App\Models\Products;

use App\Filters\Filterable;
use App\Policies\Products\ProductPolicy;
use App\Traits\HasUuid;
use Database\Factories\Products\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UsePolicy(ProductPolicy::class)]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use Filterable, HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'product_category_id',
        'name',
        'description',
        'price_cents',
        'image_url',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'is_available' => 'boolean',
        ];
    }

    /**
     * The price in major units, for display and for API payloads. The stored
     * column stays in cents - this is a read-only convenience only.
     */
    protected function price(): Attribute
    {
        return Attribute::get(fn (): float => round($this->price_cents / 100, 2));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }
}
