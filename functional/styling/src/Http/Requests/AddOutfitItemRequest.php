<?php

namespace Functional\Styling\Http\Requests;

use Functional\Styling\Enums\OutfitSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddOutfitItemRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'garment_id' => ['required_without:wishlist_item_id', 'missing_with:wishlist_item_id', 'ulid', 'exists:garments,id'],
            'wishlist_item_id' => ['required_without:garment_id', 'missing_with:garment_id', 'ulid', 'exists:wishlist_items,id'],
            'slot' => ['required', Rule::enum(OutfitSlot::class)],
            'sort' => ['sometimes', 'integer', 'min:0', 'max:999'],
        ];
    }
}
