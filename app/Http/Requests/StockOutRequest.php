<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'destination' => ['required', 'string', 'max:100'],
            'reference_number' => ['required', 'string', 'max:100'],
            'recipient_name' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:500'],
            'proof_image' => ['nullable', 'image', 'max:5120'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Pilih artikel baju yang akan dikeluarkan.',
            'destination.required' => 'Tujuan pengeluaran barang wajib dipilih.',
            'reference_number.required' => 'Nomor Surat Jalan / Resi Pengiriman wajib diisi.',
            'items.required' => 'Rincian jumlah pengeluaran wajib diisi.',
        ];
    }
}
