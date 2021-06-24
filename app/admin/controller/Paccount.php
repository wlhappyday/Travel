<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\model\Paccount as p_account;
use app\common\model\XproductClass;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\facade\Validate;
use think\Request;
use app\common\model\JproductClass;

class Paccount
{

    /**
     * @author liujiong
     * @Note  获取平台商账号信息
     */
    public function list(){
        $num = input('post.num/d','10','strip_tags');

        $mch_id = input('post.mch_id/s','','strip_tags');
        $where = [];
        if ($mch_id){
            $where['a.mch_id'] = $mch_id;
        }
        $name = input('post.name/s','','strip_tags');
        if ($name){
            $where['a.name'] = $name;
        }
        $phone = input('post.phone/s','','strip_tags');
        if ($phone){
            $where['b.phone'] = $phone;
        }
        $state = input('post.state/d');

        if ($state){
            $where['a.state'] = $state;
        }

        $result = new p_account();
        $data = $result->alias('a')->where($where)
            ->join('P_admin b','b.id=a.pid')
            ->field('a.id,a.name,a.mch_id,a.key,a.state,a.create_time,b.user_name,b.phone')
            ->paginate($num);

        if($data){
            return returnData(['data'=>$data,'code'=>'200']);
        }else{
            return returnData(['msg'=>'该用户不存在或已被紧用','code'=>'201']);
        }

    }
}