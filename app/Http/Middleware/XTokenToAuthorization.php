<?php

namespace App\Http\Middleware;

use Closure;

class XTokenToAuthorization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next){
        if ($request->headers->has('x-token')) {
            $request->headers->set('Authorization', "Bearer "+$request->header('x-token'));
        }

        return $next($request);
    }
}
