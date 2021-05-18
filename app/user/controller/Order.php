<?php
declare (strict_types = 1);

namespace app\user\controller;

use app\platform\model\J_product;
use app\platform\model\Productuser;
use think\Request;
use think\exception\ValidateException;
use think\facade\Db;
use app\common\model\JproductRecords;
use PHPExcel_IOFactory;
use app\common\model\Orderdetails;
use app\common\model\Order as orders;
use PHPExcel;
use hg\apidoc\annotation as Apidoc;
use think\file\UploadedFile;
/**
 *
 * @Apidoc\Title("订单")
 * @Apidoc\Group("order")
 */
class Order
{

    /**
     * @Apidoc\Title("获取路线和景区")
     * @Apidoc\Desc("订单导入的时候所需要的产品列表")
     * @Apidoc\Url("user/order/productlist")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("name", type="number",require=false, desc="产品名称 用于搜索")
     * @Apidoc\Param("end_time", type="number",require=false, desc="结束时间 用于搜索")
     * @Apidoc\Param("start_time", type="number",require=false, desc="开始时间 用于搜索")
     * @Apidoc\Param("type", type="number",require=true, desc="产品类型1 或者 2")
     * @Apidoc\Param("pagenum", type="number",require=true, desc="每页多少条数据")
     * @Apidoc\Param("page", type="number",require=true, desc="分页")
     * @Apidoc\Returned ("product",type="object",desc="产品",
     *     @Apidoc\Returned ("mp_name",type="int",desc="门票类型 1全价票2半价票3免费票4景区年卡5套票"),
     *     @Apidoc\Returned ("name",type="varchar(11)",desc="产品名称"),
     *     @Apidoc\Returned ("product_id",type="int",desc="产品id"),
     *     @Apidoc\Returned ("price",type="int",desc="产品价格"),
     *     @Apidoc\Returned ("number",type="int",desc="产品库存"),
     *     @Apidoc\Returned ("create_time",type="datetime",desc="添加时间"),
     *     @Apidoc\Returned ("file_path",type="varchar(255)",desc="产品图片")
     *     )
     *  @Apidoc\Returned("http",type="string",desc="域名")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("msg",type="string",desc="任务提示")
     */
    public function product_list(Request $request){
        $type = $request->get('type');
        $name = $request->get('name');
        $pagenum = $request->get('pagenum');
        $start_time = $request->get('start_time');
        $end_time = $request->get('end_time');
        if (!$type){
            return json(['code'=>'201','sign'=>'缺失参数type']);
        }
        try {
            $product = J_product::alias('JP')
                ->join('p_productuser pr','pr.product_id=JP.id')->where('JP.type',$type)->field('file.file_path,JP.mp_name,JP.type,pr.class_name,pr.name,pr.product_id,pr.price,JP.number,pr.create_time,JP.status')
                ->leftjoin('file file','file.id=JP.first_id')
                ->where('JP.status','0')->where('pr.status','0')->where([['JP.name', 'like','%'.$name.'%']]);
            if ($start_time){
                $product->whereTime('pr.create_time', '>=', strtotime($start_time));
            }
            if ($end_time){
                $product->whereTime('pr.create_time', '<=', strtotime($end_time));
            }
            $products = $product->order('pr.create_time','desc')->paginate($pagenum)->toArray();
            return json(['code'=>'200','msg'=>'操作成功','product'=>$products,'http'=>http()]);
        } catch (\Throwable $e) {
            return json(['code'=>'201','msg'=>'操作成功','sign'=>$e->getMessage()]);
        }
    }

