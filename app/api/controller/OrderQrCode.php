<?php


namespace app\api\controller;


use app\common\model\Orderdetails;
use lib\Haxi;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\response\Json;

class OrderQrCode
{
    /**
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     * @throws DbException
     */
    public function orderDecrupt(): Json
    {
        $ticketDetails = input('post.ticketDetails', '', 'strip_tags');
        if (empty($ticketDetails)) {
            return returnData(['msg' => '非法请求', "code" => 201]);
        }
        $Haxi = new Haxi();

        $orderDate = $Haxi->decrypt($ticketDetails);
        if (isset($orderDate["code"])) {
            return returnData($orderDate);
        }
        $orderDetails = (new Orderdetails)->where(["id" => $orderDate["id"], "order_id" => $orderDate["order_id"]])->find()->toArray();
        if (empty($orderDetails)) {
            return returnData(['msg' => '票务出错', "code" => 201]);
        }
        if ($orderDetails["inspect_ticket_status"] == 2) {
            return returnData(['msg' => '无效门票', "name" => $orderDetails["name"], "code" => 201]);
        }
        if ($orderDetails["inspect_ticket_status"] == 3) {
            return returnData(['msg' => '门票已使用', "name" => $orderDetails["name"], "code" => 201]);
        }
        if (!empty($orderDetails["delete_time"])) {
            return returnData(['msg' => '门票已退款', "name" => $orderDetails["name"], "code" => 201]);
        }
        if ($orderDetails["end_time"] < time()) {
            return returnData(['msg' => '门票已过期', "name" => $orderDetails["name"], "code" => 201]);
        }
        Orderdetails::update(["inspect_ticket_status" => 3], ["id" => $orderDate["id"], "order_id" => $orderDate["order_id"]]);
        return returnData(['type' => $orderDetails["admission_ticket_type"], "status" => $orderDetails["inspect_ticket_status"], "name" => $orderDetails["name"], "code" => 200]);
    }
}