<?php
declare (strict_types = 1);

namespace app\platform\controller;

use app\common\model\Order as Orders;
use app\common\model\Orderdetails;
use think\facade\Db;
use think\Request;
use hg\apidoc\annotation as Apidoc;
class Order
{

    /**
     * @Apidoc\Title("获取订单列表")
     * @Apidoc\Desc("用户端的订单列表")
     * @Apidoc\Url("platform/order/`list")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("order_id", type="number",require=false, desc="订单id 用于搜索")
     * @Apidoc\Param("end_time", type="number",require=false, desc="结束时间 用于搜索")
     * @Apidoc\Param("start_time", type="number",require=false, desc="开始时间 用于搜索")
     * @Apidoc\Param("order_status", type="number",require=false, desc="订单状态（1正在支付2支付完成3订单完结）用于搜索")
     * @Apidoc\Param("type", type="number",require=false, desc="1景区 2线路 用于搜索")
     * @Apidoc\Param("page", type="number",require=false, desc="分页")
     * @Apidoc\Param("pagenum", type="number",require=true, desc="每页多少条数据")
     * @Apidoc\Returned("data",type="array",
     *     @Apidoc\Returned ("total",type="number",desc="分页总数"),
     *     @Apidoc\Returned ("per_page",type="int",desc="首页"),
     *     @Apidoc\Returned ("last_page",type="int",desc="最后一页"),
     *     @Apidoc\Returned ("current_page",type="int",desc="当前页"),
     *     @Apidoc\Returned("data",type="array",desc="订单数据",
     *          @Apidoc\Returned ("order_id",type="number",desc="订单id"),
     *          @Apidoc\Returned ("order_status",type="number",desc="订单状态（1正在支付2支付完成3订单完结）用于搜索"),
     *          @Apidoc\Returned ("add_time",type="number",desc="下单时间"),
     *          @Apidoc\Returned ("pay_time",type="number",desc="支付时间"),
     *          @Apidoc\Returned ("surplus_num",type="number",desc="部分退款后的保留数量"),
     *          @Apidoc\Returned ("goods_num",type="number",desc="购买数量"),
     *          @Apidoc\Returned ("refund_num",type="number",desc="退订数量"),
     *          @Apidoc\Returned ("user_name",type="number",desc="账号"),
     *          @Apidoc\Returned ("name",type="number",desc="产品名称 为线路展示这个"),
     *          @Apidoc\Returned ("class_name",type="number",desc="产品名称 为景区展示这个"),
     *          @Apidoc\Returned ("nickname",type="number",desc="景区或线路的昵称"),
     *          @Apidoc\Returned ("type",type="number",desc="1景区  2线路"),
     *      )
     * )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("msg",type="string",desc="任务提示")
     */
    public function list(Request $request){
        $uid = $request->uid;
        $type = $request->get('type');
        $order_id = $request->get('order_id');
        $pagenum = $request->get('pagenum');
        $start_time = $request->get('start_time');
        $end_time = $request->get('end_time');
        $order_status = $request->get('order_status');
        $order = orders::alias('order')
            ->where(['order.deleted'=>null,'user.delete_time'=>null,'order.p_id'=>$uid])
            ->field('order.order_id,order.order_status,order.add_time,order.pay_time,order.surplus_num,order.goods_num,order.refund_num')
            ->field('user.user_name,ju.nickname,xu.nickname')
            ->field('product.name,product.class_name,product.type')
            ->leftjoin('p_user user','user.id=order.user_id')
            ->leftjoin('j_product product','product.id=order.goods_id')
            ->leftjoin('j_user ju','ju.id=order.store_id and order.store_type=1')
            ->leftjoin('x_user xu','xu.id=order.store_id and order.store_type=2');
        if ($type){
            $order->where(['product.type'=>$type]);
        }
        if ($start_time){
            $order->whereTime('order.add_time', '>=', strtotime($start_time));
        }
        if ($order_status){
            $order->where('order.order_status',$order_status);
        }
        if ($order_id){
            $order->where('order.order_id',$order_id);
        }
        if ($end_time){
            $order->whereTime('order.add_time', '<=', strtotime($end_time));
        }
        $data = $order->paginate($pagenum)->toarray();

        return returnData(['data'=>$data,'code'=>'200']);
    }

    /**
     * @Apidoc\Title("获取订单详情")
     * @Apidoc\Desc("用户端的订单列表")
     * @Apidoc\Url("platform/order/detail")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("order_id", type="number",require=true, desc="订单id         ")
     * @Apidoc\Returned("data",type="array",desc="订单详情",ref="app\common\model\Orderdetails\order_detail")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("msg",type="string",desc="任务提示")
     */
    public function Detail(Request $request){
        $order_id = $request->get('order_id');
        if (!$order_id){
            return json(['code'=>'201','sign'=>'缺少参数order_id']);
        }
        if ($order_id){
            $where['order_id'] = $order_id;
        }
        $order_result = new Orderdetails();
        $data = $order_result->where($where)->field('order_id,name,id_card,phone,admission_ticket_type,inspect_ticket_details,inspect_ticket_status')->select();
        return returnData(['data'=>$data,'code'=>'200']);
    }

}