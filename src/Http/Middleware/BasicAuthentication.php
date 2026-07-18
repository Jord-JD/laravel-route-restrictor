<?php

namespace JordJD\LaravelRouteRestrictor\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * This is the basic authentication class.
 *
 * @author Jordan Hall <jordan.hall@rapidweb.biz>
 * @author James Brooks <james@alt-three.com>
 */
class BasicAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param string                   $routeUsername
     * @param string                   $routePassword
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $routeUsername = null, $routePassword = null)
    {
        // Disable middleware if requested.
        if ($routeUsername == 'disable' || $routePassword == 'disable') {
            return $next($request);
        }

        list($username, $password) = $this->requestCredentials($request);

        if (!$this->validate($username, $password, $routeUsername, $routePassword)) {
            throw new UnauthorizedHttpException('Basic', 'Unauthorized. Please check your username and password.');
        }

        return $next($request);
    }

    /**
     * Validates the user, password combination against the request.
     *
     * @param string                   $user
     * @param string                   $password
     * @param string                   $routeUsername
     * @param string                   $routePassword
     *
     * @return bool
     */
    protected function validate($user, $password, $routeUsername = null, $routePassword = null)
    {
        if ($routeUsername !== null || $routePassword !== null) {
            return $routeUsername !== null &&
                $routePassword !== null &&
                $this->safeEquals($routeUsername, trim($user)) &&
                $this->safeEquals($routePassword, trim($password));
        }

        $globalUsername = config('laravel-route-restrictor.global.username');
        $globalPassword = config('laravel-route-restrictor.global.password');

        if ($globalUsername !== null && $globalUsername !== '' && $globalPassword !== null && $globalPassword !== '') {
            return $this->safeEquals($globalUsername, trim($user)) &&
                $this->safeEquals($globalPassword, trim($password));
        }

        return true;
    }

    /**
     * Read Basic Auth credentials from the request, including CGI/FastCGI
     * servers that only expose the raw Authorization header.
     *
     * @param Request $request
     * @return array
     */
    private function requestCredentials(Request $request)
    {
        $username = $request->getUser();
        $password = $request->getPassword();

        if ($username !== null || $password !== null) {
            return array((string) $username, (string) $password);
        }

        $authorization = $request->headers->get('Authorization');
        if (!$authorization && $request->server->has('REDIRECT_REMOTE_USER')) {
            $authorization = $request->server->get('REDIRECT_REMOTE_USER');
        }

        if (is_string($authorization) && stripos($authorization, 'Basic ') === 0) {
            $decoded = base64_decode(substr($authorization, 6), true);
            if ($decoded !== false && strpos($decoded, ':') !== false) {
                return explode(':', $decoded, 2);
            }
        }

        return array('', '');
    }

    /**
     * Compare credentials without leaking their matching prefix.
     *
     * @param string $known
     * @param string $provided
     * @return bool
     */
    private function safeEquals($known, $provided)
    {
        $known = (string) $known;
        $provided = (string) $provided;

        if (function_exists('hash_equals')) {
            return hash_equals($known, $provided);
        }

        if (strlen($known) !== strlen($provided)) {
            return false;
        }

        $difference = 0;
        for ($index = 0, $length = strlen($known); $index < $length; $index++) {
            $difference |= ord($known[$index]) ^ ord($provided[$index]);
        }

        return $difference === 0;
    }
}
