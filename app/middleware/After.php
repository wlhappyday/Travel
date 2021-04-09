<?php
declare (strict_types = 1);

namespace app\middleware;

use thans\jwt\facade\JWTAuth;

class After
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        if ($request->server()['REQUEST_URI']!="/apidoc/config"){
            return $next($request);
        }
        if ($request->server()['REQUEST_URI']!="/data"){
            return $next($request);
        }
        $response = $next($request);
        if($request->server()['REQUEST_URI']!="/api/login/login"){
            $response->header(["Authorization"=>"Bearer ".JWTAuth::refresh()]);
        }
        return $response;
    }
}
