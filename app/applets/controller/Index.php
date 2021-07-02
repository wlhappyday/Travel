<?php
declare (strict_types = 1);

namespace app\applets\controller;

use app\common\model\File;
use app\common\model\Pusermy;
use app\platform\model\Productuser;
use app\common\model\XproductClass;
use app\user\model\Config;
use app\common\model\Puserpage;
use app\common\model\Pusermagic;
use app\common\model\Puserhomenavigation;
use app\common\model\Pusernavigation;
use think\facade\Db;
use think\Request;
use app\common\model\Puseruser;
use app\api\model\Puser;
use app\common\model\Pcarousel;
use app\common\model\Pusercollection;
use hg\apidoc\annotation as Apidoc;
/**
 *
 * @Apidoc\Title("首页")
 * @Apidoc\Group("index")
 */
class Index
{

    public function tabBar(Request $request){
        $appid = $request->POST('appid');
        if (empty($appid)){
            return json(['code'=>'201','msg'=>'appid不能为空']);
        }
        $puser = Puser::where('appid',$appid)->field('id,dinavigationtcolor,dinavigationtcolors')->find();
        $navigation = Pusernavigation::where('user_id',$puser['id'])->order('navigation_id','Desc')->field('img,imgs,page_id,title')->select();
        foreach($navigation as $key=>$value){
            $navigation[$key]['page_id'] = Puserpage::where('id',$value['page_id'])->value('page');
            $navigation[$key]['img'] = http().File::where('id',$value['img'])->value('file_path');
            $navigation[$key]['imgs'] = http().File::where('id',$value['imgs'])->value('file_path');
        }
        return json(['code'=>'200','msg'=>'操作成功','navigation'=>$navigation,'puser'=>$puser]);
    }

