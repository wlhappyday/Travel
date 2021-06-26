<?php
declare (strict_types=1);

namespace app\pay\controller;

use app\common\model\Accounts;
use app\common\model\Order;
use app\common\model\Orderdetails;
use app\common\model\Padmin;
use app\common\model\Puser;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;
use think\Request;
use think\response\Json;

class Pay
{
    /**
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function index(Request $request): Json
    {
        if ($request->isPost()) {
            $orderId = $request->post('orderId');
            $exent = [
                'js_code' => $request->post('js_code', ""),
                'gname' => $request->post('gname', "")
            ];
            if (empty($exent['js_code'])) {
                return returnData(['code' => '-1', 'msg' => "非法请求"]);
            }
            $order = (new Order)->where('order_id', $orderId)->find();
            if (empty($order)) {
                return returnData(['code' => '-1', 'msg' => "非法请求"]);
            }
            $order = $order->toArray();
            $padmin = (new Padmin)->where('id', $order['p_id'])->find();
            if (empty($padmin)) {
                return returnData(['code' => '-1', 'msg' => "非法请求"]);
            }
            if (empty($padmin['cl_id']) || empty($padmin['cl_key']) || empty($padmin['sub_mch_id']) || empty($padmin['mch_id'])) {
                return returnData(['code' => '-1', 'msg' => "请平台商配置收款账户"]);
            }
            $pUser = (new Puser)->where('id', $order['p_user_id'])->find();
            if (empty($pUser)) {
                return returnData(['code' => '-1', 'msg' => "非法请求"]);
            }
            if (empty($pUser['sub_mch_id']) || empty($pUser['appid']) || empty($pUser['appkey'])) {
                return returnData(['code' => '-1', 'msg' => "请门店配置收款账户"]);
            }
            $pUser = $pUser->toArray();
            $padmin = $padmin->toArray();
            $date = json_decode($this->payTo($order, $padmin, $exent, $pUser), true);
            if (isset($date['return_msg']) || isset($date['return_code'])) {
                return returnData(['code' => '-1', 'msg' => $date['return_msg']]);
            } else {
                return returnData(['code' => '200', 'date' => $date]);
            }
        } else {
            return returnData(['code' => '-1', 'msg' => "非法请求"]);
        }
    }

    function randStr($len): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz'; // characters to build the password from
        $string = '';
        for (; $len >= 1; $len--) {
            $position = rand() % strlen($chars);
            $string .= substr($chars, $position, 1);
        }
        return $string;
    }

    /**
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws DataNotFoundException
     */

    public function payTo($order, $padmin, $exent, $pUser)
    {
        $accounts = (new Accounts)->where("mch_id", $padmin['mch_id'])->find();
//        if ($accounts==null){
//
//        }

        $url = 'https://xcxapi.payunke.com/index/unifiedorder111111?format=jsonIn';
        $payData['appid'] = $padmin['cl_id'];
        $payData['out_trade_no'] = $this->randStr(10) . "_" . $order['order_id'];
        $payData['pay_type'] = 'weChatJsFzLy';
        $payData['amount'] = sprintf("%.2f", $order['order_amount']);
        $payData['callback_url'] = 'http://platformzcm.february202.cn/api/aliPay/callback';
        $payData['success_url'] = 'http://www.baidu.com';
        $payData['error_url'] = 'http://www.baidu.com';
//            {"appid":"wxaf1f2c344b7ffa78",
//        "sub_appid":"wx6117e2540ee05b55",
//        "secret":"8200d555ea52c45fb82d2b51de506514",
//        "mch_id":"1285253401",
//        "key":"c5258aaf86c5cc46df64cfe9af0b9791",
//        "sub_mch_id":"1517514021"}
        $payData['extend'] = json_encode([
            'appid' => $accounts['appid'],
            'sub_appid' => $pUser['appid'],
            'secret' => $pUser['appkey'],
            'js_code' => $exent['js_code'],
            'gname' => $exent['gname'],
            'mch_id' => $accounts['mch_id'],
            'key' => $accounts['key'],
            'sub_mch_id' => $padmin['sub_mch_id']
        ], JSON_UNESCAPED_UNICODE);
        $payData['sign'] = getSign($padmin['cl_key'], $payData);
//        $dateE = post($url, $payData);
        return post($url, $payData);
    }

    /**
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws DataNotFoundException
     */
    public function reFund(Request $request): Json
    {
        if ($request->isPost()) {
            $orderId = $request->post('orderId');
            $orderDetailsIds = $request->post('orderDetailsIds/a');
            if (empty($orderId)) {
                return returnData(['code' => '-1', 'msg' => "非法请求"]);
            }
            $orderDate = $this->queryOrder($orderId);
            if (empty($orderDate)) {
                return returnData(['code' => '-1', 'msg' => "订单无法退款"]);
            }
            if (empty($orderDetailsIds)) {
                Order::update(["order_status" => 5, "refund_time" => time()], ["order_id" => $orderDate['order_id']]);
                if (genggaijiage((new Order())->where(["order_id" => $orderDate['order_id']])->find()->toArray())) {
                    return returnData(['code' => '200', 'msg' => "退款成功"]);
                }
            } else {
                $timeInt = (string)time();
                $orderDetails = (new Orderdetails)->where(["order_id" => $orderId, "inspect_ticket_status" => 1])->whereIn("id", $orderDetailsIds)->where('end_time', '>', $timeInt)->whereNull("delete_time")->select();
                if (empty($orderDetails)) {
                    return returnData(['code' => '-1', 'msg' => "无此订单"]);
                }
                p($this->reFundorder($orderDetails->toArray()));
            }
        }
    }

    /**
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     * @throws DbException
     */
    public function reFundOrder($orderDetails)
    {
        $price = "0";
        $refund_num = (string)count($orderDetails);
        foreach ($orderDetails as $orderDetail) {
            $price = bcadd($orderDetail['price'], $price);
        }
        $order = new Order();
        $orderDate = $order->where(["order_id" => $orderDetails[0]["order_id"]])->find()->toArray();
        if (bccomp($orderDate['order_amount'], $price, 2) > 0) {
            Order::update(["refund_price" => $price, "refund_num" => $refund_num, "order_status" => 4, "refund_time" => time(), "surplus_price" => bcsub((string)$orderDate['order_amount'], $price), "surplus_num" => bcsub((string)$orderDate['goods_num'], $refund_num)], ["order_id" => $orderDate['order_id']]);
            if (genggaijiage($order->where(["order_id" => $orderDate['order_id']])->find()->toArray())) {
                foreach ($orderDetails as $orderDetail) {
                    Orderdetails::update(["delete_time" => time(), "inspect_ticket_status" => 2], ["id" => $orderDetail["id"], "order_id" => $orderDate['order_id']]);
                }
            }
        }
    }

    /**
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     * @throws DbException
     */
    public function queryOrder($order_id)
    {
        return (new Order)->where(["order_id" => $order_id])->whereBetween("order_status", [3, 4])->find();
    }

    /**
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws DataNotFoundException
     */
    public function dingDanWJ()
    {
        $dateStr = strtotime(date('Y-m-d', time()));
        $timestamp = $dateStr - 7 * 24 * 60 * 60;
        $timestamp1 = $dateStr - 6 * 24 * 60 * 60;
        $Query = (new Db)->table('orders')->where(["pay_time" => ["between", [$timestamp, $timestamp1]], 'order_status' => ['<', 5], "is_checkout" => 0])->select();
    }

}