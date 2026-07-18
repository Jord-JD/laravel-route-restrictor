# 🚫 Laravel Route Restrictor

Laravel Route Restrictor is middleware designed to restrict an entire site or specific routes using HTTP Basic Authentication. It is compatible with Laravel 5.1 through 13.

## Setup

1. Run `composer require jord-jd/laravel-route-restrictor`.
2. On Laravel 5.4 and earlier, add `JordJD\LaravelRouteRestrictor\Providers\LaravelRouteRestrictorServiceProvider::class` to the `$providers` array in your `config/app.php` file. Newer Laravel versions discover it automatically.
3. Run `php artisan vendor:publish --provider="JordJD\LaravelRouteRestrictor\Providers\LaravelRouteRestrictorServiceProvider"`.
4. Add `\JordJD\LaravelRouteRestrictor\Http\Middleware\BasicAuthentication::class` to the `$middleware` array in your `app/Http/Kernel.php` file.
5. Add `'routeRestrictor' => \JordJD\LaravelRouteRestrictor\Http\Middleware\BasicAuthentication::class` to the `$routeMiddleware` array in your `app/Http/Kernel.php` file.
6. If a CGI/FastCGI server does not forward the `Authorization` header, configure the server to forward it. For Apache, add `RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]` immediately below `RewriteEngine On` in `public/.htaccess`.

The middleware reads credentials from the request rather than PHP globals, so it also works safely with long-running application servers and test clients.

## Global restriction

In order to restrict all routes in your Laravel application, just add the global username and password to your `.env` file as follows. Ensure you change the `username` and `password` values.

```
ROUTE_RESTRICTOR_GLOBAL_USERNAME=username
ROUTE_RESTRICTOR_GLOBAL_PASSWORD=password
```

Your entire application will then be protected by these details, unless a route specific restriction is in place.

Alternatively, you can modify the global restriction username and password in your `config/laravel-route-restrictor.php` configuration file.

## Restricting specific routes

To restrict specific routes, you must edit your routes file. Simply surround the route or routes you want to restrict with the following route group code. Ensure you change the `username` and `password` middleware parameters.

```php
Route::group(['middleware' => 'routeRestrictor:username,password'], function () {
    // Route(s) to restrict go here
});
```

Note: If you have both route specific restrictions and a global restriction, both will work, but route specific restrictions will take priority.

## Excluding specific routes from restriction

If you wish to exclude one or more routes from restriction, you must edit your routes file. Simply surround the route or routes you want to exclude with the following route group code.

```php
Route::group(['middleware' => 'routeRestrictor:disable'], function () {
    // Route(s) to exclude from restriction go here
});
```
