<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\model\JuserBalanceRecords as J_userBalanceRecords;
use app\common\model\Juser;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\Request;

class JuserBalanceRecords
{
    public function list(){
        $num = input('post.num/d','10','strip_tags');
        $type = input('post.type/d','','strip_tags');
        $uname = input('post.uname/s','','strip_tags');
        $phone = input('post.phone/s','','strip_tags');
        $data_id = input('post.data_id/s','','strip_tags');
        $where = [];
        if ($uname){
            $where['b.user_name'] = $uname;
        }
        if ($phone){
            $where['b.phone'] = $phone;
        }
        if ($data_id){
            $where['a.data_id'] = $data_id;
        }
        if ($type){
            $where['a.type'] = $type;
        }

        $balance_result = new J_userBalanceRecords();
        $data = $balance_result->alias('a')
            ->where($where)
            ->join('J_user b','b.id=a.uid','LEFT')
            ->field('a.*,b.user_name uname,b.phone')
            ->paginate($num)->toArray();

        return returnData(['data'=>$data,'code'=>'200']);
    }

}