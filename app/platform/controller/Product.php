<?php
declare (strict_types = 1);

namespace app\platform\controller;
use app\platform\model\j_product;
use app\platform\model\product_relation;
use app\platform\model\admin;
use think\Request;
use think\facade\Db;
use hg\apidoc\annotation as Apidoc;
/**
 *
 * @Apidoc\Title("产品接口")
 * @Apidoc\Group("product")
 */
class Product
{
    /**
     * @Apidoc\Title("产品应用列表")
     * @Apidoc\Desc("用户查看自己绑定/购买以及未绑定/购买的路线和景区")
     * @Apidoc\Url("platform/product/list")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned("scenic_spot",type="object",desc="景区",ref="app\platform\model\j_product\scenic_spot")
     * @Apidoc\Returned("route",type="object",desc="路线",ref="app\platform\model\j_product\route")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */

    public function list(Request $request){
        $uid =$request->uid;
        $scenic_spot = j_product::where(['type'=>'1','status'=>'0'])->field('name,jq_name,mp_name,product_code,title,money,number,img_url,video_url')->select();//景区
        $route = j_product::where(['type'=>'2','status'=>'0'])->field('name,yw_name,cx_name,jt_qname,jt_fname,xl_name,product_code,set_city,get_city,day,title,standard,end_day,address,money,number,img_url,video_url')->select();//景区
        return json(['scenic_spot'=>$scenic_spot,'route'=>$route]);
    }


    /**
     * @Apidoc\Title("产品应用平台商绑定接口")
     * @Apidoc\Desc("平台商关联产品，推送给用户")
     * @Apidoc\Url("platform/product/relation")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("关联产品")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("type", type="number",require=true, desc="产品类型" )
     * @Apidoc\Param("product_id", type="number",require=true, desc="产品id" )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function relation(Request $request){
        $uid =$request->uid;//平台商用户id
        $type = $request->get('type');//产品类型
        $product_id = $request->get('product_id');//产品id
        $j_product = j_product::where(['type'=>$type,'status'=>'0','id'=>$product_id])->find();
        if($j_product){
            Db::startTrans();
            try {
                $product_relation = new product_relation();
                $product_relation->save([
                    'uid'  =>  $uid,
                    'type' =>  $type,
                    'product_id'=>$product_id
                ]);

                Db::commit();
                return json(['code'=>'200','msg'=>'操作成功']);
            }catch (\Exception $e){
                Db::rollback();
                return json(['code'=>'-1','msg'=>'操作成功','sign'=>$e->getMessage()]);
            }
        }else{
            return json(['code'=>'-1','msg'=>'操作成功','sign'=>'请检查参数']);
        }
    }

    public function disassociate(Request $request){
        $uid =$request->uid;//平台商用户id
        $product_id = $request->get('product_id');//产品id
        Db::startTrans();
        try {
            $j_product = product_relation::where(['product_id'=>$product_id,'uid'=>$uid])->delete();
            return json(['code'=>'200','msg'=>'操作成功']);
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'-1','sign'=>$e->getMessage(),'msg'=>'操作成功']);
        }

    }

    /**
     * @Apidoc\Title("获取应用平台商绑定产品接口")
     * @Apidoc\Desc("平台商关联产品，推送给用户")
     * @Apidoc\Url("platform/product/relation_products")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("关联产品")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("admin",type="object",desc="平台商信息",ref="app\platform\model\admin\info")
     * @Apidoc\Returned("product",type="object",desc="路线",ref="app\platform\model\j_product\route")
     */
    public function relationproducts(Request $request){
        $uid =$request->uid;//平台商用户id
        $type = $request->type;
        $where['id'] = $uid;
        $where['status'] = 0;
        $map=[];
        if ($type){
            $map['ln_j_product.type']=$type;
        }
        $admin = admin::where($where)->with(['product'=>function($query) use($map){
            $query->where($map);
        }])->field('id,phone,nickname,avatar')->find()->toArray();
        return json(['code'=>'200','msg'=>'操作成功','admin'=>$admin,'product'=>$admin['product']]);
    }

}
