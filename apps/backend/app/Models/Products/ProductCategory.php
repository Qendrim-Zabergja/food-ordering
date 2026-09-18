<?php

namespace App\Models\Products;

use App\Filters\Filterable;
use App\Policies\Products\ProductCategoryPolicy;
use App\Traits\HasUuid;
use Database\Factories\Products\ProductCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UsePolicy(ProductCategoryPolicy::class)]
class ProductCategory extends Model
{
    /** @use HasFactory<ProductCategoryFactory> */
    use Filterable, HasFactory, HasUuid, SoftDeletes;

    /**
     * uuid is deliberately absent: it is assigned by HasUuid and must never be
     * settable from a request payload.
     */
    protected $fillable = [
        'name',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
