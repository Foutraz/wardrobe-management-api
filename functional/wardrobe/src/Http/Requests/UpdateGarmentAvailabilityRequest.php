<?php

namespace Functional\Wardrobe\Http\Requests;

use Functional\Wardrobe\Enums\GarmentAvailability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGarmentAvailabilityRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'availability_status' => ['required', Rule::enum(GarmentAvailability::class)],
        ];
    }
}
