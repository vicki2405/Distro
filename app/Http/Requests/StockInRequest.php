<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'reference_number' => ['required', 'string', 'max:100'],
            'supplier_name' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:500'],
            'proof_image' => ['nullable', 'image', 'max:5120'], // Max 5MB
            'items' => ['required', 'array', 'min:1'],
            'items.*.size' => ['required', 'string', 'max:10'],
            'items.*.quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Pilih artikel baju yang diterima dari konveksi.',
            'product_id.exists' => 'Artikel baju yang dipilih tidak terdaftar di sistem.',
            'reference_number.required' => 'Nomor Surat Jalan atau Nota Konveksi wajib diisi.',
            'items.required' => 'Rincian jumlah per ukuran wajib diisi.',
            'proof_image.image' => 'Berkas bukti fisik harus berupa gambar (JPG, PNG, atau WEBP).',
            'proof_image.max' => 'Ukuran foto bukti fisik maksimal 5 MB.',
        ];
    }
}
