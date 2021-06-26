<?php
declare (strict_types = 1);

namespace app\middleware;

use Closure;
use thans\jwt\facade\JWTAuth;
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
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->server()['REQUEST_URI'] == '/apidoc/config') {
            return $next($request);
        }
        if ($request->server()['REQUEST_URI'] == '/apidoc/verifyAuth') {
            return $next($request);
        }
        if ($request->server()['REQUEST_URI'] == '/apidoc/apiData') {
            return $next($request);
        }
        $response = $next($request);
        if($request->server()['REQUEST_URI']=="/api/login/SignLogin"){
            return $response;
        }
        if ($request->server()['REQUEST_URI'] == '/applets/index/index'){
            return $response;
        }
        if ($request->server()['REQUEST_URI'] == '/applets/index/tabBar'){
            return $response;
        }
        if ($request->server()['REQUEST_URI'] == '/applets/product/detail'){
            return $response;
        }
        if($request->server()['REQUEST_URI']!="/api/login/login"){
            $response->header(["Authorization"=>"Bearer ".JWTAuth::refresh(),'Access-Control-Expose-Headers'=>"Authorization"]);
        }
        return $response;
    }
}
