<?php

namespace Functional\Styling\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAvatarVersionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpeg,png', 'max:10240'],
            'parameters' => ['sometimes', 'array'],
            'parameters.height_cm' => ['sometimes', 'integer', 'min:80', 'max:250'],
            'parameters.build' => ['sometimes', 'string', 'max:32'],
            'parameters.skin_tone' => ['sometimes', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }
}
