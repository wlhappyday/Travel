<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\model\OrderCharge;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\Request;

class Charge
{
    public function list(){
        $where = [];

        $num = input('post.num/d','10','strip_tags');
        $order_no = input('post.order_no/s','','strip_tags');
        if ($order_no){
            $where['a.order_no'] = $order_no;
        }
        $pay_trade_no = input('post.pay_trade_no/s','','strip_tags');
        if ($pay_trade_no){
            $where['a.pay_trade_no'] = $pay_trade_no;
        }
        $type = input('post.type/d','','strip_tags');
        if ($type){
            $where['a.type'] = $type;
        }
        $status = input('post.status/d','','strip_tags');
        if ($status){
            $where['a.status'] = $status;
        }
        $pname = input('post.pname/s','','strip_tags');
        if ($pname){
            $where['b.user_name'] = $pname;
        }
        $jname = input('post.jname/s','','strip_tags');
        if ($jname){
            $where['c.user_name'] = $jname;
        }
        $xname = input('post.xname/s','','strip_tags');
        if ($xname){
            $where['d.user_name'] = $xname;
        }
        $order_result = new OrderCharge();
        $start_time = input('post.start_time/s','','strip_tags');
        if ($start_time){
            $order_result->whereTime('create_time', '>=', strtotime($start_time));
        }
        $end_time = input('post.end_time/s','','strip_tags');
        if ($end_time){
            $order_result->whereTime('create_time', '<=', strtotime($end_time));
        }
//p($where);
        $data = $order_result->alias('a')
            ->where($where)
            ->join('p_admin b','b.id=a.user_id and a.type=2','LEFT')
            ->join('j_user c','c.id=a.user_id and a.type=3','LEFT')
            ->join('x_user d','d.id=a.user_id and a.type=4','LEFT')
            ->field('a.id,a.order_no,a.pay_trade_no,a.type,a.money,a.status,a.create_time,a.pay_time,b.user_name pname,b.phone pphone,c.user_name jname,c.phone jphone,d.user_name xname,d.phone xphone')
            ->order('a.id desc')
            ->paginate($num)->toarray();

        return returnData(['data'=>$data,'code'=>'200']);
    }


}