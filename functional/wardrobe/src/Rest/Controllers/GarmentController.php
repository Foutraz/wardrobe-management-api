<?php

namespace Functional\Wardrobe\Rest\Controllers;

use Functional\Wardrobe\Rest\Resources\GarmentResource;
use Lomkit\Rest\Http\Controllers\Controller;

class GarmentController extends Controller
{
    public static $resource = GarmentResource::class;
}
