<?php
declare (strict_types = 1);

namespace app\scenic\controller;

use app\common\model\JuserBalanceRecords;
use app\common\model\JuserLog;
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

        $balance_result = new JuserBalanceRecords();
        $data = $balance_result->where($where)->field('id,type,scene,before_money,money,after_money,descript,create_time')->paginate($num)->toArray();

        return returnData(['data'=>$data,'code'=>'200']);
    }

}