<?php
declare (strict_types = 1);

namespace app\scenic\controller;

use app\common\model\Order as Orders;
use app\common\model\Orderdetails;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\Request;

class Order
{
    public function list(){
        $where = [];
        $where['a.store_id'] = getDecodeToken()['id'];
        $where['a.store_type'] = '1';
        $where['d.type'] = '1';
        $where['d.uid'] = getDecodeToken()['id'];

        $num = input('post.num/d','10','strip_tags');
        $order_id = input('post.order_id/s','','strip_tags');
        if ($order_id){
            $where['a.order_id'] = $order_id;
        }
        $order_status = input('post.order_status/d','','strip_tags');
        if ($order_status){
            $where['a.order_status'] = $order_status;
        }
        $uname = input('post.uname/s','','strip_tags');
        if ($uname){
            $where['b.user_name'] = $uname;
        }
        $pname = input('post.pname/s','','strip_tags');
        if ($pname){
            $where['c.user_name'] = $pname;
        }
        $order_result = new Orders();
        $start_time = input('post.start_time/s','','strip_tags');
        if ($start_time){
            $order_result->whereTime('add_time', '>=', strtotime($start_time));
        }
        $end_time = input('post.end_time/s','','strip_tags');
        if ($end_time){
            $order_result->whereTime('add_time', '<=', strtotime($end_time));
        }

        $data = $order_result->alias('a')
            ->where($where)
            ->join('p_user b','b.id=a.user_id','LEFT')
            ->join('p_admin c','c.id=a.p_id','LEFT')
            ->join('j_product d','d.id=a.store_good_id','LEFT')
            ->field('a.order_id,a.order_status,a.payment_order_id,a.store_price,a.goods_num,a.surplus_num,a.refund_num,a.add_time,b.user_name uname,c.user_name pname,d.name product_name,d.class_name')
            ->paginate($num)->toarray();

        return returnData(['data'=>$data,'code'=>'200']);
    }
    public function listDetail(){
        $where = [];
        $where['store_id'] = getDecodeToken()['id'];
        $where['store_type'] = '1';
        $order_id = input('post.order_id/s','','strip_tags');
        if ($order_id){
            $where['order_id'] = $order_id;
        }

        $order_result = new Orderdetails();
        $data = $order_result->where($where)->field('order_id,name,id_card,phone,admission_ticket_type,inspect_ticket_details,inspect_ticket_status')->select();

        return returnData(['data'=>$data,'code'=>'200']);
    }

}