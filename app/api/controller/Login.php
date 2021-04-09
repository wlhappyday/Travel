<?php


namespace app\api\controller;


use app\api\model\Admin;
use app\api\model\Juser;
use app\api\model\Padmin;
use app\api\model\Xuser;
use thans\jwt\facade\JWTAuth;

class Login
{
    public function login(){
        $phone = input('post.phone','123456','htmlspecialchars');
        $username = input('post.username','123456','htmlspecialchars');
        $passwd = input('post.passwd','123456','htmlspecialchars');
        $type = input('post.type/d','1');
        if ($username!=null){
            $where['user_name'] = $username;
        }elseif ($phone!=null){
            $where['phone'] = $phone;
        }else{
            return returnData(['msg'=>'请输入完整账号密码'],201);
        }
        if($type != '1'){
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
            default:
                return returnData(["code" => 201, 'msg' => '非法参数']);
        }
        if (empty($userDate)){
            return returnData(['msg'=>'用户不存在'],201);
        }
        if(!checkPasswd($passwd,$userDate)){
            return returnData(['msg'=>'账号密码错误'],201);
        }
        $userDate=$userDate->toArray();
        $userInfo=[
            'userName'=>$userDate['user_name'],
            'phone'=>$userDate['phone'],
            'id'=>$userDate['id'],
            'type'=>$type,
            'avatar'=>isset($userDate['avatar'])?$userDate['avatar']:''
        ];
        return returnData($userInfo,200,['Authorization'=>"Bearer ".JWTAuth::builder($userInfo)]);
    }
    public function jiemi(){
        var_dump(getDecodeToken());
    }
    public function adminLogin($where){
        return Admin::where($where)->find();
    }
    public function jLogin($where){
        return Juser::where($where)->find();
    }
    public function xLogin($where){
        return Xuser::where($where)->find();
    }
    public function pAdmin($where){
        return Padmin::where($where)->find();
    }

}