<?php
declare (strict_types = 1);

namespace app\scenic\controller;

use app\common\model\JuserLog;
use app\common\model\Log as user_log;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\Request;

class Log
{
    public function list(){
        $num = input('post.num/d','10','strip_tags');

        $uid = getDecodeToken()['id'];
        $log_result = new JuserLog();
        $data = $log_result->where(['uid'=>$uid])->field('id,info,ip,address,create_time')->paginate($num)->toArray();

        return returnData(['data'=>$data,'code'=>'200']);
    }
    public function Login_list(){
        $num = input('post.num/d','10','strip_tags');

        $uid = getDecodeToken()['id'];
        $log_result = new user_log();
        $data = $log_result->where(['uid'=>$uid,'type'=>'3'])->field('id,info,ip,address,create_time')->paginate($num)->toArray();

        return returnData(['data'=>$data,'code'=>'200']);
    }

}