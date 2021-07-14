<?php
declare (strict_types = 1);

namespace app\user\controller;

use app\common\model\Puseruser;
use app\platform\model\P_user;
use app\common\model\PuserUserBalanceRecords;
use app\common\model\Order;
use think\facade\Db;
use think\Request;
use hg\apidoc\annotation as Apidoc;
/**
 *
 * @Apidoc\Title("分销管理")
 * @Apidoc\Group("distribution")
 */
class Distribution
{

    /**
     * @Apidoc\Title("分销商列表")
     * @Apidoc\Desc("分销商列表")
     * @Apidoc\Url("user/distribution/userlist")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Param("pagenum", type="string",require=false, desc="分页数量" )
     * @Apidoc\Param("nickname", type="string",require=false, desc="微信昵称" )
     * @Apidoc\Param("name", type="string",require=false, desc="姓名" )
     * @Apidoc\Param("is_distcenter", type="string",require=false, desc="审核状态  是否是分销商  1已同意 2申请中  3已拒绝" )
     * @Apidoc\Returned("data",type="object",desc="列表",ref="app\common\model\Puseruser\zhu")
     */
    public function userlist(Request  $request){
        $id = $request->id;
        $is_distcenter = $request->get('is_distcenter');
        if ($request->isGet()){
            $name = $request->get('name');
            $nickname =  $request->get('nickname');
            $pagenum = $request->get('pagenum');
            $admin = Puseruser::where(['puser_id'=>$id])->whereIn('is_distcenter',[1,2,3 ])->order('distcenter_time','Desc')
                ->field('id,avatar,nickname,name,phone,is_distcenter,offline_count,distcenter_time,distcenters_time')
                ->where('is_distcenter','like','%'.$is_distcenter.'%')
                ->where('name','like','%'.$name.'%')
                ->where('nickname','like','%'.$nickname.'%')->paginate($pagenum);
            return json(['code'=>'200','msg'=>'操作成功','data'=>$admin]);
        }
        return json(['code'=>'201','msg'=>'请用GET访问']);
    }

    /**
     * @Apidoc\Title("分销商审核")
     * @Apidoc\Desc("分销商审核")
     * @Apidoc\Url("user/distribution/isDistcenter")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Param("puser_id", type="string",require=false, desc="分销商列表的id" )
     * @Apidoc\Param("is_distcenter", type="string",require=false, desc="审核状态  是否是分销商  1已同意 2申请中  3已拒绝" )
     */
    public function isDistcenter(Request  $request){
        $is_distcenter = $request->POST('is_distcenter');
        $id = $request->post('puser_id');
        if ($request->isPost()){
            Db::startTrans();
            try {
                $admin = Puseruser::where('id',$id)->field('is_distcenter,pid')->find();
                $admin->is_distcenter=$is_distcenter;
                $admin->save();
                if ($is_distcenter=='1'){
                    if ($admin['pid']!='0'){

                        $admins = Puseruser::where('id',$admin['pid'])->find();
                        if ($admins){
                            $admins->offline_count = bcsub(''.$admins['offline_count'].'', '1');
                            $admins->save();
                            $admin->pid = 0;
                            $admin->save();
                        }

                    }
                    addPuserLog(getDecodeToken(),'审核分销商通过：'.$id);
                }
                if ($is_distcenter=='2'){
                    addPuserLog(getDecodeToken(),'审核分销商拒绝：'.$id);
                }
                Db::commit();
                return json(['code'=>'200','msg'=>'操作成功']);
            }catch (\Exception $e){
                Db::rollback();
                return json(['code'=>'201','msg'=>'网络繁忙']);
            }
        }
        return json(['code'=>'201','msg'=>'请用POST提交']);
    }


