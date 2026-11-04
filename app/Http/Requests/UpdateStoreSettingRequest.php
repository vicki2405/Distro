<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isOwner() || $user->can('settings.manage'));
    }

    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:100'],
            'store_tagline' => ['nullable', 'string', 'max:150'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:3072'],
            'remove_logo' => ['nullable', 'boolean'],
            'hero_badge' => ['nullable', 'string', 'max:150'],
            'hero_title_prefix' => ['required', 'string', 'max:150'],
            'hero_title_highlight' => ['required', 'string', 'max:150'],
            'hero_description' => ['nullable', 'string', 'max:1000'],
            'live_feed_text' => ['nullable', 'string', 'max:100'],
            'footer_text' => ['nullable', 'string', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'store_name.required' => 'Nama brand atau toko wajib diisi.',
            'hero_title_prefix.required' => 'Judul baris pertama wajib diisi.',
            'hero_title_highlight.required' => 'Judul highlight wajib diisi.',
            'logo.image' => 'File logo harus berupa gambar valid.',
            'logo.mimes' => 'Format logo harus JPG, JPEG, PNG, WEBP, atau SVG.',
            'logo.max' => 'Ukuran file logo maksimal 3MB.',
        ];
    }
}
