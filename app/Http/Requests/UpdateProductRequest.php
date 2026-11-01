<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'exists:categories,id'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'new_sizes' => ['nullable', 'array'],
            'new_sizes.*' => ['string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama artikel baju wajib diisi.',
            'category_id.required' => 'Kategori baju wajib dipilih.',
            'selling_price.required' => 'Harga jual retail wajib diisi.',
        ];
    }
}
