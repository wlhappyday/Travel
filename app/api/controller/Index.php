<?php
declare (strict_types = 1);

namespace app\api\controller;
use app\api\model\Admin;
use thans\jwt\facade\JWTAuth;

class Index
{
    public function login(){
        $phone = input('post.phone','123456','htmlspecialchars'); // 获取get变量 并用htmlspecialchars函数过滤
        $username = input('post.username','123456','htmlspecialchars'); // 获取param变量 并用strip_tags函数过滤
        $passwd = input('post.passwd','123456','htmlspecialchars'); // 获取post变量 并用org\Filter类的safeHtml方法过滤
        if ($username!=null){
            $where=['user_name'=>$username];
        }elseif ($phone!=null){
            $where=['phone'=>$phone];
        }else{
            return returnData(['msg'=>'请输入完整账号密码'],201);
        }
        $admin = Admin::where($where)->find();
        if (empty($admin)){
            return returnData(['msg'=>'用户不存在'],201);
        }
        if(!checkPasswd($passwd,$admin)){
            return returnData(['msg'=>'账号密码错误'],201);
        }
        echo JWTAuth::builder($admin);
    }
}
