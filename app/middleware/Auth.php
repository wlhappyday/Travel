<?php
declare (strict_types = 1);

namespace app\middleware;

use thans\jwt\facade\JWTAuth;

class Auth
{
    private $newToken;
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        if($request->server()['REQUEST_URI']!="/api/login/login"){
                JWTAuth::auth();
        }
        return $next($request);
    }
}