    /**
     * @Apidoc\Title("获取热门路线和热门景区")
     * @Apidoc\Desc("订单导入的时候所需要的产品列表")
     * @Apidoc\Url("user/index/index")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned("img", type="array",require=false, desc="轮播图")
     * @Apidoc\Param("http", type="number",require=false, desc="域名")
     * @Apidoc\Param("city", type="number",require=false, desc="城市名称")
     * @Apidoc\Returned ("rjproduct",type="object",desc="产品",
     *     @Apidoc\Returned ("file_path",type="int",desc="产品图片"),
     *     @Apidoc\Returned ("class_name",type="varchar(11)",desc="产品名称"),
     *     @Apidoc\Returned ("product_id",type="int",desc="产品id"),
     *     @Apidoc\Returned ("price",type="int",desc="产品价格"),
     *     @Apidoc\Returned ("number",type="int",desc="产品库存"),
     *     @Apidoc\Returned ("address",type="datetime",desc="添加时间"),
     *     )
     * @Apidoc\Returned ("rjproduct",type="object",desc="产品",
     *      @Apidoc\Returned ("file_path",type="int",desc="产品图片"),
     *     @Apidoc\Returned ("get_city",type="varchar(11)",desc="产品目的地"),
     *     @Apidoc\Returned ("product_id",type="int",desc="产品id"),
     *     @Apidoc\Returned ("price",type="int",desc="产品价格"),
     *     )
     *  @Apidoc\Returned("http",type="string",desc="域名")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("msg",type="string",desc="任务提示")
     */
    public function index(Request $request)
    {
        $city = $request->POST('city');
        $appid = $request->POST('appid');
        if (empty($appid)){
            return json(['code'=>'201','msg'=>'appid不能为空']);
        }
        $puser = Puser::where('appid',$appid)->find();
        if(empty($puser)){
            return json(['code'=>'201','msg'=>'小程序暂时无法使用']);
        }
        //获取轮播图
        $carousel_img = Pcarousel::where(['type'=>'1','appid'=>$appid])->field('img,page,page_type')->select();
        foreach ($carousel_img as $key => $value){
            $carousel_img[$key]['img'] = http().File::where('id',$value['img'])->value('file_path');
        }

        $navigation = Puserhomenavigation::where(['user_id'=>$puser['id'],'type'=>'1'])->order('id','Desc')->field('title,img,page_id')->select();
        foreach ($navigation as $key => $value){
            $navigation[$key]['img'] = http().File::where('id',$value['img'])->value('file_path');
            $navigation[$key]['page_id'] = Puserpage::where('id',$value['page_id'])->value('page');
        }
        //热门景区
        $time = strtotime(date('Y-m-d H:i:s', strtotime('+30minute')));
        $rjproduct = Productuser::alias('pu')->where('jp.end_time','>',$time)->where('jp.delete_time',null)->where(['pu.status'=>'0','pu.is_hot'=>'1','jp.type'=>'1','jp.status'=>'0','pu.user_id'=>$puser['id']])->field('file.file_path,pu.class_name,pu.price,pu.product_id,jp.address,pu.name,pu.id,jp.type')
            ->join('j_product jp','jp.id=pu.product_id')->where([['address','like','%'.$city.'%']])->leftjoin('file file','pu.first_id=file.id')
            ->limit(5)->select()->toarray();
        //热门路线
        $rlproduct = Productuser::alias('pu')->where('jp.end_time','>',$time)->where('jp.delete_time',null)->where(['pu.status'=>'0','pu.is_hot'=>'1','jp.type'=>'2','jp.status'=>'0','pu.user_id'=>$puser['id']])->field('file.file_path,pu.price,pu.product_id,jp.get_city,pu.name,pu.id,pu.class_name,jp.type')
            ->join('j_product jp','jp.id=pu.product_id')->where([['get_city','like','%'.$city.'%']])->leftjoin('file file','pu.first_id=file.id')
            ->limit(5)->select()->toarray();
        $magic = Pusermagic::where('user_id',$puser['id'])->field('page,img,can')->select();
        foreach ($magic as $key=>$value) {
            $magic[$key]['img'] = http().File::where('id',$value['img'])->value('file_path');
            $magic[$key]['page'] = Puserpage::where('id',$value['page'])->value('page');
        }
        return json(['code'=>'200','msg'=>'操作成功','magic'=>$magic,'navigation'=>$navigation,'img'=>$carousel_img,'rjproduct'=>$rjproduct,'rlproduct'=>$rlproduct,'http'=>http(),'notice'=>$puser['notice']]);
    }

