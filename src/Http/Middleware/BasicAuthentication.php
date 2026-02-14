<?php

namespace JordJD\LaravelRouteRestrictor\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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
        // To prevent unit test failures, do not process restrictions if PHP is running via the command line
        if (php_sapi_name() == "cli") {
            return $next($request);
        }

        // Disable middleware is requested
        if ($routeUsername == 'disable' || $routePassword == 'disable') {
            return $next($request);
        }

        // Note: This ugly hack is required for web servers in which PHP is run
        // via a CGI handler. In these cases, PHP does not have access to the
        // $_SERVER['PHP_AUTH_USER'] and $_SERVER['PHP_AUTH_PW'] variables.
        // Therefore, we must use a .htaccess rule to rewrite the raw basic
        // authentication data into a $_SERVER variable, and then the below
        // code will convert this to the $_SERVER['PHP_AUTH_USER'] and
        // $_SERVER['PHP_AUTH_PW'] variables we need.
        // For this to work, the following line must be placed in your
        // `public/.htaccess` file under `RewriteEngine On`.
        // RewriteRule .* - [E=REMOTE_USER:%{HTTP:Authorization}]
        if (isset($_SERVER["REDIRECT_REMOTE_USER"]) && $_SERVER["REDIRECT_REMOTE_USER"] != '') {
            $d = base64_decode(substr($_SERVER["REDIRECT_REMOTE_USER"], 6));
            list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', $d);
        }

        $username = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] :  '';
        $password = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] :  '';

        if (!$this->validate($request, $username, $password, $routeUsername, $routePassword)) {
            throw new UnauthorizedHttpException('Basic', 'Unauthorized. Please check your username and password.');
        }

        return $next($request);
    }

    /**
     * Validates the user, password combination against the request.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $user
     * @param string                   $password
     * @param string                   $routeUsername
     * @param string                   $routePassword
     *
     * @return bool
     */
    protected function validate($request, $user, $password, $routeUsername = null, $routePassword = null)
    {
        // If we're dealing with a route with a route specific restriction, it takes priortity over global restriction.
        if ($this->specificRouteRestrictionExists()) {

            // If we have a route specific username and password, check against them.
            if ($routeUsername && $routePassword) {

                // Check against route password
                if (trim($user) == $routeUsername && trim($password) == $routePassword) {
                    return true;
                } else {
                    return false;
                }

            }
        }
        // Check if global username and password are set
        elseif (($globalUsername = config('laravel-route-restrictor.global.username')) && ($globalPassword = config('laravel-route-restrictor.global.password'))) {
            
            // Check against global password
            if (trim($user) == $globalUsername && trim($password) == $globalPassword) {
                return true;
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if the current route has an associated 'routeRestrictor'' middleware (i.e. a route specific restriction).
     *
     * @return bool
     */
    private function specificRouteRestrictionExists()
    {
        $requestMethod = isset($_POST['_method']) ? $_POST['_method'] : $_SERVER['REQUEST_METHOD'];
        
        $routes = app('router')->getRoutes()->match(app('request')->create($_SERVER['REQUEST_URI'], $requestMethod));


        if (!$routes) {
            throw new Exception('Error determining current route.');
        }

        if (get_class($routes) != \Illuminate\Routing\Route::class && count($routes) != 1) {
            $routes = $routes->first();
        }
        
        foreach($routes->middleware() as $routeMiddleware) {
            $routeMiddlewareParts = explode(':', $routeMiddleware);
            if ($routeMiddlewareParts[0]=='routeRestrictor') {
                return true;
            }
        }

        return false;
    }
}
