<?php

namespace App\Http\Middleware;

use App\Utils\Response as UtilsResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VendoraDomainMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    protected $allowedRoutes = [
        '/',
        'api/appSettings/create',
        'api/appSettings/getAppSettings',
        'api/vendor/register',
        'api/vendor/login',
        'api/vendor/verify',
        'api/vendor/get',
        'api/vendor/update',
        'api/vendor/updatePassword',
        'api/vendor/verifyEmail',
        'api/vendor/resendVerificationLink',
        'api/vendor/fetch',
        'api/vendor/vendorStore',
        'api/vendor/vendorProfile',
        'api/vendor/updateStore',
        'api/product/create',
        'api/product/get',
        'api/vendor/category',
        'api/vendor/subCategory',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getHost() === 'app.dev.vendora.sas.com') {
            $route = $request->path();

            if (!in_array($route, $this->allowedRoutes)) {
                return response()->json(['error' => 'Unauthorized access'], 403);
            }
        }
        return $next($request);
    }
}
