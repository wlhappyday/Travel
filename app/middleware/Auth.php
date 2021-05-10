<?php
declare (strict_types = 1);

namespace app\middleware;

use app\api\model\Admin;
use app\api\model\Juser;
use app\api\model\Padmin;
use app\api\model\Puser;
use app\api\model\Xuser;
use app\common\model\ErrorLog;
use Closure;
use thans\jwt\facade\JWTAuth;

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
//        p($request->server()['HTTP_AUTHORIZATION']);
        if ($request['s'] == '/apidoc/config') {
            return $next($request);
        }
        if ($request['s'] == '/apidoc/data') {
            return $next($request);
        }
        if ($request['s'] == '/apidoc/auth') {
            return $next($request);
        }
        if ($request->server()['REQUEST_URI'] == "/api/login/ceshi") {
            return $next($request);
        }
        header("Access-Control-Allow-Origin:*");
        header("Access-Control-Allow-Methods:GET, POST, OPTIONS, DELETE");
        header("Access-Control-Allow-Headers:token,DNT,X-Mx-ReqToken,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type, Accept-Language, Origin, Accept-Encoding");
        if ($request->server()['REQUEST_URI'] != "/api/login/login") {
            if (empty($request->server()['HTTP_AUTHORIZATION']) || !startwith($request->server()['HTTP_AUTHORIZATION'], "Bearer")) {
                return returnData(['code' => 404, 'msg' => '口令错误！']);
            }
            $auth = JWTAuth::auth();
            if (empty($auth)) {
                return returnData(['code' => 404, 'msg' => '口令验证错误！']);
            }
            $type = $auth['type']->getValue();
            $userDate = $this->yanzheng($type, $auth['id']->getValue());
            if (empty($userDate)) {
                return returnData(['code' => 404, 'msg' => '无权限访问，账户不存在！']);
            }
            if (!empty($userDate['delete_time'])) {
                return returnData(['code' => 404, 'msg' => '无权限访问，账户被强制删除！']);
            }
            if ($userDate["status"] != 0) {
                return returnData(['code' => 404, 'msg' => '无权限访问，账户已被禁用！']);
            }
            $modular = explode("/", $request->server()['REQUEST_URI'])[1];
            if ($modular != 'api') {
                if ($this->array[$type] != $modular) {
                    ErrorLog::create(["creat_time" => time(), 'date' => json_encode($request->server()), 'ip' => getIp(1111)['ip']]);
                    return returnData(['code' => 404, 'msg' => '无权限访问，访问有记录!请谨慎！']);
                }
            }
        }

        return $next($request);
    }

    public function yanzheng($type, $id)
    {
        $where = [
            "id" => $id
        ];
        switch ($type) {
            case 1:
                $userDate = $this->adminLogin($where);
                break;
            case 2:
                $userDate = $this->pAdmin($where);
                break;
            case 3:
                $userDate = $this->jLogin($where);
                break;
            case 4:
                $userDate = $this->xLogin($where);
                break;
            case 5:
                $userDate = $this->pLogin($where);
                break;
            default:
                $userDate = null;
        }
        if ($userDate == null) {
            return null;
        } else {
            return $userDate->toArray();
        }
    }

    public function adminLogin($where)
    {
        return Admin::where($where)->find();
    }

    public function jLogin($where)
    {
        return Juser::where($where)->find();
    }

    public function xLogin($where)
    {
        return Xuser::where($where)->find();
    }

    public function pAdmin($where)
    {
        return Padmin::where($where)->find();
    }

    public function pLogin($where)
    {
        return Puser::where($where)->find();
    }
}
