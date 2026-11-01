<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            'sizes' => ['required', 'array', 'min:1'],
            'sizes.*' => ['required', 'string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama artikel baju wajib diisi.',
            'category_id.required' => 'Kategori baju wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid.',
            'selling_price.required' => 'Harga jual retail wajib diisi.',
            'sizes.required' => 'Pilih minimal satu varian ukuran baju.',
            'sizes.min' => 'Pilih minimal satu varian ukuran baju.',
        ];
    }
}
