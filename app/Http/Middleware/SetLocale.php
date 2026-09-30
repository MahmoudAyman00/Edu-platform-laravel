<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Priority: Accept-Language header -> user.locale -> default (ar).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request);
        app()->setLocale($locale);

        return $next($request);
    }

    protected function resolve(Request $request): string
    {
        $supported = ['ar', 'en'];

        $header = strtolower((string) $request->header('Accept-Language', ''));
        if (str_starts_with($header, 'en')) {
            return 'en';
        }
        if (str_starts_with($header, 'ar')) {
            return 'ar';
        }

        $user = $request->user();
        if ($user && in_array($user->locale, $supported, true)) {
            return $user->locale;
        }

        return config('app.locale', 'ar');
    }
}
