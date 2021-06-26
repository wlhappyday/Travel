<?php
declare (strict_types=1);

namespace app\pay\controller;


use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use app\api\controller\AlibabaSMS;
use app\common\model\Order;
use app\common\model\Orderdetails;
use app\common\model\Padmin;
use app\common\model\Puser;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Request;
use think\response\Json;

class Service
{
    /**
     * @throws ClientException
     * @throws ServerException
     * @throws DbException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    function service(): ?Json
    {
        file_put_contents("./callback_log.txt", json_encode($_POST));
        $appid = $_POST['appid'];
        $callbacks = $_POST['callbacks'];
        $pay_type = $_POST['pay_type'];
        $amount = $_POST['amount'];
        $success_url = $_POST['success_url'];
        $error_url = $_POST['error_url'];
        $out_trade_no = $_POST['out_trade_no'];
        $pay_trade_no = $_POST['pay_trade_no'];
        $sign = $_POST['sign'];
        $data = [
            'appid' => $appid,
            'callbacks' => $callbacks,
            'pay_type' => $pay_type,
            'amount' => $amount,
            'success_url' => $success_url,
            'error_url' => $error_url,
            'out_trade_no' => $out_trade_no,
            'pay_trade_no' => $pay_trade_no,
            'sign' => $sign,
        ];
        $order1 = (new Order)->where(["order_id" => explode("_", $out_trade_no)[1], 'order_status' => 2])->find();
        if (empty($order1)) {
            return returnData(['code' => '-1', 'msg' => "非法请求36"]);
        }
        $order = $order1->toArray();
        $pAdmin = (new Padmin)->where('id', $order['p_id'])->find();
        if (empty($pAdmin)) {
            return returnData(['code' => '-1', 'msg' => "非法请求41"]);
        }
        if (empty($pAdmin['cl_id']) || empty($pAdmin['cl_key']) || empty($pAdmin['sub_mch_id']) || empty($pAdmin['mch_id'])) {
            return returnData(['code' => '-1', 'msg' => "非法请求44"]);
        }
        $pUser = (new Puser)->where('id', $order['p_user_id'])->find();
        if (empty($pUser)) {
            return returnData(['code' => '-1', 'msg' => "非法请求48"]);
        }
        if (empty($pUser['sub_mch_id']) || empty($pUser['appid']) || empty($pUser['appkey'])) {
            return returnData(['code' => '-1', 'msg' => "非法请求51"]);
        }
        $pAdmin = $pAdmin->toArray();
        if ($appid != $pAdmin['cl_id']) return returnData(['code' => '-1', 'msg' => "非法请求75"]);
        if ($this->verifySign($data, $pAdmin['cl_key']) == $sign) {
            if (genggaijiage($order, $pay_trade_no)) {
                $orderDetails = (new Orderdetails)->where(["order_id" => $order['order_id']])->select()->toArray();
                $AlibabaSMS = new AlibabaSMS();
                foreach ($orderDetails as $detail) {
                    $AlibabaSMS->sendSMS($detail['phone'], json_encode(['balance' => $detail['admission_ticket_type']]));
                }
                exit('success');
            }
        }
        return returnData(['code' => '-1', 'msg' => "非法请求86"]);
    }

    /**
     * @throws DataNotFoundException
     * @throws ClientException
     * @throws ServerException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    function serviceMen(): ?Json
    {
        file_put_contents("./callback_log_serviceMen.txt", json_encode($_POST));
        $appid = $_POST['appid'];
        $callbacks = $_POST['callbacks'];
        $pay_type = $_POST['pay_type'];
        $amount = $_POST['amount'];
        $success_url = $_POST['success_url'];
        $error_url = $_POST['error_url'];
        $out_trade_no = $_POST['out_trade_no'];
        $pay_trade_no = $_POST['pay_trade_no'];
        $sign = $_POST['sign'];
        $data = [
            'appid' => $appid,
            'callbacks' => $callbacks,
            'pay_type' => $pay_type,
            'amount' => $amount,
            'success_url' => $success_url,
            'error_url' => $error_url,
            'out_trade_no' => $out_trade_no,
            'pay_trade_no' => $pay_trade_no,
            'sign' => $sign,
        ];
        $order1 = (new Order)->where(["order_id" => explode("_", $out_trade_no)[1], 'order_status' => 2])->find();
        if (empty($order1)) {
            return returnData(['code' => '-1', 'msg' => "非法请求36"]);
        }
        $order = $order1->toArray();
        $pAdmin = (new Padmin)->where('id', $order['p_id'])->find();
        if (empty($pAdmin)) {
            return returnData(['code' => '-1', 'msg' => "非法请求41"]);
        }
        if (empty($pAdmin['cl_id']) || empty($pAdmin['cl_key']) || empty($pAdmin['sub_mch_id']) || empty($pAdmin['mch_id'])) {
            return returnData(['code' => '-1', 'msg' => "非法请求44"]);
        }
        $pAdmin = $pAdmin->toArray();
        if ($appid != $pAdmin['cl_id']) return returnData(['code' => '-1', 'msg' => "非法请求75"]);
        if ($this->verifySign($data, $pAdmin['cl_key']) == $sign) {
            if (genggaijiage($order, $pay_trade_no)) {
                $orderDetails = (new Orderdetails)->where(["order_id" => $order['order_id']])->select()->toArray();
                $AlibabaSMS = new AlibabaSMS();
                foreach ($orderDetails as $detail) {
                    $AlibabaSMS->sendSMS($detail['phone'], json_encode(['balance' => $detail['admission_ticket_type']]));
                }
                exit('success');
            }
        }
        return returnData(['code' => '-1', 'msg' => "非法请求86"]);
    }

    /**
     * @Note   验证签名
     * @param $data
     * @param $secret
     * @return bool
     */
    public function verifySign($data, $secret): bool
    {
        // 验证参数中是否有签名
        if (!isset($data['sign']) || !$data['sign']) {
            return false;
        }
        // 要验证的签名串
        $sign = $data['sign'];
        unset($data['sign']);
        // 生成新的签名、验证传过来的签名
        if ($sign == getSign($secret, $data)) {
            return true;
        }
        return false;
    }

    /**
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws DataNotFoundException
     */
    public function monitorOrders(Request $request): Json
    {
        if ($request->isPost()) {
            $orderId = $request->post('orderId');
            $order = (new Order)->where(['order_id' => $orderId, "order_status" => 3])->find()->toArray();
            if (empty($order)) {
                return returnData(["code" => 201, "msg" => "待支付"]);
            } else {
                return returnData(["code" => 200, "msg" => "支付成功"]);
            }
        }
    }

    public function fenZhang($order, $pAdmin, $pUser, $pAdminBalanceRecordsMoney, $pUserBalancerMoney)
    {

    }
}