<?php

namespace Functional\Wardrobe\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkGarmentWornRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'worn_on' => ['sometimes', 'date', 'before_or_equal:today'],
        ];
    }
}
