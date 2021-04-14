<?php
declare (strict_types = 1);

namespace app\middleware;

use Closure;
use think\Request;

class After
{
    /**
     * 处理请求
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle($request, Closure $next)
    {
        if ($request['s'] == '/apidoc/config') {
            return $next($request);
        }
        if ($request['s'] == '/apidoc/data') {
            return $next($request);
        }
        if ($request['s'] == '/apidoc/auth') {
            return $next($request);
        }
        $response = $next($request);
<<<<<<< HEAD
        if($request->server()['REQUEST_URI']!="/api/login/login"){
            $response->header(["Authorization"=>"Bearer ".JWTAuth::refresh(),'Access-Control-Expose-Headers'=>"Authorization"   ]);
        }
=======
//
//        if($request->server()['REQUEST_URI']!="/api/login/login"){
//            $response->header(["Authorization"=>"Bearer ".JWTAuth::refresh(),'Access-Control-Expose-Headers'=>"Authorization"]);
//        }
>>>>>>> 1d60d9487a575e51b12943ce63dab7df264506df
        return $response;
    }
}
