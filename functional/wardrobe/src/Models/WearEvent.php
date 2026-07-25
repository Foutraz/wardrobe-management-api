<?php

namespace Functional\Wardrobe\Models;

use Functional\Wardrobe\Database\Factories\WearEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['garment_id', 'worn_on'])]
#[UseFactory(WearEventFactory::class)]
class WearEvent extends Model
{
    use HasFactory, HasUlids;

    public function garment(): BelongsTo
    {
        return $this->belongsTo(Garment::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'worn_on' => 'date',
        ];
    }
}
