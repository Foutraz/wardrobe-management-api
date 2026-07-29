<?php

namespace Functional\Styling\Models;

use Functional\Styling\Database\Factories\OutfitPlanFactory;
use Functional\Styling\Policies\OutfitPlanPolicy;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Lomkit\Access\Controls\HasControl;

/**
 * @property string $id
 * @property string $user_id
 * @property string $outfit_id
 * @property Carbon $scheduled_for
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['outfit_id', 'scheduled_for'])]
#[UseFactory(OutfitPlanFactory::class)]
#[UsePolicy(OutfitPlanPolicy::class)]
class OutfitPlan extends Model
{
    use HasControl, HasUlids;

    /** @use HasFactory<OutfitPlanFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Outfit, $this>
     */
    public function outfit(): BelongsTo
    {
        return $this->belongsTo(Outfit::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_for' => 'date',
        ];
    }
}
