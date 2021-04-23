<?php


namespace app\api\controller;


use app\api\model\Admin;
use app\api\model\Juser;
use app\api\model\Padmin;
use app\api\model\Puser;
use app\api\model\Xuser;
use app\common\model\Log;
use thans\jwt\facade\JWTAuth;

class Login
{
    public function login()
    {
        $phone = input('post.phone', '123456', 'strip_tags');
        $username = input('post.username', '123456', 'strip_tags');
        $passwd = input('post.passwd', '123456', 'strip_tags');
        $type = input('post.type/d', '5');
        if ($username != null) {
            $where['user_name'] = $username;
        } elseif ($phone != null) {
            $where['phone'] = $phone;
        } else {
            return returnData(['msg' => '请输入完整账号密码'], 201);
        }
        if ($type != '1') {
            $where['status'] = '0';
        }
        switch ($type)
        {
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
                return returnData(["code" => 201, 'msg' => '非法参数']);
        }
        if (empty($userDate)) {
            return returnData(['msg' => '用户不存在'], 201);
        }
        if (!checkPasswd($passwd, $userDate)) {
            return returnData(['msg' => '账号密码错误'], 201);
        }
        $userDate = $userDate->toArray();
        $userInfo = [
            'userName' => $userDate['user_name'],
            'phone' => $userDate['phone'],
            'id' => $userDate['id'],
            'type' => $type,
            'avatar' => $userDate['avatar'] ?? ''
        ];
        $logData = [
            'uid' => $userDate['id'],
            'user_name' => $userDate['user_name'],
            'type' => $type,
            'info' => '登录',
            'create_time' => time(),
            'ip' => getIp(1111)['ip'],
        ];
        Log::create($logData);
        return returnData($userInfo, 200, ['Authorization' => "Bearer " . JWTAuth::builder($userInfo),'Access-Control-Expose-Headers'=>"Authorization"]);
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