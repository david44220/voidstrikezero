<?php

declare(strict_types=1);

namespace App\Localization;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Auth\AuthService;

class LocaleMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        $supported = config('app.supported_locales', ['en', 'fr']);
        $session = Session::getInstance();

        // 1. Check query parameter ?lang=fr
        $queryLang = $request->query('lang');
        if ($queryLang && in_array($queryLang, $supported, true)) {
            $session->set('locale', $queryLang);
            Translator::setLocale($queryLang);

            // If user is logged in, persist to database
            $user = AuthService::user();
            if ($user) {
                $user->preferred_locale = $queryLang;
                $user->save();
            }

            $response = $next($request);
            $response->cookie('voidstrike_locale', $queryLang, time() + 31536000, '/', null, false, false, 'Lax');
            return $response;
        }

        // 2. Check session
        $sessionLang = $session->get('locale');
        if ($sessionLang && in_array($sessionLang, $supported, true)) {
            Translator::setLocale($sessionLang);
            return $next($request);
        }

        // 3. Check cookie
        $cookieLang = $request->cookie('voidstrike_locale');
        if ($cookieLang && in_array($cookieLang, $supported, true)) {
            Translator::setLocale($cookieLang);
            $session->set('locale', $cookieLang);
            return $next($request);
        }

        // 4. Check user profile if logged in
        $user = AuthService::user();
        if ($user && in_array($user->preferred_locale, $supported, true)) {
            Translator::setLocale($user->preferred_locale);
            $session->set('locale', $user->preferred_locale);
            return $next($request);
        }

        // 5. Default
        Translator::setLocale(config('app.locale', 'en'));
        return $next($request);
    }
}
