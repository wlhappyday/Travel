<?php
declare (strict_types = 1);

namespace app\middleware;

use Closure;
use thans\jwt\facade\JWTAuth;
use think\Request;

/**
 * Class After
 * @package app\middleware
 */
class After
{
    /**
     * 处理请求
     *
     * @param Request $request
     * @param Closure $next
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if ($request->server()['REQUEST_URI'] == "/api/login/SignLogin") {
            return $response;
        }
        if ($request->server()['REQUEST_URI'] == '/pay/pay/orderFinish') {
            return $response;
        }
        if ($request->server()['REQUEST_URI'] == '/applets/index/index') {
            return $response;
        }
        if ($request->server()['REQUEST_URI'] == '/applets/index/tabBar') {
            return $response;
        }
        if ($request->server()['REQUEST_URI'] == '/applets/product/detail') {
            return $response;
        }
        if ($request->server()['REQUEST_URI'] == '/pay/Charge/notifyurl'){
            return $response;
        }
        if($request->server()['REQUEST_URI']!="/api/login/login"){
            $response->header(["Authorization"=>"Bearer ".JWTAuth::refresh(),'Access-Control-Expose-Headers'=>"Authorization"]);
        }
        return $response;
    }
}
