<?php

namespace Functional\Styling\Models;

use Functional\Styling\Database\Factories\AvatarFactory;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name'])]
#[UseFactory(AvatarFactory::class)]
class Avatar extends Model
{
    /** @use HasFactory<AvatarFactory> */
    use HasFactory;

    use HasUlids;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<AvatarVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(AvatarVersion::class);
    }

    /**
     * Get the version any new preview should render against.
     */
    public function currentVersion(): ?AvatarVersion
    {
        return $this->versions()->orderByDesc('version')->first();
    }

    /**
     * Get the number the next version should carry.
     */
    public function nextVersionNumber(): int
    {
        return (int) $this->versions()->max('version') + 1;
    }
}
