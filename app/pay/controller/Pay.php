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
            return returnData(['code' => '-1', 'msg' => "1111111111非法请求"]);
        }
    }

    /**
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function weChat(Request $request): Json
    {
        if ($request->isPost()) {
            $orderId = $request->post('orderId');
            $exent = [
                'gname' => $request->post('gname', "旅游")
            ];
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
            $padmin = $padmin->toArray();
            $date = json_decode($this->payToNative($order, $padmin, $exent), true);
            if (isset($date['code']) && $date['code'] == 200) {
                return returnData(['code' => '200', 'url' => $date['url']]);
            } else {
                return returnData(['code' => '-1', 'msg' => "请联系管理员", "date" => $date]);
            }
        } else {
            return returnData(['code' => '-1', 'msg' => "非法请求"]);
        }
    }
    /**
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws DataNotFoundException
     */
    public function payToNative($order, $padmin, $exent)
    {
        $accounts = (new Accounts)->where("mch_id", $padmin['mch_id'])->find();
        $url = 'https://xcxapi.payunke.com/index/unifiedorder111111?format=jsonIn';
        $payData['appid'] = $padmin['cl_id'];
        $payData['out_trade_no'] = $this->randStr(10) . "_" . $order['order_id'];
        $payData['pay_type'] = 'weChatNativeLy';
        $payData['amount'] = sprintf("%.2f", $order['order_amount']);
        $payData['callback_url'] = url('pay/service/serviceMen')->domain(true)->build();
        $payData['success_url'] = 'http://www.baidu.com';
        $payData['error_url'] = 'http://www.baidu.com';
        $payData['extend'] = json_encode([
            'sp_appid' => $accounts['appid'],
            'gname' => $exent['gname'],
            'sp_mchid' => $accounts['mch_id'],
            'key' => $accounts['key'],
            'sub_mchid' => $padmin['sub_mch_id']
        ], JSON_UNESCAPED_UNICODE);
        $payData['sign'] = getSign($padmin['cl_key'], $payData);
        return post($url, $payData);
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
        $url = 'https://xcxapi.payunke.com/index/unifiedorder111111?format=jsonIn';
        $payData['appid'] = $padmin['cl_id'];
        $payData['out_trade_no'] = $this->randStr(10) . "_" . $order['order_id'];
        $payData['pay_type'] = 'weChatJsFzLy';
        $payData['amount'] = sprintf("%.2f", $order['order_amount']);
        $payData['callback_url'] = url('pay/service/service')->domain(true)->build();
        $payData['success_url'] = 'http://www.baidu.com';
        $payData['error_url'] = 'http://www.baidu.com';
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
            $orderId = $request->post('orderId/s');
            $orderDetailsIds = $request->post('orderDetailsIds/a');
            if (empty($orderId)) {
                return returnData(['code' => '-1', 'msg' => "非法请求"]);
            }
            $orderDate = $this->queryOrder($orderId);
            $timeInt = (string)time();
            if (empty($orderDetailsIds)) {
                $orderDetailDate = (new Orderdetails)->where(["order_id" => $orderId, "inspect_ticket_status" => 1])->where('end_time', '>', $timeInt)->whereNull("delete_time")->select()->toArray();
                if (empty($orderDetailDate)) {
                    return returnData(['code' => '-1', 'msg' => "143无此订单"]);
                }
                $count = count($orderDetailDate);
                $surplus_num = $orderDate["goods_num"] - $count;
                $reFundFeeTo = $this->reFundFeeTo($orderDate, $orderDate["order_amount"]);
                if ($reFundFeeTo["code"] != 200) {
                    return returnData($reFundFeeTo);
                }
                Order::update(["order_status" => 5, "refund_num" => $count, "surplus_num" => $surplus_num, "refund_time" => time()], ["order_id" => $orderDate['order_id']]);
                if (genggaijiage((new Order())->where(["order_id" => $orderDate['order_id']])->find()->toArray())) {
                    foreach ($orderDetailDate as $orderDetail) {
                        Orderdetails::update(["delete_time" => time(), "inspect_ticket_status" => 2], ["id" => $orderDetail["id"]]);
                    }
                    return returnData(['code' => '200', 'msg' => "152退款成功"]);
                } else {
                    return returnData(['code' => '-1', 'msg' => "154退款失败"]);
                }
            } else {
                $orderDetailDate = (new Orderdetails)->where(["order_id" => $orderId, "inspect_ticket_status" => 1])->whereIn("id", $orderDetailsIds)->where('end_time', '>', $timeInt)->whereNull("delete_time")->select()->toArray();
                if (empty($orderDetailDate)) {
                    return returnData(['code' => '-1', 'msg' => "159无此订单"]);
                }
                if ($this->reFundorder($orderDetailDate, $orderDate)) {
                    return returnData(['code' => '200', 'msg' => "162退款成功"]);
                } else {
                    return returnData(['code' => '-1', 'msg' => "164退款失败"]);
                }
            }
        } else {
            return returnData(['code' => '-1', 'msg' => "非法请求"]);
        }
    }
    /**
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws DataNotFoundException
     */
    public function reFundMen(Request $request): Json
    {
        if ($request->isPost()) {
            $orderId = $request->post('orderId/s');
            $orderDetailsIds = $request->post('orderDetailsIds/a');
            if (empty($orderId)) {
                return returnData(['code' => '-1', 'msg' => "242非法请求"]);
            }
            $orderDate = $this->queryOrder($orderId);
            $timeInt = (string)time();
            if (empty($orderDetailsIds)) {
                $orderDetailDate = (new Orderdetails)->where(["order_id" => $orderId, "inspect_ticket_status" => 1])->where('end_time', '>', $timeInt)->whereNull("delete_time")->select()->toArray();
                if (empty($orderDetailDate)) {
                    return returnData(['code' => '-1', 'msg' => "249无此订单"]);
                }
                $count = count($orderDetailDate);
                $surplus_num = $orderDate["goods_num"] - $count;
                $reFundFeeTo = $this->reFundFeeTo($orderDate, $orderDate["order_amount"]);
                if ($reFundFeeTo["code"] != 200) {
                    return returnData($reFundFeeTo);
                }
                Order::update(["order_status" => 5, "refund_num" => $count, "surplus_num" => $surplus_num, "refund_time" => time()], ["order_id" => $orderDate['order_id']]);
                if (genggaijiage((new Order())->where(["order_id" => $orderDate['order_id']])->find()->toArray())) {
                    foreach ($orderDetailDate as $orderDetail) {
                        Orderdetails::update(["delete_time" => time(), "inspect_ticket_status" => 2], ["id" => $orderDetail["id"]]);
                    }
                    return returnData(['code' => '200', 'msg' => "262退款成功"]);
                } else {
                    return returnData(['code' => '-1', 'msg' => "264退款失败"]);
                }
            } else {
                $orderDetailDate = (new Orderdetails)->where(["order_id" => $orderId, "inspect_ticket_status" => 1])->whereIn("id", $orderDetailsIds)->where('end_time', '>', $timeInt)->whereNull("delete_time")->select()->toArray();
                if (empty($orderDetailDate)) {
                    return returnData(['code' => '-1', 'msg' => "269无此订单"]);
                }
                if ($this->reFundorder($orderDetailDate, $orderDate)) {
                    return returnData(['code' => '200', 'msg' => "272退款成功"]);
                } else {
                    return returnData(['code' => '-1', 'msg' => "274退款失败"]);
                }
            }
        } else {
            return returnData(['code' => '-1', 'msg' => "278非法请求"]);
        }
    }
    /**
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     * @throws DbException
     */
    public function reFundOrder($orderDetails, $orderDate): bool
    {
        $count = count($orderDetails);
        $order = new Order();
        $price = "0";
        foreach ($orderDetails as $orderDetail) {
            $price = bcadd($orderDetail['price'], $price, 3);
        }

        $reFundFeeDate = $this->reFundFeeTo($orderDate, $price);
        if ($reFundFeeDate["code"] != 200) {
            return false;
        }
        if (empty($orderDate["surplus_price"])) {
            $surplus_num = $orderDate["goods_num"] - $count;
            if (bccomp($orderDate['order_amount'], $price, 2) > 0) {
                Order::update(["refund_price" => $price, "refund_num" => $count, "order_status" => 4, "refund_time" => time(), "surplus_price" => bcsub((string)$orderDate['order_amount'], $price), "surplus_num" => $surplus_num], ["order_id" => $orderDate['order_id']]);
                if (genggaijiage($order->where(["order_id" => $orderDate['order_id']])->find()->toArray())) {
                    foreach ($orderDetails as $orderDetail) {
                        Orderdetails::update(["delete_time" => time(), "inspect_ticket_status" => 2], ["id" => $orderDetail["id"], "order_id" => $orderDate['order_id']]);
                    }
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            $refund_price = bcadd($price, $orderDate['refund_price'], 2);
            $refund_num = bcadd((string)$count, (string)$orderDate['refund_num']);
            $surplus_price = bcmul((string)$orderDate['goods_price'], bcsub((string)$orderDate['goods_num'], $refund_num), 2);
            if (bccomp($orderDate['surplus_price'], $price, 2) > -1) {
                if (bccomp($orderDate['surplus_price'], $price, 2) == 0) {
                    Order::update(["order_status" => 5, "refund_num" => $orderDate["goods_num"], "surplus_num" => 0, "surplus_price" => 0, "refund_price" => $orderDate['order_amount'], "refund_time" => time()], ["order_id" => $orderDate['order_id']]);
                    if (genggaijiage((new Order())->where(["order_id" => $orderDate['order_id']])->find()->toArray())) {
                        foreach ($orderDetails as $orderDetail) {
                            Orderdetails::update(["delete_time" => time(), "inspect_ticket_status" => 2], ["id" => $orderDetail["id"]]);
                        }
                        return true;
                    } else {
                        return false;
                    }
                }
                Order::update(["refund_price" => $refund_price, "refund_num" => $refund_num, "order_status" => 4, "refund_time" => time(), "surplus_price" => $surplus_price, "surplus_num" => bcsub((string)$orderDate['goods_num'], $refund_num)], ["order_id" => $orderDate['order_id']]);
                if (genggaijiage($order->where(["order_id" => $orderDate['order_id']])->find()->toArray())) {
                    foreach ($orderDetails as $orderDetail) {
                        Orderdetails::update(["delete_time" => time(), "inspect_ticket_status" => 2], ["id" => $orderDetail["id"]]);
                    }
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

    }
    /**
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     * @throws DbException
     */
    public function reFundOrderMen($orderDetails, $orderDate): bool
    {
        $count = count($orderDetails);
        $order = new Order();
        $price = "0";
        foreach ($orderDetails as $orderDetail) {
            $price = bcadd($orderDetail['price'], $price, 3);
        }

        $reFundFeeDate = $this->reFundFeeTo($orderDate, $price);
        if ($reFundFeeDate["code"] != 200) {
            return false;
        }
        if (empty($orderDate["surplus_price"])) {
            $surplus_num = $orderDate["goods_num"] - $count;
            if (bccomp($orderDate['order_amount'], $price, 2) > 0) {
                Order::update(["refund_price" => $price, "refund_num" => $count, "order_status" => 4, "refund_time" => time(), "surplus_price" => bcsub((string)$orderDate['order_amount'], $price), "surplus_num" => $surplus_num], ["order_id" => $orderDate['order_id']]);
                if (genggaijiageMen($order->where(["order_id" => $orderDate['order_id']])->find()->toArray())) {
                    foreach ($orderDetails as $orderDetail) {
                        Orderdetails::update(["delete_time" => time(), "inspect_ticket_status" => 2], ["id" => $orderDetail["id"], "order_id" => $orderDate['order_id']]);
                    }
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            $refund_price = bcadd($price, $orderDate['refund_price'], 2);
            $refund_num = bcadd((string)$count, (string)$orderDate['refund_num']);
            $surplus_price = bcmul((string)$orderDate['goods_price'], bcsub((string)$orderDate['goods_num'], $refund_num), 2);
            if (bccomp($orderDate['surplus_price'], $price, 2) > -1) {
                if (bccomp($orderDate['surplus_price'], $price, 2) == 0) {
                    Order::update(["order_status" => 5, "refund_num" => $orderDate["goods_num"], "surplus_num" => 0, "surplus_price" => 0, "refund_price" => $orderDate['order_amount'], "refund_time" => time()], ["order_id" => $orderDate['order_id']]);
                    if (genggaijiageMen((new Order())->where(["order_id" => $orderDate['order_id']])->find()->toArray())) {
                        foreach ($orderDetails as $orderDetail) {
                            Orderdetails::update(["delete_time" => time(), "inspect_ticket_status" => 2], ["id" => $orderDetail["id"]]);
                        }
                        return true;
                    } else {
                        return false;
                    }
                }
                Order::update(["refund_price" => $refund_price, "refund_num" => $refund_num, "order_status" => 4, "refund_time" => time(), "surplus_price" => $surplus_price, "surplus_num" => bcsub((string)$orderDate['goods_num'], $refund_num)], ["order_id" => $orderDate['order_id']]);
                if (genggaijiageMen($order->where(["order_id" => $orderDate['order_id']])->find()->toArray())) {
                    foreach ($orderDetails as $orderDetail) {
                        Orderdetails::update(["delete_time" => time(), "inspect_ticket_status" => 2], ["id" => $orderDetail["id"]]);
                    }
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

    }
    /**
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function reFundFeeTo($orderDate, $price)
    {
        if (empty($orderDate)) {
            return returnData(['code' => '-1', 'msg' => "137订单无法退款"]);
        }
        $padmin = (new Padmin)->where('id', $orderDate['p_id'])->find();
        if (empty($padmin)) {
            return returnData(['code' => '-1', 'msg' => "非法请求"]);
        }
        if (empty($padmin['sub_mch_id']) || empty($padmin['mch_id'])) {
            return returnData(['code' => '-1', 'msg' => "请平台商配置收款账户"]);
        }
        $padmin = $padmin->toArray();
        $accounts = (new Accounts)->where("mch_id", $padmin['mch_id'])->find();
        $data['sub_mch_id'] = $padmin['sub_mch_id'];
        $data['appid'] = $accounts['appid'];
        $data['mch_id'] = $accounts['mch_id'];
        $data['out_refund_no'] = md5(md5((string)time()));
        $data['nonce_str'] = md5((string)time());
        $data['transaction_id'] = $orderDate['transaction_id'];
        $data['refund_fee'] = $price * 100;
        $data['total_fee'] = $orderDate['order_amount'] * 100;
        $result = weixinpay($data, $accounts);
        if ($result['return_code'] == 'SUCCESS') {
            $dataTwo['sub_mch_id'] = $padmin['sub_mch_id'];
            $dataTwo['appid'] = $accounts['appid'];
            $dataTwo['mch_id'] = $accounts['mch_id'];
            $dataTwo['nonce_str'] = md5((string)time());
            $dataTwo['transaction_id'] = $orderDate['transaction_id'];
            $result1 = weixinpay($dataTwo, $accounts, 'refundOrder');
            if ($result1['return_code'] == 'SUCCESS' && $result1['result_code'] == 'SUCCESS') {
                return ['code' => 200, 'msg' => '退款申请成功'];
            } else {
                return ['code' => 40008, 'msg' => '退款申请失败，' . $result["err_code_des"]];
            }

        } else {
            return ['code' => 40008, 'msg' => '退款申请失败，' . $result["err_code_des"]];
        }

    }

    /**
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     * @throws DbException
     */
    public function queryOrder($order_id)
    {
        return (new Order)->where(["order_id" => $order_id])->whereIn("order_status", [3, 4])->find();
    }
}