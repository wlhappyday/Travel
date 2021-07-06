<?php
declare (strict_types = 1);

namespace app\scenic\controller;

use app\common\model\OrderCharge;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\Request;

class Charge
{
    public function list(){
        $where = [];

        p(aliSmsSend('15210174216',3,1,3));

        $num = input('post.num/d','10','strip_tags');
        $order_no = input('post.order_no/s','','strip_tags');
        if ($order_no){
            $where['a.order_no'] = $order_no;
        }
        $pay_trade_no = input('post.pay_trade_no/s','','strip_tags');
        if ($pay_trade_no){
            $where['a.pay_trade_no'] = $pay_trade_no;
        }
        $status = input('post.status/d','','strip_tags');
        if ($status){
            $where['a.status'] = $status;
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
        $where['type'] = '3';
//p($where);
        $data = $order_result->alias('a')
            ->where($where)
            ->field('a.id,a.order_no,a.pay_trade_no,a.money,a.status,a.create_time,a.pay_time')
            ->order('a.id desc')
            ->paginate($num)->toarray();

        return returnData(['data'=>$data,'code'=>'200']);
    }


}