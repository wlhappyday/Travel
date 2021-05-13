<?php
declare (strict_types = 1);

namespace app\line\controller;

use app\common\model\XuserLog;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\Request;

class Log
{
    public function list(){
        $num = input('post.num/d','10','strip_tags');

        $uid = getDecodeToken()['id'];
        $log_result = new XuserLog();
        $data = $log_result->where(['uid'=>$uid])->field('id,info,ip,address,create_time')->paginate($num)->toArray();

        return returnData(['data'=>$data,'code'=>'200']);
    }

    public function Loginlist(){
        $num = input('post.num/d','10','strip_tags');

        $uid = getDecodeToken()['id'];
        $log_result = new XuserLog();
        $data = $log_result->where(['uid'=>$uid,'type'=>'4'])->field('id,info,ip,address,create_time')->paginate($num)->toArray();

        return returnData(['data'=>$data,'code'=>'200']);
    }

}