    /**
     * @Apidoc\Title("订单导入")
     * @Apidoc\Desc("订单导入")
     * @Apidoc\Url("user/order/orderadd")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("product_id", type="number",require=true, desc="产品id")
     * @Apidoc\Param("file", type="file",require=true, desc="exce文档")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("msg",type="string",desc="任务提示")
     */
    public function order_add(Request $request){
        $id = $request->id;
        $uid = $request->uid;
        $product_id = $request->post('product_id');
        if (!$product_id){
            return json(['code'=>'201','sign'=>'请检查参数product_id']);
        }
        $price = Productuser::where(['product_id'=>$product_id,'user_id'=>$id])->value('price');
        Db::startTrans();
        try {
            $product = J_product::alias('JP')->where(['JP.status'=>'0','JP.id'=>$product_id])
                ->join('p_productuser pu','pu.product_id=JP.id')
                ->field('pu.id,pu.title,JP.type,pu.price,JP.uid,JP.type,pu.name,JP.number,JP.mp_id,JP.money')
                ->find();
            $file = $request->file('file');
            validate(['imgFile' => [
                'fileSize' => 2024888,
                'fileExt' => 'xls,xlsx',
            ]])->check(['imgFile' => $file]);

            $ext = $file->getOriginalExtension();
            $savename = \think\facade\Filesystem::disk('public')->putFile( 'file', $file);
            $path = public_path().'storage/'.$savename;
            if($ext=="xlsx"){
                $reader = \PHPExcel_IOFactory::createReader('Excel2007');
            }else{
                $reader = \PHPExcel_IOFactory::createReader('Excel5');
            }
            $excel = $reader->load($path,$encode = 'utf-8');

            $sheet = $excel->getSheet(0)->toArray();
            array_shift($sheet);
            if ($product['number']<count($sheet)){
                return json(['code'=>'201','sign'=>'库存不足，剩余'.$product['number']]);
            }
            $bcmul = bcmul(''.count($sheet).'',$price,2);
            $order = new orders();
            $order->store_price =$product['money'];
            $order->p_price =$price;
            $order->order_amount = $bcmul;
            $order->total_amount = $bcmul;
            $order->add_time = time();
            $order->store_id = $product['uid'];
            $order->store_type = $product['type'];
            $order->p_id =$uid;
            if ($product['type'] == '1'){
                $order->admission_ticket_type = $product['mp_id'];
            }
            $order->p_user_id = $id;
            $order->goods_id = $product['id'];
            $order->goods_name = $product['name'];
            $order->goods_num = count($sheet);
            $order->goods_price = $price;
            $order->save();
            $i = 0;
            foreach ($sheet as $k => $v) {
                if(is_numeric($v[1])){
                    $v[1] = (int)$v[1];
                }
                $Orderdetails = new Orderdetails;
                $Orderdetails->save([
                    'name'=>$v[0],
                    'id_card'=>$v[1],
                    'order_id'=>$order['id'],
                    'phone'=>$v[2],
                    'type'=>$product['type'],
                    'price'=>$price
                ]);
                $i++;
            }
            $number = bcsub(''.$product['number'].'',''.count($sheet).'');
            $J_product = J_product::where('id',$product_id)->find();
            $J_product->number = $number;
            $J_product->save();
            JproductRecords::create([
                'product_id'=>$product_id,
                'uid'=>$id,
                'type'=>$product['type'],
                'class'=>'2',
                'before_number'=>$product['number'],
                'number'=>count($sheet),
                'after_number'=>$number,
                'data_id'=>$order['id'],
                'descript'=>'导入订单处理减库存'.count($sheet)
            ]);
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code'=>'201','sign'=>$e->getMessage()]);
        }
        return json(['code'=>'-1','msg'=>'网络繁忙']);
    }


