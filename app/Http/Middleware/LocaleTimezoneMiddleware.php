<?php

namespace App\Http\Middleware;

use Closure;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class LocaleTimezoneMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = trim((string) $request->headers->get('X-Locale', config('app.locale', 'en')));
        $timezone = trim((string) $request->headers->get('X-Timezone', config('app.timezone', 'UTC')));

        if ($locale !== '') {
            App::setLocale($locale);
            $request->attributes->set('locale', $locale);
        }

        if ($this->isValidTimezone($timezone)) {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
            $request->attributes->set('timezone', $timezone);
        }

        return $next($request);
    }

    private function isValidTimezone(string $timezone): bool
    {
        return in_array($timezone, DateTimeZone::listIdentifiers(), true);
    }
}
