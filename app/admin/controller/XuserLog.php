<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\model\XuserLog as X_userLog;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\Request;

class XuserLog
{
    public function list(){
        $num = input('post.num/d','10','strip_tags');
        $uname = input('post.uname/s','','strip_tags');
        $where = [];
        if ($uname){
            $where['uname'] = $uname;
        }
        $log_result = new X_userLog();
        $data = $log_result->where($where)->field('id,uname,info,ip,address,create_time')->paginate($num);

        return returnData(['data'=>$data,'code'=>'200']);
    }

}