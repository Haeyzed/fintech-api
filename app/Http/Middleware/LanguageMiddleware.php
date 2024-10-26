<?php

namespace App\Http\Middleware;

use App\Helpers\TranslateTextHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LanguageMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the language from the request header, or use the default app locale if not provided
        $language = $request->header('Accept-Language', config('app.locale'));

        // If the language is not one of the available locales, fallback to the default locale
        if (! in_array($language, config('app.available_locales'))) {
            $language = config('app.fallback_locale');
        }

        // Set the translation source language to English and target language to the requested language
        TranslateTextHelper::setSource('en')->setTarget($language);

        return $next($request);
    }
}
