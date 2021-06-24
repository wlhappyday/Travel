<?php
declare (strict_types = 1);

namespace app\middleware;
use Closure;

class Auth
{
    private $array = [
        '0' => 'api',
        '1' => 'admin',
        '2' => 'platform',
        '3' => 'scenic',
        '4' => 'line',
        '5' => 'user',
    ];

    /**
     * 处理请求
     * @param  $request
     * @param Closure $next
     * @return mixed $next
     */
    public function handle($request, Closure $next)
    {


        if ($request['s'] == '/apidoc/config'){
            return $next($request);
        }
        if ($request['s'] == '/apidoc/data'){
            return $next($request);
        }
        if ($request['s'] == '/apidoc/verifyAuth'){
            return $next($request);
        }
        if ($request['s'] == '/apidoc/apiData') {
            return $next($request);
        }
        if ($request->server()['REQUEST_URI'] != "/api/login/ceshi") {
            return $next($request);
        }
        if ($request['s'] == "/api/login/SignLogin") {
            return $next($request);
        }
        if ($request->server()['REQUEST_URI'] != "/api/login/login") {
            $auth = JWTAuth::auth();
            $modular = explode("/", $request->server()['REQUEST_URI'])[1];
            if ($modular != 'api') {
                if ($this->array[$auth['type']->getValue()] != $modular) {
                    ErrorLog::create(["creat_time" => time(), 'date' => json_encode($request->server()), 'ip' => getIp(1111)['ip']]);
                    return returnData(['code' => 404, 'msg' => '无权限访问，访问有记录!请谨慎！']);
                }
            }
        }
        return $next($request);
    }
}
