<?php
declare (strict_types = 1);

namespace app\middleware;

use thans\jwt\facade\JWTAuth;
use \Closure;
class Auth
{
    /**
     * 处理请求
     *
     * @param  $request
     * @param Closure $next
     * @return mixed $next
     */
    public function handle($request, Closure $next)
    {
//        if($request->server()['REQUEST_URI']!="/api/login/login"){
//                JWTAuth::auth();
//        }
        return $next($request);
    }
}