    /**
     * @Apidoc\Title("搜索")
     * @Apidoc\Desc("搜索")
     * @Apidoc\Url("user/index/search")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("city", type="number",require=false, desc="城市名称")
     * @Apidoc\Param("product_name", type="number",require=false, desc="产品名称")
     * @Apidoc\Param("type", type="number",require=false, desc="线路还是景区")
     *
     * @Apidoc\Returned ("rjproduct",type="object",desc="产品",
     *     @Apidoc\Returned ("file_path",type="int",desc="产品图片"),
     *     @Apidoc\Returned ("class_name",type="varchar(11)",desc="产品名称"),
     *     @Apidoc\Returned ("product_id",type="int",desc="产品id"),
     *     @Apidoc\Returned ("price",type="int",desc="产品价格"),
     *     @Apidoc\Returned ("number",type="int",desc="产品库存"),
     *     @Apidoc\Returned ("address",type="datetime",desc="添加时间"),
     *     )
     * @Apidoc\Returned ("rjproduct",type="object",desc="产品",
     *      @Apidoc\Returned ("file_path",type="int",desc="产品图片"),
     *     @Apidoc\Returned ("get_city",type="varchar(11)",desc="产品目的地"),
     *     @Apidoc\Returned ("product_id",type="int",desc="产品id"),
     *     @Apidoc\Returned ("price",type="int",desc="产品价格"),
     *     )
     *  @Apidoc\Returned("http",type="string",desc="域名")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("msg",type="string",desc="任务提示")
     */
    public function search(Request $request){
        $get_city = $request->get('get_city');
        $product_name = $request->get('product_name');
        $id = Puser::where('appid',getDecodeToken()['appid'])->value('id');
        $type = $request->get('type');
        $class_id = $request->get('class_id');
        if($type=='1'){
            //景區
            $product = Productuser::alias('pu')->where('jp.delete_time',null)->where(['pu.status'=>'0','jp.type'=>'1','jp.status'=>'0','pu.user_id'=>$id])
                ->join('j_product jp','jp.id=pu.product_id')
                ->leftjoin('file file','pu.first_id=file.id')
                ->field('file.file_path,pu.class_name,pu.price,pu.product_id,jp.get_city,pu.name,pu.id,jp.type')
                ->where([['jp.get_city','like','%'.$get_city.'%']])
                ->where(['jp.mp_id'=>$class_id])
                ->where([['pu.name','like','%'.$product_name.'%']])
                ->select()->toarray();
        }else if($type=='2'){
            //綫路
            $jt_qname = XproductClass::where('id',$class_id)->value('name');
            $product = Productuser::alias('pu')->where('jp.delete_time',null)->where(['pu.status'=>'0','jp.type'=>'2','jp.status'=>'0','pu.user_id'=>$id])
                ->join('j_product jp','jp.id=pu.product_id')
                ->leftjoin('file file','pu.first_id=file.id')
                ->field('file.file_path,pu.class_name,pu.price,pu.product_id,jp.address,pu.name,pu.id,jp.type')
                ->where([['jp.get_city','like','%'.$get_city.'%']])
                ->where([['pu.name','like','%'.$product_name.'%']])
                ->where(['jp.jt_qname'=>$jt_qname])
                ->select()->toarray();
        }else{
            return json(['code'=>'201','msg'=>'type不能为空']);
        }
        return json(['code'=>'200','msg'=>'操作成功','product'=>$product,'http'=>http()]);
    }

    public function userinfo(Request $request){
        $id = getDecodeToken()['puser_id'];
        $user = Puseruser::where(['appid'=>getDecodeToken()['appid'],'id'=>$id])->find();
        $collection = Pusercollection::where('user_id',$id)->count();
        $product = Pusermy::where(['user_id'=>$user['puser_id'],'type'=>'1'])->select();
        foreach($product as $key=>$value){
            $product[$key]['page'] =Puserpage::where('id',$value['page'])->value('page');
            $product[$key]['img'] = http(). File::where('id',$value['img'])->value('file_path');
        }
        $distcenter= Puseruser::where('id',$id)->value('is_distcenter');
        $order = \app\common\model\Order::where(['user_id'=>$id,'order_status'=>'2'])->count();
        return json(['code'=>'200','msg'=>'操作成功','user'=>$user,'collection'=>$collection,'product'=>$product,'ordercount'=>$order,'distcenter'=>$distcenter]);
    }

    public function distcenter(Request  $request){
        $id = getDecodeToken()['puser_id'];
        $name = $request->post('name');
        $phone = $request->post('phone');
        $region = $request->post('region');
        $textarea = $request->post('textarea');
        $remarks = $request->post('remarks');
        Db::startTrans();
        try {
            $puseruser= Puseruser::where('id',$id)->find();
            $puseruser->name = $name;
            $puseruser->phone = $phone;
            $puseruser->region = $region;
            $puseruser->textarea = $textarea;
            $puseruser->remarks = $remarks;
            $request->is_distcenter = '3';
            $puseruser->save();
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'操作失败']);
        }
    }

    public function isdistcenter(Request $request){
        $id = getDecodeToken()['puser_id'];
        $puseruser= Puseruser::where('id',$id)->value('is_distcenter');
        $refuse= Puseruser::where('id',$id)->value('refuse');
        return json(['code'=>'200','msg'=>'操作成功','is_distcenter'=>$puseruser,'refuse'=>$refuse]);
    }
}
