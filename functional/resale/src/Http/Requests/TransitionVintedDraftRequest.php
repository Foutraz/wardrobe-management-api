<?php

namespace Functional\Resale\Http\Requests;

use Functional\Resale\Enums\VintedDraftStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionVintedDraftRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(VintedDraftStatus::class)],
        ];
    }
}
