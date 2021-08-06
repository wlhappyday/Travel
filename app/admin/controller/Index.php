<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\model\Order;
use app\common\model\OrderCharge;
use app\common\model\Padmin;
use thans\jwt\facade\JWTAuth;
use think\cache\driver\Redis;
use think\facade\Cache;
use think\facade\Db;
use think\Request;

class Index
{
    public function index(){
        //handle 待支付 payment 已支付 refund 退款 end 订单完结
        $data['order']['handle'] = Order::whereIn('order_status',[1,2])->count();
        $data['order']['payment'] = Order::whereIn('order_status',[3])->count();
        $data['order']['refund'] = Order::whereIn('order_status',[4,5])->count();
        $data['order']['end'] = Order::whereIn('order_status',[6])->count();

        //上周
        //pay_amount 订单交易额  pay_sum 订单数量 refund_amount 退款金额  refund_sum 退款数量
        $data['week']['pay_sum'] = Cache::store('redis')->get('admin_pay_sum');
        $data['week']['pay_amount'] = Cache::store('redis')->get('admin_pay_amount');
        $data['week']['refund_amount'] = Cache::store('redis')->get('admin_refund_sum');
        $data['week']['refund_sum'] = Cache::store('redis')->get('admin_refund_amount');

        return returnData(['data'=>$data,'code'=>'200']);
    }
    public function  today(){
        //今天
        //pay_amount 订单交易额  pay_sum 订单数量 refund_amount 退款金额  refund_sum 退款数量
        $date = date('Y-m-d',time());
        $begintime = strtotime($date);

        $today_pay_amount = Order::whereIn('order_status',[3,6])->where('add_time','>',$begintime)->sum('order_amount');
        $data['today']['pay_sum'] = Order::whereIn('order_status',[3,6])->where('add_time','>',$begintime)->count();
        $data['today']['refund_amount'] = Order::whereIn('order_status',[4,5])->where('add_time','>',$begintime)->sum('refund_price');;
        $data['today']['pay_amount']  = bcsub((string) $today_pay_amount,(string) $data['today']['refund_amount'],2);
        $data['today']['refund_sum'] = Order::whereIn('order_status',[4,5])->where('add_time','>',$begintime)->count();

        return returnData(['data'=>$data,'code'=>'200']);
    }


}