<?php

namespace Functional\Wardrobe\Http\Controllers;

use Functional\Users\Enums\Ability;
use Functional\Wardrobe\Services\WardrobeStatistics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ShowWardrobeStatisticsController
{
    /**
     * Report the figures that tell an owner whether their wardrobe is earning its keep.
     */
    public function __invoke(Request $request, WardrobeStatistics $statistics): JsonResponse
    {
        Gate::authorize(Ability::ViewGarment->value);

        return new JsonResponse([
            'data' => $statistics->for((string) $request->user()?->getAuthIdentifier()),
        ]);
    }
}