    /**
     * @Apidoc\Title("修改分销商比例")
     * @Apidoc\Desc("修改分销商比例")
     * @Apidoc\Url("user/distribution/distcenterPrice")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Param("distribution", type="string",require=true, desc="分销比例" )
     */
    public function distcenterPrice(Request  $request){
        $id = $request->id;
        $distribution = $request->post('distribution');
        if ($request->isPost()){
            Db::startTrans();
            try {
                 P_user::where('id',$id)->update([
                    'distribution'=>$distribution
                ]);
                addPuserLog(getDecodeToken(),'修改分销商比例：'.$id);
                Db::commit();
                return json(['code'=>'200','msg'=>'操作成功']);
            }catch (\Exception $e){
                Db::rollback();
                return json(['code'=>'201','msg'=>'网络繁忙']);
            }
        }
        return json(['code'=>'201','msg'=>'请用POST提交']);
    }


    /**
     * @Apidoc\Title("分销商订单列表")
     * @Apidoc\Desc("分销商订单列表")
     * @Apidoc\Url("user/distribution/distcenterOrder")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Param("pagenum", type="string",require=false, desc="分页数量" )
     * @Apidoc\Param("nickname", type="string",require=false, desc="微信昵称" )
     * @Apidoc\Param("order_id", type="string",require=false, desc="订单编号" )
     * @Apidoc\Param("start_time", type="string",require=false, desc="下单开始时间" )
     * @Apidoc\Param("end_time", type="string",require=false, desc="下单结束时间" )
     * @Apidoc\Returned("data",type="object",desc="列表",
     *     @Apidoc\Returned ("total",type="string",desc="全部数据"),
     *     @Apidoc\Returned ("per_page",type="string",desc="每页数据"),
     *     @Apidoc\Returned ("current_page",type="string",desc="当前页"),
     *     @Apidoc\Returned ("last_page",type="string",desc="最后一页"),
     *     @Apidoc\Returned ("data",type="object",desc="列表",
     *          @Apidoc\Returned ("order_id",type="string",desc="订单编号"),
     *          @Apidoc\Returned ("order_status",type="string",desc="订单状态  支付状态（1正在支付2待支付（已经创建了支付订单，未输入密码或余额不足）3支付完成 4部分退款中  5全部退款 6订单完结"),
     *          @Apidoc\Returned ("total_amount",type="string",desc="订单总价"),
     *          @Apidoc\Returned ("pay_time",type="string",desc="支付时间"),
     *          @Apidoc\Returned ("add_time",type="string",desc="下单时间"),
     *          @Apidoc\Returned ("goods_name",type="string",desc="商品名称"),
     *          @Apidoc\Returned ("goods_num",type="string",desc="购买数量"),
     *          @Apidoc\Returned ("goods_price",type="string",desc="单价"),
     *          @Apidoc\Returned ("file_path",type="string",desc="图片"),
     *          @Apidoc\Returned ("nickname",type="string",desc="微信昵称"),
     *      )
     * )
     * @Apidoc\Returned ("http",type="string",desc="url"),
     */
    public function distcenterOrder(Request  $request){

        if ($request->isGet()){
            $id = $request->id;
            $pagenum = $request->get('pagenum');
            $start_time  = $request->get('start_time');
            $end_time  = $request->get('end_time');
            $order_id  = $request->get('order_id');
            $nickname  = $request->get('nickname');
            $order = Order::alias('order')->where(['order.p_user_id'=>$id])
                ->where('order_id','like','%'.$order_id.'%')
                ->where('nickname','like','%'.$nickname.'%')
                ->field('order.order_id,order.order_status,order.total_amount,order.pay_time,order.add_time,order.goods_name,order.goods_num,order.goods_price')
                ->field('file.file_path,juu.nickname')
                ->join('p_user_user_balance_records upb','upb.data_id=order.order_id')
                ->join('p_productuser pp','pp.id=order.goods_id')
                ->join('p_user_user juu','order.user_id=juu.id')
                ->join('file file','file.id=pp.first_id');
            if ($start_time){
                $order->whereTime('order.add_time', '>=', strtotime($start_time));
            }
            if ($end_time){
                $order->whereTime('order.add_time', '<=', strtotime($end_time));
            }
            $data = $order->paginate($pagenum);
            return json(['code'=>'200','msg'=>'操作成功','data'=>$data,'http'=>http()]);
        }
        return json(['code'=>'201','msg'=>'请用GET访问']);

    }
}
