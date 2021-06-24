<?php
declare (strict_types = 1);

namespace app\applets\controller;

use app\api\model\Puser;
use app\common\model\File;
use app\common\model\Puseruser;
use app\platform\model\Productuser;
use app\common\model\PuserInfo;
use app\common\model\Puserpassenger;
use think\facade\Db;
use think\facade\Validate;
use think\Request;
use hg\apidoc\annotation as Apidoc;
class Product
{
    /**
     * @Apidoc\Title("产品详情")
     * @Apidoc\Desc("产品详情")
     * @Apidoc\Url("applets/product/detail")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("product_id", type="number",require=false, desc="产品id")
     * @Apidoc\Returned ("product",type="object",desc="产品",
     *     @Apidoc\Returned ("file_path",type="int",desc="产品图片"),
     *     @Apidoc\Returned ("name",type="int",desc="产品名称 线路展示"),
     *     @Apidoc\Returned ("class_name",type="int",desc="产品名称 景区展示"),
     *     @Apidoc\Returned ("price",type="int",desc="产品价格"),
     *     @Apidoc\Returned ("desc",type="int",desc="产品简介 说明"),
     *     @Apidoc\Returned ("video_id",type="int",desc="产品视频"),
     *     @Apidoc\Returned ("title",type="int",desc="产品标题"),
     *     @Apidoc\Returned ("img_id",type="array",desc="产品多图"),
     *     @Apidoc\Returned ("type",type="int",desc="产品状态 1景区 2线路"),
     *     @Apidoc\Returned ("cp_type",type="int",desc="出票信息"),
     *     @Apidoc\Returned ("yp_type",type="int",desc="验票类型"),
     *     @Apidoc\Returned ("product_code",type="int",desc="产品编码"),
     *     @Apidoc\Returned ("set_city",type="int",desc="出发城市"),
     *     @Apidoc\Returned ("get_city",type="int",desc="目的城市"),
     *     @Apidoc\Returned ("standard",type="int",desc="产品标准"),
     *     @Apidoc\Returned ("day",type="int",desc="出行天数"),
     *     @Apidoc\Returned ("material",type="int",desc="行程素材"),
     *     @Apidoc\Returned ("yw_name",type="int",desc="业务分类"),
     *     @Apidoc\Returned ("cx_name",type="int",desc="出行方式"),
     *     @Apidoc\Returned ("jt_qname",type="int",desc="交通方式  去程"),
     *     @Apidoc\Returned ("jt_fname",type="int",desc="交通方式  返程"),
     *     @Apidoc\Returned ("xl_name",type="int",desc="线路产品分类"),
     *     @Apidoc\Returned ("jq_name",type="int",desc="景区产品分类"),
     *     @Apidoc\Returned ("mp_name",type="int",desc="门票类型"),
     *     @Apidoc\Returned ("product_id",type="int",desc="产品id"),
     *     )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("msg",type="string",desc="任务提示")
     */



    public function detail(Request $request){
        $product_id = $request->get('product_id');
        $puser_id = getDecodeToken()['puser_id'];
        $appid = getDecodeToken()['appid'];
        $type = $request->get('type');
        $id = Puseruser::where(['appid'=>$appid,'id'=>$puser_id])->value('puser_id');
        if($type=='1'){
            //景區
            $product = Productuser::alias('pu')->where('jp.delete_time',null)->where(['pu.product_id'=>$product_id,'pu.status'=>'0','jp.type'=>'1','jp.status'=>'0','pu.user_id'=>$id])
                ->join('j_product jp','jp.id=pu.product_id')
                ->leftjoin('file file','pu.first_id=file.id')
                ->field('file.file_path,pu.class_name,pu.price,pu.product_id,pu.img_id,jp.get_city,pu.name,pu.id,jp.end_time,pu.video_id')
                ->find();
        }else if($type=='2'){
            //綫路
            $product = Productuser::alias('pu')->where('jp.delete_time',null)->where(['pu.status'=>'0','jp.type'=>'2','jp.status'=>'0','pu.user_id'=>$id])
                ->join('j_product jp','jp.id=pu.product_id')
                ->leftjoin('file file','pu.first_id=file.id')
                ->field('file.file_path,pu.class_name,pu.price,pu.img_id,pu.product_id,jp.address,pu.name,pu.id,pu.video_id')
                ->find();
        }else{
            return json(['code'=>'201','msg'=>'type不能为空']);
        }
        $product['video_id'] =http(). File::where('id',$product['video_id'])->value('file_path');
        $product['file_path'] = http().$product['file_path'];
        foreach($product['img_id'] as $key => $val){
            $product['img_id']->$key = http().File::where('id',$val)->value('file_path');
        }
        $product['end_time'] = date('Y-m-s h:i:s',$product['end_time']);
        return json(['code'=>'200','msg'=>'操作成功','product'=>$product]);
    }

