<?php

namespace App\Http\Requests\Carts;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The product arrives as a uuid under `product.id`, matching the
     * relationship payload convention. No price is accepted - the server reads
     * it from the product, so a client cannot name its own price.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product.id' => ['required', 'string', 'exists:products,uuid'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ];
    }
}
