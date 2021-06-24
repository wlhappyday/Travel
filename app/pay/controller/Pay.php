<?php
declare (strict_types=1);

namespace app\pay\controller;

use app\common\model\Config;
use app\common\model\Order;
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
                return returnData(['code' => '-1', 'msg' => "非法请求"], 200);
            }
            $order = Order::where('order_id', $orderId)->find();
            if (empty($order)) {
                return returnData(['code' => '-1', 'msg' => "非法请求"], 200);
            }
            $order = $order->toArray();
            $padmin = Padmin::where('id', $order['p_id'])->find();
            if (empty($padmin)) {
                return returnData(['code' => '-1', 'msg' => "非法请求"], 200);
            }
            if (empty($padmin['cl_id']) || empty($padmin['cl_key']) || empty($padmin['sub_mch_id']) || empty($padmin['mch_id'])) {
                return returnData(['code' => '-1', 'msg' => "请平台商配置收款账户"], 200);
            }
            $pUser = Puser::where('id', $order['p_user_id'])->find();
            if (empty($pUser)) {
                return returnData(['code' => '-1', 'msg' => "非法请求"], 200);
            }
            if (empty($pUser['sub_mch_id']) || empty($pUser['appid']) || empty($pUser['appkey'])) {
                return returnData(['code' => '-1', 'msg' => "请门店配置收款账户"], 200);
            }
            $pUser = $pUser->toArray();
            $padmin = $padmin->toArray();
            echo $this->payTo($order, $padmin, $exent, $pUser);
            die();
        } else {
            return returnData(['code' => '-1', 'msg' => "非法请求"], 200);
        }
    }

    public function payTo($order, $padmin, $exent, $pUser)
    {
        $url = 'https://xcxapi.payunke.com/index/unifiedorder111111?format=jsonIn';
        $paydata['appid'] = $padmin['mch_id'];
        $paydata['out_trade_no'] = $order['order_id'];
        $paydata['pay_type'] = 'weChatJsFzLy';
        $paydata['amount'] = sprintf("%.2f", $order['order_amount']);
        $paydata['callback_url'] = 'http://platformzcm.february202.cn/api/aliPay/callback';
        $paydata['success_url'] = 'http://www.baidu.com';
        $paydata['error_url'] = 'http://www.baidu.com';
//            {"appid":"wxaf1f2c344b7ffa78",
//        "sub_appid":"wx6117e2540ee05b55",
//        "secret":"8200d555ea52c45fb82d2b51de506514",
//        "mch_id":"1285253401",
//        "key":"c5258aaf86c5cc46df64cfe9af0b9791",
//        "sub_mch_id":"1517514021"}
        $paydata['extend'] = json_encode([
            'appid' => Account::where("title", "appid")->value("value"),
            'sub_appid' => $pUser['appid'],
            'secret' => $pUser['appkey'],
            'js_code' => $exent['js_code'],
            'gname' => $exent['gname'],
            'mch_id' => Config::where("title", "mch_id")->value("value"),
            'key' => Config::where("title", "key")->value("value"),
            'sub_mch_id' => $pUser['sub_mch_id']
        ], JSON_UNESCAPED_UNICODE);
        $paydata['sign'] = getSign($padmin['mch_key'], $paydata);
        $dateeeeeeee = post($url, $paydata);
        return $dateeeeeeee;
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