<?php

namespace Functional\Wardrobe\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadWishlistImageRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:10240'],
        ];
    }
}
