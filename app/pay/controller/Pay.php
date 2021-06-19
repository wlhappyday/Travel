<?php
declare (strict_types=1);

namespace app\pay\controller;

use app\common\model\Config;
use app\common\model\Order;
use app\common\model\Padmin;
use app\common\model\Puser;
use think\Request;

class Pay
{
    public function index(Request $request)
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
            if (empty($padmin['mch_id']) || empty($padmin['mch_key'])) {
                return returnData(['code' => '-1', 'msg' => "请平台商配置手续费账户"], 200);
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

    function payTo($order, $padmin, $exent, $pUser)
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
            'appid' => Config::where("title", "appid")->value("value"),
            'sub_appid' => $pUser['appid'],
            'secret' => $pUser['appkey'],
            'js_code' => $exent['js_code'],
            'gname' => $exent['gname'],
            'mch_id' => Config::where("title", "mch_id")->value("value"),
            'key' => Config::where("title", "key")->value("value"),
            'sub_mch_id' => $pUser['sub_mch_id']
        ], JSON_UNESCAPED_UNICODE);
        $paydata['sign'] = $this->getSign($padmin['mch_key'], $paydata);
        $dateeeeeeee = $this->post($url, $paydata);
        return $dateeeeeeee;
    }

    function getSign($secret, $data): string
    {
        // 去空
        $data = array_filter($data);
        //签名步骤一：按字典序排序参数
        ksort($data);
        if ($data['pay_type'] == 'AliRoyalty') {
            foreach ($data['royalty_parameters'] as $k => $v) {
                ksort($data['royalty_parameters'][$k]);
            }
        }
        $string_a = http_build_query($data);
        $string_a = urldecode($string_a);
        //签名步骤二：在string后加入mch_key
        $string_sign_temp = $string_a . "&key=" . $secret;
        // var_dump($string_sign_temp);
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);
        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);
        // var_dump($result);
        return $result;
    }

    function post($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        //   curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        $output = curl_exec($ch);
        //   var_dump(curl_error($ch));die();
        curl_close($ch);
        return $output;
    }
}