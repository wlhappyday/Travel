<?php
declare (strict_types=1);

namespace app\pay\controller;


use app\common\model\JuserBalanceRecords;
use app\common\model\Order;
use app\common\model\Padmin;
use app\common\model\PadminBalanceRecords;
use app\common\model\Puser;
use app\common\model\Puserbalancerecords;
use app\common\model\XuserBalanceRecords;

class Service
{
    function service()
    {
        file_put_contents("./callback_log.txt", json_encode($_POST));
        $appid = $_POST['appid'];
        $callbacks = $_POST['callbacks'];
        $pay_type = $_POST['pay_type'];
        $amount = $_POST['amount'];
        $success_url = $_POST['success_url'];
        $error_url = $_POST['error_url'];
        $out_trade_no = $_POST['out_trade_no'];
        $sign = $_POST['sign'];
        $data = [
            'appid' => $appid,
            'callbacks' => $callbacks,
            'pay_type' => $pay_type,
            'amount' => $amount,
            'success_url' => $success_url,
            'error_url' => $error_url,
            'out_trade_no' => $out_trade_no,
            'sign' => $sign,
        ];

        $order1 = Order::where(["order_id" => $out_trade_no, 'order_status' => 2])->find();
        if (empty($order1)) {
            return returnData(['code' => '-1', 'msg' => "非法请求36"], 200);
        }
        $order = $order1->toArray();
        $pAdmin = Padmin::where('id', $order['p_id'])->find();
        if (empty($pAdmin)) {
            return returnData(['code' => '-1', 'msg' => "非法请求41"], 200);
        }
        if (empty($pAdmin['mch_id']) || empty($pAdmin['mch_key'])) {
            return returnData(['code' => '-1', 'msg' => "非法请求44"], 200);
        }
        $pUser = Puser::where('id', $order['p_user_id'])->find();
        if (empty($pUser)) {
            return returnData(['code' => '-1', 'msg' => "非法请求48"], 200);
        }
        if (empty($pUser['sub_mch_id']) || empty($pUser['appid']) || empty($pUser['appkey'])) {
            return returnData(['code' => '-1', 'msg' => "非法请求51"], 200);
        }
        $pUser = $pUser->toArray();
        $pAdmin = $pAdmin->toArray();
        if ($appid != $pAdmin['mch_id']) exit('error:appid');
        if ($this->verifySign($data, $pAdmin['mch_key']) == $sign) {
            $this->genggaijiage($order, $pAdmin, $pUser);
            exit('success');
        } else {
            exit('error:sign');
        }
    }

    /**
     * @Note   验证签名
     * @param $data
     * @param $orderStatus
     * @return bool
     */
    public function verifySign($data, $secret)
    {
        // 验证参数中是否有签名
        if (!isset($data['sign']) || !$data['sign']) {
            return false;
        }
        // 要验证的签名串
        $sign = $data['sign'];
        unset($data['sign']);
        // 生成新的签名、验证传过来的签名

        return getSign($secret, $data);
    }

    public function genggaijiage($order, $pAdmin, $pUser)
    {
        if ($order['order_status'] == 2) {
            Order::update(["order_id" => $order['order_id'], "order_status" => 3, "transaction_id" => $order['order_id'], "pay_time" => time()]);
            $pAdminBalanceRecordsMoney = bcsub($order['p_price'], $order["store_price"]);
            PadminBalanceRecords::create(["data_id" => $order['order_id'], "uid" => $order['p_id'], "p_price" => $order['p_price'], "money" => $pAdminBalanceRecordsMoney]);
            $dateEEEEEEE = ["data_id" => $order['order_id'], "uid" => $order['store_id'], "p_price" => $order['store_price'], "money" => $order['store_price']];
            $pUserBalancerMoney = bcsub($order['goods_price'], $order["p_price"]);
            Puserbalancerecords::create(["data_id" => $order['order_id'], "uid" => $order['store_id'], "p_price" => $order['goods_price'], "money" => $pUserBalancerMoney]);
            if ($order['store_type'] == 1) {
                JuserBalanceRecords::create($dateEEEEEEE);
            } elseif ($order['store_type'] == 2) {
                XuserBalanceRecords::create($dateEEEEEEE);
            }
            return true;
        }
//        $dateFenZhang = [
//
//        ];
//        $url = 'https://xcxapi.payunke.com/index/unifiedorder111111?format=jsonIn';
//        $paydata['appid'] = $pAdmin['mch_id'];
//        $paydata['out_trade_no'] = $order['order_id'];
//        $paydata['pay_type'] = 'weChatJsFzLy';
//        $paydata['amount'] = sprintf("%.2f",$order['order_amount']);
//        $paydata['callback_url'] = 'http://platformzcm.february202.cn/api/aliPay/callback';
//        $paydata['success_url'] = 'http://www.baidu.com';
//        $paydata['error_url'] = 'http://www.baidu.com';
//        $paydata['extend'] =  json_encode([
//            'appid'=>Config::where("title","appid")->value("value"),
//            'sub_appid'=>$pUser['appid'],
//            'secret'=>$pUser['appkey'],
//            'mch_id'=>Config::where("title","mch_id")->value("value"),
//            'key'=>Config::where("title","key")->value("value"),
//            'sub_mch_id'=>$pUser['sub_mch_id']
//        ],JSON_UNESCAPED_UNICODE);
//        $paydata['sign'] = getSign($pAdmin['mch_key'],$paydata);
//        $dateeeeeeee=post($url,$paydata);
//        return $dateeeeeeee;
//        p($dateFenZhang);
//        $this->fenZhang($order,$pAdmin,$pUser,$pAdminBalanceRecordsMoney,$pUserBalancerMoney);
    }

    public function fenZhang($order, $pAdmin, $pUser, $pAdminBalanceRecordsMoney, $pUserBalancerMoney)
    {

    }
}