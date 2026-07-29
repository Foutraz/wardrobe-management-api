<?php

namespace Functional\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MergeProductsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'into' => ['required', 'ulid', 'exists:products,id', 'different:product'],
        ];
    }
}
