<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Only delivery details. No items, no prices, no total - those come from
     * the user's cart on the server. There is nothing a client could send here
     * that would change what the order costs.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'delivery_address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
