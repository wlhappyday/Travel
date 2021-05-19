<?php
declare (strict_types = 1);

namespace app\platform\controller;
use app\common\model\File;
use app\platform\model\J_product;
use app\platform\model\Product_relation;
use app\platform\model\Admin;
use app\platform\model\Adminlogin;
use app\platform\model\Productuser;
use think\facade\Validate;
use think\Request;
use think\facade\Db;
use hg\apidoc\annotation as Apidoc;
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
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function list(Request $request){
        $uid =$request->uid;
        $Product_relation = Product_relation::field('product_id')->column('product_id');
        $pagenum = $request->get('pagenum');
        $title = $request->get('title');
        $start_time = $request->get('start_time');
        $end_time = $request->get('end_time');
        $type = $request->get('type');
        $where[] = ['id','NOT IN',$Product_relation];
        $J_product = J_product::where(['status'=>'0'])->with(['juser'=>function($query){
            $query->where('status','0');
        }])
            ->where([['title','like','%'.$title.'%']])
            ->where($where);
        if ($start_time){
            $J_product->whereTime('create_time', '>=', strtotime($start_time));
        }
        if($type){
            $J_product->where([['type','=',$type]]);
        }
        if ($end_time){
            $J_product->whereTime('create_time', '<=', strtotime($end_time));
        }
        $product = $J_product->order('id','Desc')->paginate($pagenum)->toarray();

        if($type == 1){
            $id = Juser::where(['a.status'=>'0'])->alias('a')->join('j_product b','b.uid=a.id and b.type=1')->column('b.id');
        }else if($type == 2){
            $id = Xuser::where(['a.status'=>'0'])->alias('a')->join('j_product b','b.uid=a.id and b.type=2')->column('b.id');
        }else {
            $jid = Juser::where(['a.status'=>'0'])->alias('a')->join('j_product b','b.uid=a.id and b.type=1')->column('b.id');
            $xid = Xuser::where(['a.status'=>'0'])->alias('a')->join('j_product b','b.uid=a.id and b.type=2')->column('b.id');
            $id = array_merge($jid,$xid);
        }
        $data = Jproduct::where($where)->alias('a')
            ->whereIn('a.id',$id)
            ->join('j_user b','b.id = a.uid and a.type = 1','left')
            ->join('x_user c','c.id = a.uid and a.type = 2','left')
            ->join('file d','d.id=a.first_id')
            ->field('a.id,a.type,a.name,a.class_name,a.title,a.money,a.number,a.end_time,a.desc,b.user_name j_name,c.user_name x_name,d.file_path')
            ->select();;
        dd($product);
        return json(['code'=>'200','msg'=>'操作成功','scenic_spot'=>$product]);
    }


    /**
     * @Apidoc\Title("产品应用详情")
     * @Apidoc\Desc("用户查看自己绑定/购买以及未绑定/购买的路线和景区")
     * @Apidoc\Url("platform/product/details")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Param("product_id", type="number",require=true, desc="产品id" )
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned("scenic_spot",type="object",desc="景区",ref="app\platform\model\j_product\scenic_spot")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function details(Request $request){
        $uid =$request->uid;
        $product_id = $request->post('product_id');//产品id
        $J_product = J_product::where(['status'=>'0','id'=>$product_id])->find()->toArray();//景区
        foreach ($J_product['img_id'] as $key=>$val){
            $J_product['img_id']->$key = File::where('id',$val)->value('file_path');
        }
        return json(['code'=>'200','msg'=>'操作成功','scenic_spot'=>$J_product]);
    }

    /**
     * @Apidoc\Title("平台商产品应用详情")
     * @Apidoc\Desc("平台商产品应用详情")
     * @Apidoc\Url("platform/product/platformdetails")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Param("product_id", type="number",require=true, desc="产品id" )
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned("scenic_spot",type="object",desc="景区",ref="app\platform\model\j_product\scenic_spot")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function platform_details(Request $request){
        $uid =$request->uid;
        $product_id = $request->post('product_id');//产品id
        $J_product = Product_relation::where(['uid'=>$uid,'product_id'=>$product_id])->with(['Product'=>function($query){
            $query->where('status','0');
        }])->find()->toArray();
        if (!empty($J_product['Product'])){
            foreach ($J_product['Product']['img_id'] as $key=>$val){
                $J_product['Product']['img_id']->$key = File::where('id',$val)->value('file_path');
            }
        }

        return json(['code'=>'200','msg'=>'操作成功','scenic_spot'=>$J_product]);
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
        $type = $request->post('type');//产品类型
        $product_id = $request->post('product_id');//产品id
        $j_product = J_product::where(['type'=>$type,'status'=>'0','id'=>$product_id])->find();
        if($j_product){
            Db::startTrans();
            try {
                if (Product_relation::where(['product_id'=>$product_id,'uid'=>$uid])->find()){
                    return json(['code'=>'201','msg'=>'操作成功','sign'=>'您已经绑定该产品']);
                }
                $product_relation = new Product_relation();
                $product_relation->save([
                    'uid'  =>  $uid,
                    'type' =>  $type,
                    'product_id'=>$product_id,
                    'price'=>$j_product['money'],
                ]);
                $data['info'] = '平台商绑定产品：'.$product_id;
                $login = new Adminlogin();
                $login->log($data);
                Db::commit();
                return json(['code'=>'200','msg'=>'操作成功']);
            }catch (\Exception $e){
                Db::rollback();
                return json(['code'=>'201','msg'=>'操作成功','sign'=>$e->getMessage()]);
            }
        }else{
            return json(['code'=>'-1','msg'=>'操作成功','sign'=>'请检查参数']);
        }
    }
    /**
     * @Apidoc\Title("产品应用平台商解除绑定")
     * @Apidoc\Desc("平台商关联产品，推送给用户")
     * @Apidoc\Url("platform/product/disassociate")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("关联产品")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("product_id", type="number",require=true, desc="产品id" )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */

    public function disassociate(Request $request){
        $uid =$request->uid;//平台商用户id
        $product_id = $request->post('product_id');//产品id
        Db::startTrans();
        try {
            $j_product = Product_relation::where(['product_id'=>$product_id,'uid'=>$uid])->delete();
            if ($j_product){
                $data['info'] = '平台商解除绑定的产品：'.$product_id;
                $login = new Adminlogin();
                $login->log($data);
                addPadminLog(getDecodeToken(),'解除绑定产品:'.$product_id);
                Db::commit();
                return json(['code'=>'200','msg'=>'操作成功']);
            }
            return json(['code'=>'201','sign'=>'没有这条数据们无法解除','msg'=>'操作成功']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','sign'=>$e->getMessage(),'msg'=>'操作成功']);
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
     * @Apidoc\Returned("product",type="object",desc="路线",ref="app\platform\model\J_product\scenic_spot")
     */
    public function relationproducts(Request $request){
        $uid =$request->uid;//平台商用户id
        $title = $request->get('title');
        $pagenum = $request->get('pagenum');
        $start_time = $request->get('start_time');
        $end_time = $request->get('end_time');
        $type = $request->get('type');
        $product = J_product::alias('JP')->where([['JP.title','like','%'.$title.'%']])->where(['JP.status'=>'0'])
            ->join('p_product_relation pr','pr.product_id=JP.id');
        if ($start_time){
            $product->whereTime('pr.create_time', '>=', strtotime($start_time));
        }
        if($type){
            $product->where([['JP.type','=',$type]]);
        }
        if ($end_time){
            $product->whereTime('pr.create_time', '<=', strtotime($end_time));
        }
        $j_product = $product->where('pr.uid',$uid)->order('pr.id','desc')->paginate($pagenum);
        return json(['code'=>'200','msg'=>'操作成功','product'=>$j_product]);
    }
    /**
     * @Apidoc\Title("修改绑定产品价格")
     * @Apidoc\Desc("修改绑定产品价格")
     * @Apidoc\Url("platform/product/edit")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("关联产品")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("id", type="number",require=true, desc="产品id" )
     * @Apidoc\Param("price", type="number",require=true, desc="产品价格" )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function edit(Request $request){
        $uid = $request->uid;
        $id = $request->post('id');
        $rule = [
            'price'=>'require',
        ];
        $msg = [
            'price.require'=>'价格必填',
        ];
        $validate = Validate::rule($rule)->message($msg);
        if (!$validate->check($request->post())) {
            return json(['code'=>'201','msg'=>'操作成功','sign'=>$validate->getError()]);
        }
        Db::startTrans();
        try {
            $Product_relation = Product_relation::where(['id'=>$id])->find();
            $Product_relation->price = $request->post('price');
            $Product_relation->save();
            addPadminLog(getDecodeToken(),'修改绑定产品'.$id.'价格为'.$request->post('price'));
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','sign'=>$e->getMessage(),'msg'=>'操作成功']);
        }
    }
}
