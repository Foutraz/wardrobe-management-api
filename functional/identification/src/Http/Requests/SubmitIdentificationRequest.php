<?php

namespace Functional\Identification\Http\Requests;

use Functional\Identification\Enums\IdentificationKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitIdentificationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::enum(IdentificationKind::class)],
            'barcode' => ['required_if:kind,barcode', 'missing_unless:kind,barcode', 'digits_between:8,14'],
            'image' => ['required_unless:kind,barcode', 'missing_if:kind,barcode', 'image', 'mimes:jpeg,png,webp', 'max:10240'],
        ];
    }
}
