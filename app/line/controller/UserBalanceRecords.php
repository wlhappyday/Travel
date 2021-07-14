<?php
declare (strict_types = 1);

namespace app\line\controller;

use app\common\model\XuserBalanceRecords;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\Request;

class UserBalanceRecords
{
    public function list(){
        $num = input('post.num/d','10','strip_tags');
        $type = input('post.type/d','','strip_tags');
        $uid = getDecodeToken()['id'];
        $where = [];
        $where['uid'] = $uid;
        if($type){
            $where['type'] = $type;
        }
        $data_id = input('post.data_id/s','','strip_tags');
        if ($data_id){
            $where['data_id'] = $data_id;
        }

        $balance_result = new XuserBalanceRecords();
        $data = $balance_result->where($where)->paginate($num)->toArray();

        return returnData(['data'=>$data,'code'=>'200']);
    }

}