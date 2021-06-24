<?php
declare (strict_types = 1);

namespace app\middleware;
use app\common\model\ErrorLog;
use Closure;
use thans\jwt\facade\JWTAuth;
<<<<<<< HEAD
=======

>>>>>>> 58431788394ba85ba1bb173852eb8dbecb449178
class Auth
{
    private $array = [
        '0' => 'api',
        '1' => 'admin',
        '2' => 'platform',
        '3' => 'scenic',
        '4' => 'line',
        '5' => 'user',
        '6'=>'applets'
    ];

    /**
     * 处理请求
     * @param  $request
     * @param Closure $next
     * @return mixed $next
     */
    public function handle($request, Closure $next)
    {
<<<<<<< HEAD
        if ($request['s'] == '/apidoc/config'){
=======
        if ($request->server()['REQUEST_URI'] == '/apidoc/config') {
>>>>>>> 58431788394ba85ba1bb173852eb8dbecb449178
            return $next($request);
        }
        if ($request->server()['REQUEST_URI'] == '/apidoc/data') {
            return $next($request);
        }
        if ($request->server()['REQUEST_URI'] == '/apidoc/verifyAuth') {
            return $next($request);
        }
        if ($request->server()['REQUEST_URI'] == '/apidoc/apiData') {
            return $next($request);
        }
        if ($request->server()['REQUEST_URI'] == "/api/login/SignLogin") {
            return $next($request);
        }
        if ($request->server()['REQUEST_URI'] == "/pay/service/service") {
            return $next($request);
        }
        if ($request['s'] == '/applets/index/index'){
            return $next($request);
        }
        if ($request['s'] == '/applets/index/tabBar'){
            return $next($request);
        }
        if ($request['s'] == '/applets/product/detail'){
            return $response;
        }

        if ($request->server()['REQUEST_URI'] != "/api/login/login") {
            $auth = JWTAuth::auth();
            if (isset($auth['code1'])) {
                if ($auth['code1'] == 1) {
                    ErrorLog::create(["creat_time" => time(), 'date' => json_encode($request->server()), 'ip' => getIp(1111)['ip']]);
                    return returnData(['code' => 404, 'msg' => '51无权限访问，访问有记录!请谨慎！']);
                }
            }
            $modular = explode("/", $request->server()['REQUEST_URI'])[1];
            if ($modular != 'api' && $modular != "pay") {
                if ($this->array[$auth['type']->getValue()] != $modular) {
                    ErrorLog::create(["creat_time" => time(), 'date' => json_encode($request->server()), 'ip' => getIp(1111)['ip']]);
                    return returnData(['code' => 404, 'msg' => '无权限访问，访问有记录!请谨慎！']);
                }
            }
        }
        return $next($request);
    }
}