    /**
     * @Apidoc\Title("获取用户乘客")
     * @Apidoc\Desc("获取用户乘客")
     * @Apidoc\Url("applets/product/passenger")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned ("passenger",type="object",desc="用户乘客",
     *     @Apidoc\Returned ("name",type="int",desc="用户姓名"),
     *     @Apidoc\Returned ("id_card",type="int",desc="用户证件号"),
     *     @Apidoc\Returned ("id",type="int",desc="id"),
     *     )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("msg",type="string",desc="任务提示")
     */
    public function passenger(Request $request){
        $id = getDecodeToken()['puser_id'];
        $appid = getDecodeToken()['appid'];
        $passenger = Puserpassenger::where(['user_id'=>$id])->select();
        return json(['code'=>'200','msg'=>'操作成功','passenger'=>$passenger]);
    }

    public function userinfoadd(Request $request){
        $id = getDecodeToken()['puser_id'];
        $appid = getDecodeToken()['appid'];
        $rule = [
            'name'=>'require|length:2,10',
            'card'=>'require|idCard',
            'phone'=>'require|mobile',
        ];
        $msg = [
            'name.require'=>'姓名不能为空',
            'name.length'=>'姓名必须2-10个字符',
            'card.require'=>'身份证不能为空',
            'card.idCard'=>'身份证格式不正确',
            'phone.require'=>'手机号不能为空',
            'phone.mobile'=>'手机号格式不正确',
        ];
        $validate = Validate::rule($rule)->message($msg);
        if (!$validate->check($request->post())) {
            return json(['code'=>'201','msg'=>'操作成功','sign'=>$validate->getError()]);
        }
        Db::startTrans();
        try {
            Puserpassenger::insert([
                'name'=>$request->post('name'),
                'card'=>$request->post('card'),
                'phone'=>$request->post('phone'),
                'user_id'=>$id,
            ]);
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','sign'=>$e->getMessage(),'msg'=>'操作失败']);
        }
    }
    public function userinfoedit(Request $request){
        $id = getDecodeToken()['puser_id'];
        $appid = getDecodeToken()['appid'];
        $rule = [
            'userinfo_id'=>'require',
            'name'=>'require|length:2,10',
            'card'=>'require|idCard',
            'phone'=>'require|mobile',
        ];
        $msg = [
            'userinfo_id.require'=>'参数错误',
            'name.require'=>'姓名不能为空',
            'name.length'=>'姓名必须2-10个字符',
            'card.require'=>'身份证不能为空',
            'card.idCard'=>'身份证格式不正确',
            'phone.require'=>'手机号不能为空',
            'phone.mobile'=>'手机号格式不正确',
        ];
        $validate = Validate::rule($rule)->message($msg);
        if (!$validate->check($request->post())) {
            return json(['code'=>'201','msg'=>'操作成功','sign'=>$validate->getError()]);
        }
        Db::startTrans();
        try {
            Puserpassenger::where('id',$request->post('userinfo_id'))->update([
                'name'=>$request->post('name'),
                'card'=>$request->post('card'),
                'phone'=>$request->post('phone'),
            ]);
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','sign'=>$e->getMessage(),'msg'=>'操作失败']);
        }
    }
    public function userinfodel(Request $request){
        $check = explode(',',$request->post('check'));
        Db::startTrans();
        try {
            Puserpassenger::destroy($check);
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','sign'=>$e->getMessage(),'msg'=>'操作失败']);
        }
    }

    public function userinfodetail(Request $request){
        $userinfo_id = $request->post('userinfo_id');
        $passenger = Puserpassenger::where('id',$userinfo_id)->find();
        return json(['code'=>'200','msg'=>'操作成功','passenger'=>$passenger]);
    }
}
