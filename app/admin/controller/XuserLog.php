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
        $phone = input('post.phone/s','','strip_tags');
        $where = [];
        $where['b.status'] = '0';
        if ($uname){
            $where['a.uname'] = $uname;
        }
        if ($phone){
            $where['b.phone'] = $phone;
        }
        $log_result = new X_userLog();
        $data = $log_result->where($where)->alias('a')->join('X_user b','b.id=a.uid','LEFT')->order('a.id asc')->field('a.id,a.uname,a.info,a.ip,a.address,a.create_time,b.phone')->paginate($num);

        return returnData(['data'=>$data,'code'=>'200']);
    }

}