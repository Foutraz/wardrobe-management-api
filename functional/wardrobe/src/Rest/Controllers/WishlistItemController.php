<?php

namespace Functional\Wardrobe\Rest\Controllers;

use Functional\Wardrobe\Rest\Resources\WishlistItemResource;
use Lomkit\Rest\Http\Controllers\Controller;

class WishlistItemController extends Controller
{
    public static $resource = WishlistItemResource::class;
}
