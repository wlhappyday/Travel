<?php
declare (strict_types = 1);

namespace app\platform\controller;
use app\api\model\Puser;
use app\common\model\File;
use app\common\model\Pproductreview;
use app\platform\model\J_product;
use app\platform\model\Product_relation;
use app\platform\model\Admin;
use app\common\model\PfzAccount;
use app\common\model\Paccount;
use app\platform\model\Adminlogin;
use app\platform\model\Productuser;
use app\common\model\JproductReview;
use app\api\model\Juser;
use app\api\model\Xuser;
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
        $where[] = ['a.id','NOT IN',$Product_relation];
        if($type == 1){
            $id = Juser::where(['a.status'=>'0'])->alias('a')->join('j_product b','b.uid=a.id and b.type=1')->column('b.id');
        }else if($type == 2){
            $id = Xuser::where(['a.status'=>'0'])->alias('a')->join('j_product b','b.uid=a.id and b.type=2')->column('b.id');
        }else {
            $jid = Juser::where(['a.status'=>'0'])->alias('a')->join('j_product b','b.uid=a.id and b.type=1')->column('b.id');
            $xid = Xuser::where(['a.status'=>'0'])->alias('a')->join('j_product b','b.uid=a.id and b.type=2')->column('b.id');
            $id = array_merge($jid,$xid);
        }
        $data = J_product::where($where)->where(['a.status'=>'0'])->alias('a')
            ->whereIn('a.id',$id)
            ->where([['a.name', 'like','%'.$title.'%']])
            ->join('j_user b','b.id = a.uid and a.type = 1','left')
            ->join('x_user c','c.id = a.uid and a.type = 2','left')
            ->join('file d','d.id=a.first_id')
            ->field('a.mp_name,a.set_city,a.get_city,a.id,a.type,a.name,a.class_name,a.title,a.money,a.number,a.end_time,a.desc,d.file_path');
        if ($end_time){
            $data->whereTime('a.create_time', '<=', strtotime($end_time));
        }
        if ($start_time){
            $data->whereTime('a.create_time', '>=', strtotime($start_time));
        }
        $product = $data->order('a.id','Desc')->paginate($pagenum)->toarray();
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
            $J_product['img_id']->$key = http().File::where('id',$val)->value('file_path');
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
     * @Apidoc\Title("平台商产品应用详情")
     * @Apidoc\Desc("平台商产品应用详情")
     * @Apidoc\Url("platform/product/platformdetails")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Param("product_id", type="number",require=true, desc="产品id" )
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned("scenic_spot",type="object",desc="景区",ref="app\platform\model\j_product\scenic_spot")
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
                    return json(['code'=>'201','msg'=>'您已经绑定该产品']);
                }
                if($j_product['mp_id']=='6'){
                    $JproductReview = JproductReview::where(['pid'=>$uid,'product_id'=>$product_id,'uid'=>$j_product['uid']])->whereIn('state',[1,2])->find();
                    if ($JproductReview){
                        return json(['code'=>'201','msg'=>'绑定该产品需要审核，请耐心等待']);
                    }
                    $pro = ProductReviewAdd(getDecodeToken(),$product_id);
                    if ($pro['code']!='200'){

                        return json(['code'=>'201','msg'=>$pro['msg']]);
                    }
                }else{
                    $accoount = Padmin::where(['id'=>$uid])->find();
                    if($accoount['sub_mch_id']&&$accoount['mch_id']){
                        $pfz = PfzAccount::where(['mch_id'=>$accoount['mch_id'],'status'=>'2','pid'=>$uid,'state'=>$j_product['type'],'uid'=>$j_product['uid'],'sub_mch_id'=>$accoount['sub_mch_id']])->find();
                       if ($pfz){
                           $product_relation = new Product_relation();
                           $product_relation->save([
                               'uid'  =>  $uid,
                               'type' =>  $type,
                               'product_id'=>$product_id,
                               'price'=>$j_product['money'],
                               'mp_id'=>$j_product['mp_id']
                           ]);
                       }else{
                           PfzAccount::insert([
                               'mch_id'=> $accoount['mch_id'],'status'=>'1','pid'=>$uid,'state'=>$j_product['type'],'uid'=>$j_product['uid'],'sub_mch_id'=>$accoount['sub_mch_id']
                           ]);
                           return json(['code'=>'201','msg'=>'正在审核中，请稍后']);
                       }

                    }else{
                        return json(['code'=>'201','msg'=>'没有开启的收款账号']);
                    }

                }

                $data['info'] = '平台商绑定产品：'.$product_id;
                $login = new Adminlogin();
                $login->log($data);
                Db::commit();
                return json(['code'=>'200','msg'=>'操作成功']);
            }catch (\Exception $e){
                Db::rollback();
                return json(['code'=>'201','msg'=>'网络异常']);
            }
        }else{
            return json(['code'=>'201','msg'=>'没有该产品']);
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
                $puser = Puser::where('uid',$uid)->field('id')->select();
                foreach ($puser as $value){
                    Productuser::where(['product_id'=>$product_id,'user_id'=>$value['id']])->delete();
                }
                addPadminLog(getDecodeToken(),'解除绑定产品:'.$product_id);
                Db::commit();
                return json(['code'=>'200','msg'=>'操作成功']);
            }
            return json(['code'=>'201','msg'=>'没有改产品信息']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'网络繁忙']);
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
        if($type == 1){
            $id = Juser::where(['a.status'=>'0'])->alias('a')->join('j_product b','b.uid=a.id and b.type=1')->column('b.id');
        }else if($type == 2){
            $id = Xuser::where(['a.status'=>'0'])->alias('a')->join('j_product b','b.uid=a.id and b.type=2')->column('b.id');
        }else {
            $jid = Juser::where(['a.status'=>'0'])->alias('a')->join('j_product b','b.uid=a.id and b.type=1')->column('b.id');
            $xid = Xuser::where(['a.status'=>'0'])->alias('a')->join('j_product b','b.uid=a.id and b.type=2')->column('b.id');
            $id = array_merge($jid,$xid);
        }
        $data = J_product::where(['a.status'=>'0'])->alias('a')
            ->whereIn('a.id',$id)
            ->where([['a.name', 'like','%'.$title.'%']])
            ->join('j_user b','b.id = a.uid and a.type = 1','left')
            ->join('x_user c','c.id = a.uid and a.type = 2','left')
            ->join('file d','d.id=a.first_id')
            ->join('p_product_relation pr','pr.product_id=a.id')
            ->field('pr.id,pr.product_id,a.type,a.name,a.class_name,a.title,pr.price,a.number,a.end_time,a.desc,d.file_path,a.get_city,a.set_city,a.mp_name,pr.state');
        if ($start_time){
            $data->whereTime('a.create_time', '>=', strtotime($start_time));
        }
        if ($end_time){
            $data->whereTime('a.create_time', '<=', strtotime($end_time));
        }
        $j_product = $data->where('pr.uid',$uid)->order('pr.id','desc')->paginate($pagenum)->toarray();
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
            return json(['code'=>'201','msg'=>$validate->getError()]);
        }
        Db::startTrans();
        try {
            $price = $request->post('price');
            $Product_relation = Product_relation::where(['id'=>$id])->find();
            $product = J_product::where(['id'=>$Product_relation['product_id']])->field('money,state')->find();
            if ($product['state']=='1'){
                return json(['code'=>'201','msg'=>'当前产品无法改价']);
            }
            if ($price < $product['money']){
                return json(['code'=>'201','msg'=>'不能低于成本价']);
            }
            if ($product['money']){
                $Productuser = Productuser::where(['pid',getDecodeToken()['id'],'product_id'=>$Product_relation['product_id']])->find();
                if ($Productuser){
                    return json(['code'=>'201','msg'=>'已有门店绑定该产品所有无法更改价格']);
                }
            }

            $Product_relation->price = $price;
            $Product_relation->save();
            addPadminLog(getDecodeToken(),'修改绑定产品'.$id.'价格为'.$request->post('price'));
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'网络繁忙']);
        }
    }

    /**
     * @Apidoc\Title("平台商特惠票审核列表 仅查看")
     * @Apidoc\Desc("平台商特惠票审核列表")
     * @Apidoc\Url("platform/product/reviewlist")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("state", type="number",require=true, desc="状态 1审核中 2审核成功 3审核拒绝" )
     * @Apidoc\Param("pagenum", type="number",require=true, desc="分页" )
     * @Apidoc\Returned("id",type="string",desc="审核列表的id")
     * @Apidoc\Returned("name",type="string",desc="产品的名称")
     * @Apidoc\Returned("class_name",type="string",desc="产品的简介")
     * @Apidoc\Returned("user_name",type="string",desc="申请特惠票的用户账号")
     * @Apidoc\Returned("phone",type="string",desc="申请特惠票的手机号")
     * @Apidoc\Returned("phone",type="string",desc="申请特惠票的状态 1审核中 2审核成功 3审核拒绝")
     * @Apidoc\Returned("create_time",type="string",desc="申请特惠票的时间")
     */
    public function review_list(Request $request){
        $id = getDecodeToken()['id'];
        $pagenum = $request->get('pagenum');
        $state = $request->get('state');
        $product_result = new JproductReview();
        $data = $product_result->alias('a')->where(['a.pid'=>$id,'b.mp_id'=>'6','b.type'=>'1'])
            ->join('j_product b','b.id=a.product_id','LEFT')
            ->join('p_user c','c.id=a.uid','LEFT');
        if($state){
            $data->where('a.state',$state);
        }
        $review= $data->field('a.id,b.name,b.class_name,c.user_name,c.phone,a.state,a.create_time')->paginate($pagenum)->toArray();
        return json(['code'=>'200','msg'=>'操作成功','review'=>$review]);
    }

    /**
     * @Apidoc\Title("平台商绑定特惠票的审核列表   有操作")
     * @Apidoc\Desc("平台商绑定特惠票的审核列表")
     * @Apidoc\Url("platform/product/previewlist")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("state", type="number",require=true, desc="状态 1审核中 2审核成功 3审核拒绝" )
     * @Apidoc\Param("pagenum", type="number",require=true, desc="分页" )
     * @Apidoc\Returned("id",type="string",desc="审核列表的id")
     * @Apidoc\Returned("name",type="string",desc="产品的名称")
     * @Apidoc\Returned("class_name",type="string",desc="产品的简介")
     * @Apidoc\Returned("user_name",type="string",desc="申请特惠票的用户账号")
     * @Apidoc\Returned("phone",type="string",desc="申请特惠票的手机号")
     * @Apidoc\Returned("phone",type="string",desc="申请特惠票的状态 1审核中 2审核成功 3审核拒绝")
     * @Apidoc\Returned("create_time",type="string",desc="申请特惠票的时间")
     */
    public function preview_list(Request $request){
        $id = getDecodeToken()['id'];
        $pagenum = $request->get('pagenum');
        $state = $request->get('state');
        $product_result = new Pproductreview();
        $data = $product_result->alias('a')->where(['a.pid'=>$id,'b.mp_id'=>'6','b.type'=>'1'])
            ->join('j_product b','b.id=a.product_id','LEFT')
            ->join('p_user c','c.id=a.uid','LEFT');
        if($state){
            $data->where('a.state',$state);
        }
        $review= $data->field('a.id,b.name,b.class_name,c.user_name,c.phone,a.state,a.create_time')->paginate($pagenum)->toArray();
        return json(['code'=>'200','msg'=>'操作成功','review'=>$review]);
    }

    /**
     * @Apidoc\Title("平台商特惠票更改审核状态")
     * @Apidoc\Desc("平台商特惠票更改审核状态")
     * @Apidoc\Url("platform/product/reviewstate")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("id", type="number",require=true, desc="平台商特惠票审核id" )
     * @Apidoc\Param("state", type="number",require=true, desc="状态 1审核中 2审核成功 3审核拒绝" )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function review_state(Request $request){
        $id = $request->post('id');
        $state = $request->post('state');
        Db::startTrans();
        try {
            $review = Pproductreview::where('id',$id)->find();
            $review->state = $state;
            $review->save();
            if ($state=='2'){
                $data = J_product::alias('JP')->where(['JP.status'=>'0','JP.id'=>$review['product_id']])
                    ->join('p_product_relation pr','pr.product_id=JP.id')
                    ->field('JP.name,JP.desc,JP.title,JP.img_id,JP.type,pr.price,JP.class_name,JP.mp_id,pr.uid')
                    ->find()->toarray();
                $text = '通过';
                $data['user_id'] = $review['uid'];
                $data['product_id'] = $review['product_id'];
                $data['money'] = ceil($data['price'] * 0.03+$data['price']);
                $data['price'] =  ceil($data['money']);
                $data['pid'] =  $data['uid'];
                $Productuser = Productuser::create($data);
            }else{
                $text='拒绝';
            }
            addPadminLog(getDecodeToken(),'特惠票审核'.$text.$id);
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'网络繁忙']);
        }
    }

    /**
     * @Apidoc\Title("平台商品是否可以改价")
     * @Apidoc\Desc("平台商品是否可以改价")
     * @Apidoc\Url("platform/product/productstate")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("id", type="number",require=true, desc="平台商绑定产品接口id" )
     * @Apidoc\Param("state", type="number",require=true, desc="状态 1可以 0不可以" )
     */
    public function product_state(Request $request){
        $id = $request->post('id');
        $state = $request->post('state');
        Db::startTrans();
        try {

            $relationc = Product_relation::where(['id'=>$id])->find();
            $Productuser = Productuser::where(['pid'=>getDecodeToken()['id'],'product_id'=>$relationc['product_id']])->find();
            if ($Productuser){
                return json(['code'=>'201','msg'=>'已有门店绑定该产品,所以无法开启']);
            }
            $relationc->state = $state;
            $relationc->save();
            if ($state=='1'){
                $text = '可以';
            }else{
                $text = '不可以';
            }
            addPadminLog(getDecodeToken(),'开启产品'.$text.'改价:'.$id);
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'网络繁忙']);
        }
    }
}
