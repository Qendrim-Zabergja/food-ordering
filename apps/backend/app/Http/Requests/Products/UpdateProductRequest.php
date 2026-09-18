<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category.id' => ['sometimes', 'required', 'string', 'exists:product_categories,uuid'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:99999.99'],
            'image_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'is_available' => ['sometimes', 'boolean'],
        ];
    }
}
