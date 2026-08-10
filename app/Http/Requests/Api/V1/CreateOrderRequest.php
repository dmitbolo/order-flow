<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'Укажите склад для оформления заказа.',
            'warehouse_id.exists' => 'Указанный склад не существует.',
            'items.required' => 'Заказ должен содержать хотя бы один товар.',
            'items.*.product_id.required' => 'Идентификатор товара обязателен.',
            'items.*.product_id.exists' => 'Один из выбранных товаров не существует.',
            'items.*.quantity.min' => 'Количество товара должно быть не менее 1.',
        ];
    }
}
