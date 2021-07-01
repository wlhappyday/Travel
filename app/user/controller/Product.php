<?php
declare (strict_types = 1);

namespace app\user\controller;
use app\api\model\Juser;
use app\api\model\Xuser;
use app\common\model\Pproductreview;
use app\common\model\Pcarousel;
use app\common\model\Puser;
use app\platform\model\Adminlogin;
use app\platform\model\J_product;
use app\common\model\File;
use app\platform\model\Product_relation;
use app\platform\model\Admin;
use app\platform\model\Productuser;
use think\facade\Validate;
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
     * @Apidoc\Desc("用户获取平台商绑定的产品")
     * @Apidoc\Url("user/product/list")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("name", type="number",require=true, desc="产品名称")
     * @Apidoc\Param("type", type="number",require=true, desc="产品类型1 或者 2")
     * @Apidoc\Param("end_time", type="number",require=true, desc="结束时间")
     * @Apidoc\Param("start_time", type="number",require=true, desc="开始时间")
     * @Apidoc\Returned ("product",type="object",desc="平台商列表",
     *     @Apidoc\Returned ("total",type="number",desc="分页总数"),
     *     @Apidoc\Returned ("per_page",type="int",desc="首页"),
     *     @Apidoc\Returned ("last_page",type="int",desc="最后一页"),
     *     @Apidoc\Returned ("current_page",type="int",desc="当前页"),
     *     @Apidoc\Returned ("data",type="object",desc="产品",ref="app\platform\model\J_product\scenic_spot"),
     *     @Apidoc\Returned ("price",type="double(10,2)	",desc="价格"),
     *  )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */

    public function list(Request $request){
        $uid =$request->uid;
        $pagenum = $request->get('pagenum');
        $name = $request->get('name');
        $start_time = $request->get('start_time');
        $end_time = $request->get('end_time');
        $type = $request->get('type');
        $Productuser = Productuser::field('product_id')->column('product_id');
        $where[] = ['a.id','NOT IN',$Productuser];
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
            ->where([['a.name', 'like','%'.$name.'%']])->where($where)
            ->join('j_user b','b.id = a.uid and a.type = 1','left')
            ->join('x_user c','c.id = a.uid and a.type = 2','left')
            ->join('file d','d.id=a.first_id')
            ->field('pr.product_id,a.id,a.type,a.name,a.class_name,a.title,pr.price,a.number,a.end_time,a.desc,d.file_path,a.get_city,a.set_city,a.mp_name,pr.price,a.get_city')
            ->join('p_product_relation pr','pr.product_id=a.id')->where('pr.uid',$uid)->order('pr.id','desc');
        if ($start_time){
            $data->whereTime('pr.create_time', '>=', strtotime($start_time));
        }
        if ($end_time){
            $data->whereTime('pr.create_time', '<=', strtotime($end_time));
        }
        $product = $data->order('pr.id','desc')->paginate($pagenum)->toArray();
        return json(['code'=>'200','msg'=>'操作成功','product'=>$product]);
    }

    /**
     * @Apidoc\Title("平台商产品应用详情")
     * @Apidoc\Desc("平台商产品应用详情")
     * @Apidoc\Url("user/product/listdetails")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Param("product_id", type="number",require=true, desc="产品id" )
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned("scenic_spot",type="object",desc="景区",ref="app\platform\model\j_product\scenic_spot")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function list_details(Request $request){
        $uid = $request->uid;
        $id = $request->id;
        $product_id = $request->get('product_id');//产品id
        $Productrelation = Product_relation::where(['uid'=>$uid,'product_id'=>$product_id])->with(['Product'=>function($query){
            $query->where('status','0');
        }])->find()->toArray();
        foreach ($Productrelation['Product']['img_id'] as $key=>$val){
            $Productrelation['Product']['img_id']->$key = http().File::where('id',$val)->value('file_path');
        }
        return json(['code'=>'200','msg'=>'操作成功','product'=>$Productrelation]);
    }
    /**
     * @Apidoc\Title("产品存入用户产品表")
     * @Apidoc\Desc("产品应用添加到用户表")
     * @Apidoc\Url("user/product/productuseradd")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("product_id", type="number",require=true, desc="产品id")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */

    public function product_user_add(Request $request){
        $id = $request->id;
        $product_id = $request->post('product_id');//产品id
        Db::startTrans();

        try {
            $data = J_product::alias('JP')->where(['JP.status'=>'0','JP.id'=>$product_id])
                ->join('p_product_relation pr','pr.product_id=JP.id')
                ->field('JP.name,JP.desc,JP.title,JP.img_id,JP.type,pr.price,JP.class_name,JP.mp_id,pr.uid')
                ->find()->toarray();
            if($data){
                if($data['mp_id']=='6'&&$data['type']=='1'){
                    $JproductReview = Pproductreview::where(['pid'=>$data['uid'],'product_id'=>$product_id,'uid'=>$id])->whereIn('state',[1,2])->find();
                    if ($JproductReview){
                        return json(['code'=>'201','msg'=>'绑定该产品需要审核，请耐心等待']);
                    }
                    $pro = PproductReviewAdd(getDecodeToken(),$product_id,$data['uid']);
                    if ($pro['code']!='200'){
                        return json(['code'=>'201','msg'=>$pro['msg']]);
                    }
                }else{
                    $data['user_id'] = $id;
                    $data['product_id'] = $product_id;
                    $data['money'] = ceil($data['price'] * 0.03+$data['price']);
                    $data['price'] =  ceil($data['money']);
                    $data['pid'] =  $data['uid'];
                    $Productuser = Productuser::create($data);
                }
                addPuserLog(getDecodeToken(),'添加产品');
                Db::commit();
                return json(['code'=>'200','msg'=>'操作成功']);
            }else{
                return json(['code'=>'201','msg'=>'该产品不存在或者被禁用']);
            }
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'网络繁忙']);
        }

    }


    /**
     * @Apidoc\Title("用户产品表")
     * @Apidoc\Desc("用户产品表")
     * @Apidoc\Url("user/product/productrelatiolist")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("name", type="number",require=true, desc="产品名称")
     * @Apidoc\Param("type", type="number",require=true, desc="产品类型1 或者 2")
     * @Apidoc\Param("end_time", type="number",require=true, desc="结束时间")
     * @Apidoc\Param("start_time", type="number",require=true, desc="开始时间")
     * @Apidoc\Returned ("product",type="object",desc="平台商列表",
     *     @Apidoc\Returned ("total",type="number",desc="分页总数"),
     *     @Apidoc\Returned ("total",type="number",desc="分页总数"),
     *     @Apidoc\Returned ("per_page",type="int",desc="首页"),
     *     @Apidoc\Returned ("last_page",type="int",desc="最后一页"),
     *     @Apidoc\Returned ("current_page",type="int",desc="当前页"),
     *     @Apidoc\Returned ("data",type="object",desc="产品",ref="app\platform\model\J_product\scenic_spot"),
     *     @Apidoc\Returned ("price",type="double(10,2)	",desc="价格"),
     *  )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function product_relatio_list(Request $request){
        $uid =$request->uid;
        $id =$request->id;
        $name = $request->get('name');
        $start_time = $request->get('start_time');
        $end_time = $request->get('end_time');
        $type = $request->get('type');
        $pagenum = $request->get('pagenum');
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
            ->where([['a.name', 'like','%'.$name.'%']])
            ->join('j_user b','b.id = a.uid and a.type = 1','left')
            ->join('x_user c','c.id = a.uid and a.type = 2','left')
            ->join('p_productuser pp','pp.product_id=a.id')->where(['pp.user_id'=>$id])->order('pp.id','desc')
            ->join('file d','d.id=pp.first_id')
            ->field('pp.product_id,a.type,pp.name,pp.title,pp.price,pp.status,a.end_time,pp.desc,d.file_path,pp.is_poster,a.mp_name,a.set_city,a.get_city,a.number');
        if ($start_time){
            $data->whereTime('pp.create_time', '>=', strtotime($start_time));
        }
        if ($end_time){
            $data->whereTime('pp.create_time', '<=', strtotime($end_time));
        }
        $product = $data->paginate($pagenum)->toArray();
        return json(['code'=>'200','msg'=>'操作成功','product'=>$product]);
    }

    /**
     * @Apidoc\Title("用户产品修改产品")
     * @Apidoc\Desc("用户产品修改产品")
     * @Apidoc\Url("user/product/productrelatioedit")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("product_id", type="number",require=true, desc="产品id")
     * @Apidoc\Param("price", type="number",require=true, desc="产品价格")
     * @Apidoc\Param("title", type="number",require=true, desc="产品标题")
     * @Apidoc\Param("name", type="number",require=true, desc="产品名称")
     * @Apidoc\Param("desc", type="number",require=true, desc="产品详情")
     * @Apidoc\Param("img_id", type="array",require=true, desc="产品图片")
     * @Apidoc\Param("first_id", type="array",require=true, desc="产品首图")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */

    public function product_relatio_edit(Request $request){
        $uid =$request->uid;
        $id =$request->id;
        $product_id = $request->post('product_id');
        $rule = [
            'name'=>'require|length:5,50',
            'desc'=>'require|min:50',
            'price'=>'require',
            'img_id'=>'require',
            // 'first_id'=>'require',
            'video_id'=>'require',
        ];
        $msg = [
            'name.require'=>'产品名称不能为空',
            'name.length'=>'产品名称必须5-50个字符',
            'desc.require'=>'产品简介不能为空',
            'desc.min'=>'产品简介最小字符为50',
            'price.require'=>'价格必填',
            'img_id.require'=>'图片不能为空',
            // 'first_id.require'=>'首图不能为空',
            'video_id.require'=>'视频不能为空'

        ];
        if (!is_numeric($request->post('price'))) {
            return json(['code'=>'201','msg'=>'价格格式错误必须为数字']);
        }
        $validate = Validate::rule($rule)->message($msg);
        if (!$validate->check($request->post())) {
            return json(['code'=>'201','msg'=>$validate->getError()]);
        }
        Db::startTrans();
        try {

            $mp_name = J_product::where('id',$product_id)->value('mp_name');
            $productuser = Productuser::where(['user_id'=>$id,'product_id'=>$product_id])->find();
            $data = $request->post();
            $relationc = Product_relation::where(['uid'=>$uid,'product_id'=>$product_id])->field('state,price')->find();
            if($data['price']!=$relationc['peice']){
                if($relationc['state']=='0'){
                    return json(['code'=>'201','msg'=>'当前产品无法改价']);
                }
            }

            if($productuser){
                $productuser->price = $data['price'];
                $productuser->title = $data['title'];
                if ($productuser['type']='1'){
                    $productuser->class_name = $data['name'].'-'.$mp_name;
                }
                $productuser->name = $data['name'];
                $productuser->first_id = $data['first_id'];
                $productuser->desc = $data['desc'];
                $productuser->img_id = $data['img_id'];
                $productuser->video_id = $data['video_id'];
                $productuser->img = $data['img'];
                $productuser->save();
                addPuserLog(getDecodeToken(),'修改产品'.$product_id);
                Db::commit();
                return json(['code'=>'200','msg'=>'操作成功']);
            }else{
                return json(['code'=>'201','msg'=>'该数据不存在']);
            }
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'网络繁忙']);
        }

    }

    /**
     * @Apidoc\Title("用户产品上下架")
     * @Apidoc\Desc("用户产品上下架")
     * @Apidoc\Url("user/product/productstatus")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("product_id", type="number",require=true, desc="产品id")
     * @Apidoc\Param("status", type="number",require=true, desc="产品状态")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function product_status(Request $request){
        $product_id = $request->post('product_id');
        $id = $request->id;
        $status = $request->post('status');
        $productuser = Productuser::where(['product_id'=>$product_id,'user_id'=>$id])->find();
        if ($productuser){
            Db::startTrans();
            try {
                $productuser->status = $status;
                $productuser->save();
                if ($status=='9'){
                    addPuserLog(getDecodeToken(),'产品下架'.$product_id);
                }else{
                    addPuserLog(getDecodeToken(),'产品上架'.$product_id);
                }
                Db::commit();
                return json(['code'=>'200','msg'=>'操作成功']);
            }catch (\Exception $e){
                Db::rollback();
                return json(['code'=>'201','msg'=>'该数据不存在']);
            }
        }else{
            return json(['code'=>'201','msg'=>'操作成功','sign'=>'']);
        }
    }

    /**
     * @Apidoc\Title("用户产品热门")
     * @Apidoc\Desc("用户产品热门")
     * @Apidoc\Url("user/product/hot")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("product_id", type="number",require=true, desc="产品id")
     * @Apidoc\Param("hot", type="number",require=true, desc="0不是 1是")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function hot(Request $request){
        $product_id = $request->post('product_id');
        $hot = $request->post('hot');
        $id = $request->id;
        Db::startTrans();
        try {
            $Productuser = Productuser::where(['product_id'=>$product_id,'user_id'=>$id])->find();
            $Productuser->is_hot = $hot;
            $Productuser->save();
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'网络繁忙']);
        }
    }

    /**
     * @Apidoc\Title("产品应用用户端解除绑定")
     * @Apidoc\Desc("平台商关联产品，推送给用户")
     * @Apidoc\Url("user/product/disassociate")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("关联产品")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("product_id", type="number",require=true, desc="产品id" )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function disassociate(Request $request){
        $uid =$request->uid;//平台商用户id
        $id = $request->id;
        $product_id = $request->post('product_id');//产品id
        Db::startTrans();
        try {
            $j_product = Productuser::where(['product_id'=>$product_id,'user_id'=>$id])->delete();
            if ($j_product){
                addPuserLog(getDecodeToken(),'用户端解除绑定的产品'.$product_id);
                Db::commit();
                return json(['code'=>'200','msg'=>'操作成功']);
            }
            return json(['code'=>'201','msg'=>'没有这条数据们无法解除']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'网络繁忙']);
        }
    }

    /**
     * @Apidoc\Title("平台商产品应用详情")
     * @Apidoc\Desc("平台商产品应用详情")
     * @Apidoc\Url("user/product/platformdetails")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Param("product_id", type="number",require=true, desc="产品id" )
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned("scenic_spot",type="object",desc="景区",ref="app\platform\model\j_product\scenic_spot")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function platform_details(Request $request){
        $uid =$request->uid;
        $id = $request->id;
        $product_id = $request->get('product_id');//产品id

        if ($product_id){
            $J_product = Productuser::where(['user_id'=>$id,'product_id'=>$product_id])->with(['Product'=>function($query){
                $query->where('status','0');
            }])->find()->toArray();
            $J_product['posterimg'] = $J_product['img'];
            $J_product['posterimgs'] = $J_product['img'];
            if (!empty($J_product)){
                foreach ($J_product['img_id'] as $key=>$val){
                    $J_product['avatar'][$key]= http().File::where('id',$val)->value('file_path');
                    $J_product['img'][$key] = $val;
                }
                $J_product['posterimgs'][$key]= http().File::where('id',$J_product['posterimg'])->value('file_path');
            }
            return json(['code'=>'200','msg'=>'操作成功','scenic_spot'=>$J_product]);
        }else{
            return json(['code'=>'201','msg'=>'参数错误']);
        }

    }


    /**
     * @Apidoc\Title("海报列表")
     * @Apidoc\Desc("海报")
     * @Apidoc\Url("user/product/poster")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("name", type="number",require=true, desc="产品名称")
     * @Apidoc\Param("type", type="number",require=true, desc="产品类型1 或者 2")
     * @Apidoc\Param("end_time", type="number",require=true, desc="结束时间")
     * @Apidoc\Param("start_time", type="number",require=true, desc="开始时间")
     * @Apidoc\Returned ("product",type="object",desc="平台商列表",
     *     @Apidoc\Returned ("total",type="number",desc="分页总数"),
     *     @Apidoc\Returned ("per_page",type="int",desc="首页"),
     *     @Apidoc\Returned ("last_page",type="int",desc="最后一页"),
     *     @Apidoc\Returned ("current_page",type="int",desc="当前页"),
     *     @Apidoc\Returned ("data",type="object",desc="产品",ref="app\platform\model\J_product\scenic_spot"),
     *     @Apidoc\Returned ("price",type="double(10,2)	",desc="价格"),
     *  )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function poster(Request $request){
        $uid =$request->uid;
        $id =$request->id;
        $name = $request->get('name');
        $start_time = $request->get('start_time');
        $end_time = $request->get('end_time');
        $type = $request->get('type');
        $pagenum = $request->get('pagenum');
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
            ->where([['a.name', 'like','%'.$name.'%']])
            ->join('j_user b','b.id = a.uid and a.type = 1','left')
            ->join('x_user c','c.id = a.uid and a.type = 2','left')
            ->join('p_productuser pp','pp.product_id=a.id')->where(['pp.user_id'=>$id])->order('pp.id','desc')
            ->join('file d','d.id=pp.first_id')
            ->field('pp.id,a.type,pp.name,pp.title,pp.price,pp.status,a.end_time,pp.desc,d.file_path,pp.is_poster,pp.img');
        if ($start_time){
            $data->whereTime('pp.create_time', '>=', strtotime($start_time));
        }
        if ($end_time){
            $data->whereTime('pp.create_time', '<=', strtotime($end_time));
        }
        $product = $data->paginate($pagenum)->toArray();
        return json(['code'=>'200','msg'=>'操作成功','product'=>$product]);
    }
    /**
     * @Apidoc\Title("海报开启关闭")
     * @Apidoc\Desc("海报开启关闭")
     * @Apidoc\Url("user/product/posterstatus")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("海报")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("product_id", type="number",require=true, desc="产品id" )
     * @Apidoc\Param("is_poster", type="number",require=true, desc="状态值 1开启 0关闭" )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function posterstatus(Request $request){
        $product_id = $request->post('product_id');
        $is_poster = $request->post('is_poster');
        if ($product_id){
            Db::startTrans();
            try {
                $j_product = Productuser::where(['product_id'=>$product_id])->find();
                if ($is_poster=='1'){
                    $j_product->is_poster = $is_poster;
                    $j_product->img = $j_product['first_id'];
                    $j_product->save();
                    addPuserLog(getDecodeToken(),'用户端开启海报'.$product_id);
                }else{
                    addPuserLog(getDecodeToken(),'用户端关闭海报'.$product_id);
                }
                Db::commit();
                return json(['code'=>'200','msg'=>'操作成功']);
            }catch (\Exception $e){
                Db::rollback();
                return json(['code'=>'201','msg'=>'网络繁忙']);
            }
        }
        return json(['code'=>'201','msg'=>'参数错误']);
    }
    /**
     * @Apidoc\Title("海报详情")
     * @Apidoc\Desc("海报详情")
     * @Apidoc\Url("user/product/posterdetail")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("海报")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("id", type="number",require=true, desc="产品id" )
     * @Apidoc\Param("img_id", type="number",require=true, desc="图片的id" )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function poster_detail(Request $request){
        $id = $request->post('id');
        $product = Prpductuser::where('id',$id)->field('img')->find();
        $product['img'] = http().File::where('id',$product)->value('file_path');
        return json(['code'=>'200','msg'=>'操作成功','product'=>$product]);
    }

    /**
     * @Apidoc\Title("海报修改图片")
     * @Apidoc\Desc("海报修改图片")
     * @Apidoc\Url("user/product/posterimg")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("海报")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("product_id", type="number",require=true, desc="产品id" )
     * @Apidoc\Param("img_id", type="number",require=true, desc="图片的id" )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function posterimg(Request $request){
        $product_id = $request->post('product_id');
        $img = $request->post('img_id');
        if ($product_id){
            Db::startTrans();
            try {
                $j_product = Productuser::where(['product_id'=>$product_id])->update([
                    'img'=>$img
                ]);
                addPuserLog(getDecodeToken(),'海报图片上传/修改'.$product_id);
                Db::commit();
                return json(['code'=>'200','msg'=>'操作成功']);
            }catch (\Exception $e){
                Db::rollback();
                return json(['code'=>'201','msg'=>'网络繁忙']);
            }
        }
        return json(['code'=>'201','msg'=>'参数错误']);
    }

    /**
     * @Apidoc\Title("门店绑定特惠票的审核列表")
     * @Apidoc\Desc("门店绑定特惠票的审核列表")
     * @Apidoc\Url("user/product/previewlist")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("state", type="number",require=true, desc="状态 1审核中 2审核成功 3审核拒绝" )
     * @Apidoc\Param("pagenum", type="number",require=true, desc="分页" )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function preview_list(Request $request){
        $id = getDecodeToken()['id'];
        $pagenum = $request->get('pagenum');
        $state = $request->get('state');
        $product_result = new PproductReview();
        $data = $product_result->alias('a')->where(['a.uid'=>$id,'b.mp_id'=>'6','b.type'=>'1'])
            ->join('j_product b','b.id=a.product_id','LEFT')
            ->join('p_user c','c.id=a.uid','LEFT');
        if($state){
            $data->where('a.state',$state);
        }
        $review= $data->field('a.id,b.name,b.class_name,c.user_name,c.phone,a.state,a.create_time')->paginate($pagenum)->toArray();
        return json(['code'=>'200','msg'=>'操作成功','review'=>$review]);
    }
}
