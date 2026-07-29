<?php

namespace Functional\Identification\Http\Requests;

use Functional\Wardrobe\Enums\GarmentCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmIdentificationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'ulid', 'exists:categories,id'],
            'brand_id' => ['sometimes', 'nullable', 'ulid', 'exists:brands,id'],
            'condition' => ['required', Rule::enum(GarmentCondition::class)],
            'size_label' => ['sometimes', 'nullable', 'string', 'max:32'],
            'colour_name' => ['sometimes', 'nullable', 'string', 'max:64'],
            'colour_hex' => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'material_composition' => ['sometimes', 'nullable', 'string', 'max:255'],
            'purchase_price_cents' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'purchase_currency' => ['sometimes', 'nullable', 'string', 'size:3'],
        ];
    }
}
