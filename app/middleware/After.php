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
        if ($request['s'] == '/apidoc/config'){
            return $next($request);
        }
        if ($request['s'] == '/apidoc/data'){
            return $next($request);
        }
        if ($request['s'] == '/apidoc/auth'){
            return $next($request);
        }
        $response = $next($request);
        if($request->server()['REQUEST_URI']!="/api/login/login"){
            $response->header(["Authorization"=>"Bearer ".JWTAuth::refresh(),'Access-Control-Expose-Headers'=>"Authorization"   ]);
        }
        return $response;
    }
}
