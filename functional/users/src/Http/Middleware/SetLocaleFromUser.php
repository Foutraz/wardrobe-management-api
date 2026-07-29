<?php

namespace Functional\Users\Http\Middleware;

use Closure;
use Functional\Users\Enums\Locale;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromUser
{
    /**
     * Answer each request in the language the signed-in account chose.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale;

        if ($locale instanceof Locale) {
            app()->setLocale($locale->value);
        }

        return $next($request);
    }
}
