<?php
declare (strict_types = 1);

namespace app\applets\controller;

use app\common\model\File;
use app\platform\model\Productuser;
use app\user\model\Config;
use think\Request;
use app\api\model\Puser;
use hg\apidoc\annotation as Apidoc;
/**
 *
 * @Apidoc\Title("首页")
 * @Apidoc\Group("index")
 */
class Index
{

    /**
     * @Apidoc\Title("获取热门路线和热门景区")
     * @Apidoc\Desc("订单导入的时候所需要的产品列表")
     * @Apidoc\Url("user/index/index")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("img", type="number",require=false, desc="产品图片")
     * @Apidoc\Param("http", type="number",require=false, desc="域名")
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
        $appid = $request->appid;
        $city = $request->get('city');
        $id = Puser::where('appid',$appid)->value('id');
        //获取轮播图
        $carousel_img = Config::where(['mid'=>$id,'title'=>'Carousel_img'])->find()['value'];
        $img = [];
        foreach ($carousel_img as $key => $value){
            $img[$key] = http().File::where('id',$value)->value('file_path');
        }
        //热门景区
        $rjproduct = Productuser::alias('pu')->where('jp.delete_time',null)->where(['pu.status'=>'0','pu.is_hot'=>'1','jp.type'=>'1','jp.status'=>'0','pu.user_id'=>$id])->field('file.file_path,pu.class_name,pu.price,pu.product_id,jp.address,jp.jq_name')
            ->join('j_product jp','jp.id=pu.product_id')->where([['address','like','%'.$city.'%']])->leftjoin('file file','pu.first_id=file.id')
            ->limit(10)->select()->toarray();
        //热门路线
        $rlproduct = Productuser::alias('pu')->where('jp.delete_time',null)->where(['pu.status'=>'0','pu.is_hot'=>'1','jp.type'=>'2','jp.status'=>'0','pu.user_id'=>$id])->field('file.file_path,pu.price,pu.product_id,jp.get_city')
            ->join('j_product jp','jp.id=pu.product_id')->where([['get_city','like','%'.$city.'%']])->leftjoin('file file','pu.first_id=file.id')
            ->limit(10)->select()->toarray();
        return json(['code'=>'200','msg'=>'操作成功','img'=>$img,'rjproduct'=>$rjproduct,'rlproduct'=>$rlproduct,'http'=>http()]);
    }

    /**
     * @Apidoc\Title("获取的热门产品")
     * @Apidoc\Desc("热门产品")
     * @Apidoc\Url("user/index/search")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned ("product",type="object",desc="搜索列表",
     *     @Apidoc\Returned ("get_city",type="int",desc="产品城市"),
     *     @Apidoc\Returned ("product—_id",type="int",desc="产品id"),
     *     )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("msg",type="string",desc="任务提示")
     */
    public function search(Request $request){
        $appid = $request->appid;
        $id = Puser::where('appid',$appid)->value('id');
        $rlproduct = Productuser::alias('pu')->where('jp.delete_time',null)->where(['pu.status'=>'0','pu.is_hot'=>'1','jp.type'=>'2','jp.status'=>'0','pu.user_id'=>$id])->field('pu.product_id,jp.get_city')
            ->join('j_product jp','jp.id=pu.product_id')
            ->limit(10)->select()->toarray();
        return json(['code'=>'200','msg'=>'操作成功','product'=>$rlproduct]);
    }
}
