<?php
declare (strict_types = 1);

namespace app\applets\controller;

use app\api\model\Puser;
use app\common\model\File;
use app\common\model\Puseruser;
use app\platform\model\Productuser;
use app\common\model\PuserInfo;
use think\facade\Db;
use think\facade\Validate;
use think\Request;
use hg\apidoc\annotation as Apidoc;
class Product
{
    /**
     * @Apidoc\Title("搜索列表及全部列表")
     * @Apidoc\Desc("搜索列表及全部列表")
     * @Apidoc\Url("applets/product/list")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("city", type="number",require=false, desc="搜索的城市名称")
     * @Apidoc\Returned ("product",type="object",desc="产品",
     *     @Apidoc\Returned ("file_path",type="int",desc="产品图片"),
     *     @Apidoc\Returned ("class_name",type="varchar(11)",desc="产品名称"),
     *     @Apidoc\Returned ("product_id",type="int",desc="产品id"),
     *     @Apidoc\Returned ("price",type="int",desc="产品价格"),
     *     @Apidoc\Returned ("number",type="int",desc="产品库存"),
     *     @Apidoc\Returned ("get_city",type="datetime",desc="所在、目的地城市"),
     *     @Apidoc\Returned ("jp_name",type="datetime",desc="景区产品分类"),
     *     )
     *  @Apidoc\Returned("http",type="string",desc="域名")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("msg",type="string",desc="任务提示")
     */
    public function list(Request $request)
    {
        $city = $request->get('city');
        $puser_id = $request->puser_id;
        $appid = $request->appid;
        $id = Puseruser::where(['appid'=>$appid,'id'=>$puser_id])->value('user_id');
        $data = Productuser::alias('pu')->where('jp.delete_time',null)->where(['pu.status'=>'0','pu.is_hot'=>'1','jp.type'=>'1','jp.status'=>'0','pu.user_id'=>$id])->field('file.file_path,pu.class_name,pu.price,pu.product_id,jp.address,jp.jq_name')
            ->join('j_product jp','jp.id=pu.product_id')->where([['get_city','like','%'.$city.'%']])->leftjoin('file file','pu.first_id=file.id')
            ->select()->toarray();
        return json(['code'=>'200','msg'=>'操作成功','product'=>$data,'http'=>http()]);
    }

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
        $puser_id = $request->puser_id;
        $appid = $request->appid;
        $id = Puseruser::where(['appid'=>$appid,'id'=>$puser_id])->value('puser_id');
        $product = Productuser::where(['pu.product_id'=>$product_id,'pu.user_id'=>$id,'pu.status'=>'0'])->alias('pu')
        ->join('j_product jp','jp.id=pu.product_id')->join('file file','file.id=pu.first_id')
        ->field('pu.product_id.pu.name,pu.class_name,pu.price,pu.desc,pu.first_id,pu.video_id,pu.title,pu.img_id')
        ->field('file.file_path,jp.type,jp.cp_type,jp.yp_type,jp.product_code,jp.set_city,jp.get_city,jp.standard,jp.day,jp.material,jp.yw_name,jp.cx_name,jp.jt_qname,jp.jt_fname,jp.xl_name,jp.jq_name,jp.mp_name')->find()->toarray();
        $product['video_id'] =http(). File::where('id',$product['video_id'])->value('file_path');
        $product['file_path'] = http().$product['file_path'];
        foreach($product['img_id'] as $key => $val){
            $product['img_id']->$key = http().File::where('id',$val)->value('file_path');
        }
        return json(['code'=>'200','msg'=>'操作成功','product'=>$product]);
    }

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
     *     @Apidoc\Returned ("title",type="int",desc="产品标题"),
     *     @Apidoc\Returned ("type",type="int",desc="产品状态 1景区 2线路"),
     *     @Apidoc\Returned ("product_id",type="int",desc="产品id"),
     *     )
     * @Apidoc\Returned ("userinfo",type="object",desc="用户乘车人列表",
     *     @Apidoc\Returned ("name",type="int",desc="用户姓名"),
     *     @Apidoc\Returned ("id_card",type="int",desc="用户证件号"),
     *     @Apidoc\Returned ("class_name",type="int",desc="手机号"),
     *     @Apidoc\Returned ("id",type="int",desc="id"),
     *     )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("msg",type="string",desc="任务提示")
     */
    public function orderdetail(Request $request){
        $product_id = $request->get('product_id');
        $puser_id = $request->puser_id;
        $appid = $request->appid;
        $id = Puseruser::where(['appid'=>$appid,'id'=>$puser_id])->value('puser_id');
        $product = Productuser::where(['pu.product_id'=>$product_id,'pu.user_id'=>$id,'pu.status'=>'0','jp.status'=>'0','jp.delete_time'=>null])->alias('pu')
            ->join('j_product jp','jp.id=pu.product_id')
            ->field('pu.name,pu.title,pu.first_id,pu.prict,jp.end_time,pu.class_name,jp.type,')
            ->find()->toarray();
        $userinfo = PuserInfo::where('uid',$id)->limit(2)->select();
        return json(['code'=>'200','msg'=>'操作成功','product'=>$product,'userinfo'=>$userinfo]);
    }


    /**
     * @Apidoc\Title("获取用户乘客")
     * @Apidoc\Desc("获取用户乘客")
     * @Apidoc\Url("applets/product/userInfo")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned ("product",type="object",desc="用户乘客",
     *     @Apidoc\Returned ("name",type="int",desc="用户姓名"),
     *     @Apidoc\Returned ("id_card",type="int",desc="用户证件号"),
     *     @Apidoc\Returned ("phone",type="int",desc="手机号"),
     *     @Apidoc\Returned ("id",type="int",desc="id"),
     *     )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("msg",type="string",desc="任务提示")
     */
    public function userInfo(Request $request){
        $puser_id = $request->puser_id;
        $appid = $request->appid;
        $id = Puseruser::where(['appid'=>$appid,'id'=>$puser_id])->value('puser_id');
        $userinfo = PuserInfo::where('uid',$id)->select();
        return json(['code'=>'200','msg'=>'操作成功','userinfo'=>$userinfo]);
    }

    public function userinfoadd(Request $request){
        $puser_id = $request->puser_id;
        $appid = $request->appid;
        $id = Puseruser::where(['appid'=>$appid,'id'=>$puser_id])->value('puser_id');
        $rule = [
            'name'=>'require|length:2,50',
            'id_card'=>'require|idCard',
            'phone'=>'require|mobile',
        ];
        $msg = [
            'name.require'=>'姓名不能为空',
            'name.length'=>'姓名必须5-50个字符',
            'id_card.require'=>'身份证不能为空',
            'id_card.idCard'=>'身份证格式不正确',
            'phone.require'=>'手机号不能为空',
            'phone.mobile'=>'手机号格式不正确',
        ];
        $validate = Validate::rule($rule)->message($msg);
        if (!$validate->check($request->post())) {
            return json(['code'=>'201','msg'=>'操作成功','sign'=>$validate->getError()]);
        }
        Db::startTrans();
        try {
             PuserInfo::insert([
                 'name'=>$request->post('name'),
                'id_card'=>$request->post('id_card'),
                'phone'=>$request->post('phone'),
                 'uid'=>$id,
            ]);
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','sign'=>$e->getMessage(),'msg'=>'操作失败']);
        }
    }
}
