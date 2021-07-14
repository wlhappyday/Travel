<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\model\Order as Orders;
use app\common\model\Orderdetails;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\Request;

class Order
{
    public function list(){
        $where = [];

        $num = input('post.num/d','10','strip_tags');
        $order_id = input('post.order_id/s','','strip_tags');
        if ($order_id){
            $where['a.order_id'] = $order_id;
        }
        $transaction_id = input('post.transaction_id/s','','strip_tags');
        if ($transaction_id){
            $where['a.transaction_id'] = $transaction_id;
        }
        $order_status = input('post.order_status/d','','strip_tags');
        if ($order_status){
            $where['a.order_status'] = $order_status;
        }
        $store_type = input('post.store_type/d','','strip_tags');
        if ($store_type){
            $where['a.store_type'] = $store_type;
        }
        $uname = input('post.uname/s','','strip_tags');
        if ($uname){
            $where['b.user_name'] = $uname;
        }
        $pname = input('post.pname/s','','strip_tags');
        if ($pname){
            $where['c.user_name'] = $pname;
        }
        $jname = input('post.jname/s','','strip_tags');
        if ($jname){
            $where['e.user_name'] = $jname;
        }
        $xname = input('post.xname/s','','strip_tags');
        if ($xname){
            $where['f.user_name'] = $xname;
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
            ->join('j_user e','e.id=a.store_id and a.store_type=1','LEFT')
            ->join('x_user f','f.id=a.store_id and a.store_type=2','LEFT')
            ->field('a.order_id,a.order_status,a.transaction_id,a.order_amount,a.coupon_price,a.goods_price,a.store_price,a.p_price,a.goods_num,a.surplus_num,a.refund_num,a.store_type,a.add_time,a.pay_time,b.user_name uname,c.user_name pname,d.name product_name,d.class_name,e.user_name jname,f.user_name xname')
            ->paginate($num)->toarray();

        return returnData(['data'=>$data,'code'=>'200']);
    }
    public function listDetail(){
        $where = [];
//        $where['store_id'] = getDecodeToken()['id'];
        $order_id = input('post.order_id/s','','strip_tags');
        if ($order_id){
            $where['order_id'] = $order_id;
        }

        $order_result = new Orderdetails();
        $data = $order_result->where($where)->field('order_id,name,id_card,phone,price,admission_ticket_type,inspect_ticket_details,inspect_ticket_status')->select();

        return returnData(['data'=>$data,'code'=>'200']);
    }

}