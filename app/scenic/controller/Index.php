<?php
declare (strict_types = 1);

namespace app\scenic\controller;

use app\common\model\Order;
use app\common\model\OrderCharge;
use app\common\model\Juser;
use thans\jwt\facade\JWTAuth;
use think\cache\driver\Redis;
use think\facade\Cache;
use think\facade\Db;
use think\Request;

class Index
{
    public function index(){
        $id = getDecodeToken()['id'];
        $where['store_id'] = $id;
        $where['store_type'] = '1';

        $data['user'] = Juser::where(['a.id'=>$id])->alias('a')
            ->join('file b','b.id=a.avatar','LEFT')
            ->field('a.user_name,a.phone,b.file_path,a.amount')
            ->find();

        //handle 待支付 payment 已支付 refund 退款 end 订单完结
        $data['order']['handle'] = Order::where($where)->whereIn('order_status',[1,2])->count();
        $data['order']['payment'] = Order::where($where)->whereIn('order_status',[3])->count();
        $data['order']['refund'] = Order::where($where)->whereIn('order_status',[4,5])->count();
        $data['order']['end'] = Order::where($where)->whereIn('order_status',[6])->count();

        //上周
        //pay_amount 订单交易额  pay_sum 订单数量 refund_amount 退款金额  refund_sum 退款数量
        $data['week']['pay_sum'] = Cache::store('redis')->get('scenic_pay_sum_'.$id);
        $data['week']['pay_amount'] = Cache::store('redis')->get('scenic_pay_amount_'.$id);
        $data['week']['refund_amount'] = Cache::store('redis')->get('scenic_refund_sum_'.$id);
        $data['week']['refund_sum'] = Cache::store('redis')->get('scenic_refund_amount_'.$id);

        return returnData(['data'=>$data,'code'=>'200']);
    }
    public function  today(){
        $id = getDecodeToken()['id'];
        $where['store_id'] = $id;
        $where['store_type'] = '1';
        //今天
        //pay_amount 订单交易额  pay_sum 订单数量 refund_amount 退款金额  refund_sum 退款数量
        $date = date('Y-m-d',time());
        $begintime = strtotime($date);

        $today_pay_amount = Order::where($where)->whereIn('order_status',[3,6])->where('add_time','>',$begintime)->sum('store_price*goods_num');
        $data['today']['pay_sum'] = Order::where($where)->whereIn('order_status',[3,6])->where('add_time','>',$begintime)->count();
        $data['today']['refund_amount'] = Order::where($where)->whereIn('order_status',[4,5])->where('add_time','>',$begintime)->sum('store_price*refund_num');;
        $data['today']['pay_amount']  = bcsub((string) $today_pay_amount,(string) $data['today']['refund_amount'],2);
        $data['today']['refund_sum'] = Order::where($where)->whereIn('order_status',[4,5])->where('add_time','>',$begintime)->count();

        return returnData(['data'=>$data,'code'=>'200']);
    }


}