    /**
     * @Apidoc\Title("获取订单列表")
     * @Apidoc\Desc("用户端的订单列表")
     * @Apidoc\Url("user/order/orderlist")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("order_id", type="number",require=false, desc="订单id 用于搜索")
     * @Apidoc\Param("good_name", type="number",require=false, desc="产品名称 用于搜索")
     * @Apidoc\Param("end_time", type="number",require=false, desc="结束时间 用于搜索")
     * @Apidoc\Param("start_time", type="number",require=false, desc="开始时间 用于搜索")
     * @Apidoc\Param("order_status", type="number",require=true, desc="订单状态（1正在支付2支付完成3订单完结）用于搜索")
     * @Apidoc\Param("order_status", type="number",require=true, desc="订单状态（1正在支付2支付完成3订单完结）用于搜索")
     * @Apidoc\Param("store_type", type="number",require=true, desc="1景区2线路 用于搜索")
     * @Apidoc\Param("page", type="number",require=true, desc="分页")
     * @Apidoc\Param("pagenum", type="number",require=true, desc="每页多少条数据")
     * @Apidoc\Returned("order",type="object",desc="路线",ref="app\common\model\Order\order_list")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("msg",type="string",desc="任务提示")
     */
    public function order_list(Request $request){
        $order_id = $request->get('order_id');
        $id = $request->id;
        $pagenum = $request->get('pagenum');
        $start_time = $request->get('start_time');
        $end_time = $request->get('end_time');
        $order_status = $request->get('order_status');
        $store_type = $request->get('store_type');
        $goods_name = $request->get('goods_name');
        $order = orders::order('order_id','Desc')->where('p_user_id',$id)->where([['goods_name','like','%'.$goods_name.'%']])
            ->field('order_id,transaction_id,order_status,coupon_price,order_amount,total_amount,add_time,pay_time,refund_price,surplus_price,is_checkout,store_type,goods_name,goods_num,goods_price,refund_num,refund_price');
        if ($start_time){
            $order->whereTime('add_time', '>=', strtotime($start_time));
        }
        if ($order_status){
            $order->where('order_status',$order_status);
        }
        if ($order_id){
            $order->where('order_id',$order_id);
        }
        if ($store_type){
            $order->where('store_type',$store_type);
        }
        if ($end_time){
            $order->whereTime('add_time', '<=', strtotime($end_time));
        }
        $orders = $order->order('order_id','desc')->paginate($pagenum)->toArray();

        return json(['code'=>'200','msg'=>'操作成功','order'=>$orders]);
    }


    /**
     * @Apidoc\Title("获取订单详情")
     * @Apidoc\Desc("用户端的订单详情")
     * @Apidoc\Url("user/order/orderdetail")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("order_id", type="number",require=true, desc="订单id")
     * @Apidoc\Returned("Orderdetails",type="object",desc="订单用户列表",ref="app\common\model\Orderdetails\order_detail")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("msg",type="string",desc="任务提示")
     */
    public function order_detail(Request $request){
        $order_id = $request->get('order_id');
        if ($order_id){
            try{
                $Orderdetails = Orderdetails::where(['order_id'=>$order_id])->field('name,id_card,order_id,delete_time,admission_ticket_type,phone,price')->select()->toArray();
                return json(['code'=>'200','msg'=>'操作成功','Orderdetails'=>$Orderdetails]);
            }catch (\Exception $e){
                return json(['code'=>'201','sign'=>$e->getMessage()]);
            }
        }
        return json(['code'=>'201','sign'=>'缺失参数order_id']);
    }

    /**
     * @Apidoc\Title("修改订单优惠价格")
     * @Apidoc\Desc("修改总订单优惠价格")
     * @Apidoc\Url("user/order/ordercouponprice")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("order_id", type="number",require=true, desc="订单id")
     * @Apidoc\Param("coupon_price", type="number",require=true, desc="优惠价格")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("msg",type="string",desc="任务提示")
     */
    public function ordercouponprice(Request $request){
        $order_id = $request->post('order_id');
        $coupon_price = $request->post('coupon_price');
        if ($order_id && $coupon_price){
            Db::startTrans();
            try {
                $order = orders::where('order_id', $order_id)->field('p_price,order_amount,coupon_price')->find();
                $count = Orderdetails::where('order_id', $order_id)->count();
                $price = bcmul($order['p_price'],''.$count.'',2);
                $pprice =bcmul($order['p_price'],'0.03',2);
                $zprice =bcadd($pprice,$price,2);//平台商的价格
                $zzprice = bcsub($order['order_amount'],$zprice,2);//总价减平台商的价格
//                $zcouponprice = bcsub($order['order_amount'],$zprice,2);//总价减优惠价格
                if($zzprice <= '0'){
                    return json(['code'=>'201','sign'=>'该订单无法修改']);
                }
                if ($zzprice < $coupon_price){
                    return json(['code'=>'201','sign'=>'优惠价格不能高于'.$zzprice]);
                }
                $order->coupon_price = $price;
                $order->order_amount = bcsub($order['order_amount'],$price,2);
                $order->save();
                Db::commit();
                return json(['code'=>'200','msg'=>'操作成功']);
            }catch (\Exception $e){
                Db::rollback();
                return json(['code'=>'201','sign'=>$e->getMessage()]);
            }
        }
        return json(['code'=>'201','sign'=>'参数错误']);
    }
}
