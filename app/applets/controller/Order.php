<?php
declare (strict_types = 1);

namespace app\applets\controller;

use app\api\model\Puser;
use app\common\model\File;
use app\common\model\PuserInfo;
use app\common\model\Puseruser;
use app\platform\model\J_product;
use app\platform\model\Product_relation;
use app\platform\model\Productuser;
use think\facade\Db;
use think\Request;
use app\common\model\Order as orders;
use hg\apidoc\annotation as Apidoc;
class Order
{
    /**
     * @Apidoc\Title("创建订单")
     * @Apidoc\Desc("创建订单")
     * @Apidoc\Url("applets/order/orderadd")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("product_id", type="number",require=false, desc="产品id")
     * @Apidoc\Param("userinfo_id", type="array",require=false, desc="选中购买线路或者景区乘客的id")
     *  @Apidoc\Returned("http",type="string",desc="域名")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("msg",type="string",desc="任务提示")
     */
    public function orderadd(Request $request){
        $product_id = $request->post('product_id');
        $userinfo_id = $request->post('userinfo_id');
        $puser_id = $request->puser_id;
        $appid = $request->appid;
        $id = Puseruser::where(['appid'=>$appid,'id'=>$puser_id])->value('puser_id');
        $p_id = Puser::where('id',$id)->value('uid');
        $productuser =  Productuser::where(['user_id'=>$id,'product_id'=>$product_id,'status'=>'0'])->find();
        $product = J_product::where('id',$product_id)->field('uid,type')->find();
        $productrelationprice = Product_relation::where(['uid'=>$p_id,'product_id'=>$product_id])->value('price');
        $price = bcmul(''.count($userinfo_id).'' ,$productuser['price'],2);
        Db::startTrans();
        try {
            $order = new orders;
            $order->order_amount=$price;
            $order->total_amount=$price;
            $order->add_time = time();
            $order->user_id = $puser_id;
            $order->store_id = $product['uid'];
            $order->store_type = $product['type'];
            $order->store_good_id = $product_id;
            $order->store_price = $product['money'];
            $order->p_id = $p_id;
            $order->p_price = $productrelationprice;
            $order->p_user_id = $id;
            $order->goods_id = $productuser['id'];
            $order->goods_name = $productuser['name'];
            $order->goods_num = count($userinfo_id);
            $order->goods_price = $productuser['price'];
            $order->save();
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','sign'=>$e->getMessage()]);
        }
    }

    /**
     * @Apidoc\Title("订单列表")
     * @Apidoc\Desc("订单列表")
     * @Apidoc\Url("applets/order/orderlist")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("order_status", type="number",require=false, desc="订单状态 为空全部返回全部订单")
     * @Apidoc\Returned("http",type="string",desc="域名")
     * @Apidoc\Returned ("order",type="object",desc="产品",
     *     @Apidoc\Returned ("store_good_id",type="int",desc="产品id"),
     *     @Apidoc\Returned ("goods_name",type="varchar(11)",desc="产品名称"),
     *     @Apidoc\Returned ("goods_num",type="int",desc="购买数量"),
     *     @Apidoc\Returned ("order_amount",type="int",desc="支付价格"),
     *     @Apidoc\Returned ("add_time",type="int",desc="下单时间"),
     *     @Apidoc\Returned ("order_status",type="datetime",desc="支付状态（1正在支付2待支付（已经创建了支付订单，未输入密码或余额不足）3支付完成4订单完结）5全部退款"),
     *     @Apidoc\Returned ("end_time",type="datetime",desc="景区或者路线购买截止时间"),
     *     @Apidoc\Returned ("file_path",type="datetime",desc="产品图片"),
     *     )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("msg",type="string",desc="任务提示")
     */
    public function orderlist(Request $request){
        $puser_id = $request->puser_id;
        $appid = $request->appid;
        $order_status = $request->get('order_status');
        $id = Puseruser::where(['appid'=>$appid,'id'=>$puser_id])->value('puser_id');
        $order = orders::alias('order')->where(['order.user_id'=>$puser_id])->field('order.store_good_id,order.goods_name,order.goods_num,order.order_amount,order.add_time,order.order_status')
            ->join('j_product jp','order.store_good_id=jp.id')->field('jp.end_time')
            ->join('p_productuser pu','order.store_good_id=pu.product_id and pu.user_id='.$id)->field('pu.first_id')
            ->join('file file','file.id=pu.first_id')->field('file.file_path');
        if($order_status){
            $order->where('order.order_status',$order_status);
        }
         $orders = $order->select();
        return json(['code'=>'200','msg'=>'操作成功','order'=>$orders,'http'=>http()]);
    }

    public function orderdetail(Request $request){
        $order_id = $request->get('order_id');
        $puser_id = $request->puser_id;
        $appid = $request->appid;
        $id = Puseruser::where(['appid'=>$appid,'id'=>$puser_id])->value('puser_id');
        $order = orders::where(['order_id'=>$order_id])->field('order_id,order_status,order_amount,add_time,goods_num')->find();
    }

}