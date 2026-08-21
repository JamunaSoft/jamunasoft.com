<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Any request whose Host is not this site (a customer domain pointed at this
 * server before its website exists) gets the branded parked page instead of
 * the Jamuna Soft website.
 */
class ServeParkedDomainPage
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());
        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        $ownHosts = [$appHost, 'www.'.$appHost, 'localhost', '127.0.0.1'];

        if ($appHost === '' || in_array($host, $ownHosts, true) || filter_var($host, FILTER_VALIDATE_IP)) {
            return $next($request);
        }

        return response()->view('domains.parked', ['host' => $host]);
    }
}
