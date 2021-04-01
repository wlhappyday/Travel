<?php


namespace app\api\controller;


use app\api\model\Admin;
use app\api\model\Juser;
use app\api\model\Padmin;
use app\api\model\Xuser;
use thans\jwt\facade\JWTAuth;
use think\Request;

class Login
{
    public function login(){
        $phone = input('post.phone','123456','htmlspecialchars'); // 获取get变量 并用htmlspecialchars函数过滤
        $username = input('post.username','123456','htmlspecialchars'); // 获取param变量 并用strip_tags函数过滤
        $passwd = input('post.passwd','123456','htmlspecialchars'); // 获取post变量 并用org\Filter类的safeHtml方法过滤
        $type = input('post.type/d','1');
        if ($username!=null){
            $where=['user_name'=>$username];
        }elseif ($phone!=null){
            $where=['phone'=>$phone];
        }else{
            return returnData(['msg'=>'请输入完整账号密码'],201);
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
                return returnData(['msg'=>'非法参数'],201);
        }
        if (empty($userDate)){
            return returnData(['msg'=>'用户不存在'],201);
        }
        if(!checkPasswd($passwd,$userDate)){
            return returnData(['msg'=>'账号密码错误'],201);
        }
        return returnData($userDate->toArray(),200,['Authorization'=>JWTAuth::builder($userDate->toArray())]);
//        p($userDate->toArray());
        p(JWTAuth::builder($userDate->toArray())) ;
    }
    public function jiemi(Request $request){
        p(JWTAuth::auth());
        p($request->header("Authorization"));
        $token = JWTAuth::builder(['uid' => 1]);//参数为用户认证的信息，请自行添加

        JWTAuth::auth();//token验证

        JWTAuth::refresh();//刷新token，会将旧token加入黑名单

        $tokenStr = JWTAuth::token()->get(); //可以获取请求中的完整token字符串

        $payload = JWTAuth::auth(); //可验证token, 并获取token中的payload部分
        $uid = $payload['uid']->getValue(); //可以继而获取payload里自定义的字段，比如uid
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