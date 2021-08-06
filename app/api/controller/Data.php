<?php

namespace app\api\controller;

use app\common\model\Juser;
use app\common\model\Jproduct;
use app\common\model\Order;
use app\common\model\Padmin;
use app\common\model\Puser;
use app\common\model\Xuser;
use think\facade\Cache;

class Data
{
    public function scenic(){
        $user = Juser::where(['status'=>'0'])->field('id')->select()->toArray();
        $date = date('Y-m-d',time());

        $begintime = strtotime("$date -7 day");
        $endtime = strtotime($date);

        foreach ($user as $k=>$v){
            $where['store_id'] = $v['id'];
            $where['store_type'] = '1';

            $today_pay_amount = Order::where($where)->whereIn('order_status',[3,6])->whereBetweenTime('add_time',$begintime,$endtime)->sum('store_price*goods_num');
            $pay_sum = Order::where($where)->whereIn('order_status',[3,6])->whereBetweenTime('add_time',$begintime,$endtime)->count();
            $refund_amount = Order::where($where)->whereIn('order_status',[4,5])->whereBetweenTime('add_time',$begintime,$endtime)->sum('store_price*refund_num');;
            $pay_amount  = bcsub((string) $today_pay_amount,(string) $refund_amount,2);
            $refund_sum = Order::where($where)->whereIn('order_status',[4,5])->whereBetweenTime('add_time',$begintime,$endtime)->count();

            Cache::store('redis')->set('scenic_pay_sum_'.$v['id'],$pay_sum);
            Cache::store('redis')->set('scenic_pay_amount_'.$v['id'],$pay_amount);
            Cache::store('redis')->set('scenic_refund_sum_'.$v['id'],$refund_sum);
            Cache::store('redis')->set('scenic_refund_amount_'.$v['id'],$refund_amount);

        }

    }
    public function line(){
        $user = Xuser::where(['status'=>'0'])->field('id')->select()->toArray();
        $date = date('Y-m-d',time());

        $begintime = strtotime("$date -7 day");
        $endtime = strtotime($date);

        foreach ($user as $k=>$v){
            $where['store_id'] = $v['id'];
            $where['store_type'] = '2';

            $today_pay_amount = Order::where($where)->whereIn('order_status',[3,6])->whereBetweenTime('add_time',$begintime,$endtime)->sum('store_price*goods_num');
            $pay_sum = Order::where($where)->whereIn('order_status',[3,6])->whereBetweenTime('add_time',$begintime,$endtime)->count();
            $refund_amount = Order::where($where)->whereIn('order_status',[4,5])->whereBetweenTime('add_time',$begintime,$endtime)->sum('store_price*refund_num');;
            $pay_amount  = bcsub((string) $today_pay_amount,(string) $refund_amount,2);
            $refund_sum = Order::where($where)->whereIn('order_status',[4,5])->whereBetweenTime('add_time',$begintime,$endtime)->count();

            Cache::store('redis')->set('line_pay_sum_'.$v['id'],$pay_sum);
            Cache::store('redis')->set('line_pay_amount_'.$v['id'],$pay_amount);
            Cache::store('redis')->set('line_refund_sum_'.$v['id'],$refund_sum);
            Cache::store('redis')->set('line_refund_amount_'.$v['id'],$refund_amount);

        }

    }
    public function platform(){
        $user = Padmin::where(['status'=>'0'])->field('id')->select()->toArray();
        $date = date('Y-m-d',time());

        $begintime = strtotime("$date -7 day");
        $endtime = strtotime($date);

        foreach ($user as $k=>$v){
            $where['p_id'] = $v['id'];

            $today_pay_amount = Order::where($where)->whereIn('order_status',[3,6])->whereBetweenTime('add_time',$begintime,$endtime)->sum('p_price*goods_num');
            $pay_sum = Order::where($where)->whereIn('order_status',[3,6])->whereBetweenTime('add_time',$begintime,$endtime)->count();
            $refund_amount = Order::where($where)->whereIn('order_status',[4,5])->whereBetweenTime('add_time',$begintime,$endtime)->sum('p_price*refund_num');;
            $pay_amount  = bcsub((string) $today_pay_amount,(string) $refund_amount,2);
            $refund_sum = Order::where($where)->whereIn('order_status',[4,5])->whereBetweenTime('add_time',$begintime,$endtime)->count();

            Cache::store('redis')->set('platform_pay_sum_'.$v['id'],$pay_sum);
            Cache::store('redis')->set('platform_pay_amount_'.$v['id'],$pay_amount);
            Cache::store('redis')->set('platform_refund_sum_'.$v['id'],$refund_sum);
            Cache::store('redis')->set('platform_refund_amount_'.$v['id'],$refund_amount);

        }

    }
    public function user(){
        $user = Puser::where(['status'=>'0'])->field('id')->select()->toArray();
        $date = date('Y-m-d',time());

        $begintime = strtotime("$date -7 day");
        $endtime = strtotime($date);

        foreach ($user as $k=>$v){
            $where['p_user_id'] = $v['id'];

            $today_pay_amount = Order::where($where)->whereIn('order_status',[3,6])->whereBetweenTime('add_time',$begintime,$endtime)->sum('order_amount');
            $pay_sum = Order::where($where)->whereIn('order_status',[3,6])->whereBetweenTime('add_time',$begintime,$endtime)->count();
            $refund_amount = Order::where($where)->whereIn('order_status',[4,5])->whereBetweenTime('add_time',$begintime,$endtime)->sum('refund_price');
            $pay_amount  = bcsub((string) $today_pay_amount,(string) $refund_amount,2);
            $refund_sum = Order::where($where)->whereIn('order_status',[4,5])->whereBetweenTime('add_time',$begintime,$endtime)->count();

            Cache::store('redis')->set('user_pay_sum_'.$v['id'],$pay_sum);
            Cache::store('redis')->set('user_pay_amount_'.$v['id'],$pay_amount);
            Cache::store('redis')->set('user_refund_sum_'.$v['id'],$refund_sum);
            Cache::store('redis')->set('user_refund_amount_'.$v['id'],$refund_amount);

        }

    }
    public function admin(){
        $date = date('Y-m-d',time());

        $begintime = strtotime("$date -7 day");
        $endtime = strtotime($date);

        $today_pay_amount = Order::whereIn('order_status',[3,6])->whereBetweenTime('add_time',$begintime,$endtime)->sum('order_amount');
        $pay_sum = Order::whereIn('order_status',[3,6])->whereBetweenTime('add_time',$begintime,$endtime)->count();
        $refund_amount = Order::whereIn('order_status',[4,5])->whereBetweenTime('add_time',$begintime,$endtime)->sum('refund_price');;
        $pay_amount  = bcsub((string) $today_pay_amount,(string) $refund_amount,2);
        $refund_sum = Order::whereIn('order_status',[4,5])->whereBetweenTime('add_time',$begintime,$endtime)->count();

        Cache::store('redis')->set('admin_pay_sum',$pay_sum);
        Cache::store('redis')->set('admin_pay_amount',$pay_amount);
        Cache::store('redis')->set('admin_refund_sum',$refund_sum);
        Cache::store('redis')->set('admin_refund_amount',$refund_amount);



    }

}