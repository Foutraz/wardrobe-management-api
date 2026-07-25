<?php

namespace Functional\Wardrobe\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadGarmentPhotoRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:10240'],
        ];
    }
}
