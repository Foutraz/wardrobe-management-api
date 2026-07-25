<?php

namespace Functional\Styling\Database\Factories;

use Functional\Styling\Enums\PreviewMode;
use Functional\Styling\Enums\RenderStatus;
use Functional\Styling\Models\Outfit;
use Functional\Styling\Models\OutfitPreview;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OutfitPreview>
 */
class OutfitPreviewFactory extends Factory
{
    protected $model = OutfitPreview::class;

    public function definition(): array
    {
        return [
            'outfit_id' => Outfit::factory(),
            'avatar_version_id' => null,
            'mode' => PreviewMode::FlatLay,
            'status' => RenderStatus::Pending,
            'cache_key' => hash('sha256', Str::lower(faker()->ulid())),
            'provider' => null,
            'cost_cents' => 0,
            'failure_reason' => null,
        ];
    }
}
