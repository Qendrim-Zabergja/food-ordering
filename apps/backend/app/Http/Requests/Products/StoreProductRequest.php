<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The client sends the category by uuid, as `category.id`, matching the
     * relationship payload convention in the API standards. price arrives in
     * major units and is converted to cents by the controller.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category.id' => ['required', 'string', 'exists:product_categories,uuid'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'is_available' => ['nullable', 'boolean'],
        ];
    }
}
