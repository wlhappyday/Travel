<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\model\Log as Login_log;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\Request;

class Log
{
    public function list(){
        $num = input('post.num/d','10','strip_tags');
        $user_name = input('post.user_name/s','','strip_tags');
        $type = input('post.type/d','','strip_tags');
        $where = [];
        if ($user_name){
            $where['user_name'] = $user_name;
        }
        if ($type){
            $where['type'] = $type;
        }
        $log_result = new Login_log();
        $data = $log_result->where($where)->field('id,user_name,info,type,ip,address,create_time')->paginate($num);

        return returnData(['data'=>$data,'code'=>'200']);
    }

}