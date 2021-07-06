<?php
declare (strict_types = 1);

namespace app\applets\controller;

use app\api\model\Puser;
use app\common\model\File;
use app\common\model\Jproduct;
use app\common\model\Puseruser;
use app\platform\model\Productuser;
use app\common\model\PuserFootprint;
use app\common\model\JproductClass;
use app\common\model\XproductClass;
use app\common\model\PuserInfo;
use app\common\model\Pusercollection;
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
        $product_id = $request->post('product_id');
        $puseruser_id = $request->post('uid');
        $uid = getDecodeToken()['puser_id'];
        $appid = getDecodeToken()['appid'];
        $type = $request->post('type');
        $id = Puser::where('appid',$appid)->value('id');
        if($type=='1'){
            //景區
            $product = Productuser::alias('pu')->where('jp.delete_time',null)->where(['pu.product_id'=>$product_id,'pu.status'=>'0','jp.type'=>'1','jp.status'=>'0','pu.user_id'=>$id])
                ->join('j_product jp','jp.id=pu.product_id')
                ->leftjoin('file file','pu.first_id=file.id')
                ->field('pu.desc,file.file_path,pu.class_name,pu.price,pu.product_id,pu.img_id,jp.get_city,pu.name,pu.id,jp.end_time,pu.video_id,jp.type')
                ->find();
        }else if($type=='2'){
            //綫路
            $product = Productuser::alias('pu')->where('jp.delete_time',null)->where(['pu.status'=>'0','jp.type'=>'2','jp.status'=>'0','pu.user_id'=>$id])
                ->join('j_product jp','jp.id=pu.product_id')
                ->leftjoin('file file','pu.first_id=file.id')
                ->field('file.file_path,pu.class_name,pu.price,pu.img_id,pu.product_id,jp.address,pu.name,pu.id,jp.end_time,jp.type,pu.video_id,jp.day')
                ->find();
        }else{
            return json(['code'=>'201','msg'=>'type不能为空']);
        }
        $product['video_id'] =http(). File::where('id',$product['video_id'])->value('file_path');
        $product['file_path'] = http().$product['file_path'];
        foreach($product['img_id'] as $key => $val){
            $product['img_id']->$key = http().File::where('id',$val)->value('file_path');
        }
        $product['img_id'] = json_decode(json_encode($product['img_id']),TRUE);
        $product['end_time'] = date('Y-m-s h:i:s',$product['end_time']);
        $product['collection'] = false;
        if ($uid){
            if (Pusercollection::where(['user_id'=>getDecodeToken()['puser_id'],'product_id'=>$product['product_id']])->find()){
                $product['collection'] = true;
            }
        }
        if ($puseruser_id){
            $puserusers = Puseruser::where(['id'=>$puseruser_id,'is_distcenter'=>'1'])->find();
            if ($puserusers){
                $puseruser = Puseruser::where(['id'=>$uid,'pid'=>'0'])->where('is_distcenter','<>','1')->find();
                if ($uid !=$puseruser_id){
                    if (!empty($puseruser)){
                        $puseruser->pid = $puseruser_id;
                        $puseruser->save();
                         Puseruser::where(['id'=>$puseruser_id,'is_distcenter'=>'1'])->inc('offline_count')->update();
                    }
                }

            }

        }

        return json(['code'=>'200','msg'=>'操作成功','product'=>$product,'data'=>getDecodeToken()]);
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
            return json(['code'=>'201','msg'=>'网络异常']);
        }
    }

    public function userinfodetail(Request $request){
        $userinfo_id = $request->post('userinfo_id');
        $passenger = Puserpassenger::where('id',$userinfo_id)->find();
        return json(['code'=>'200','msg'=>'操作成功','passenger'=>$passenger]);
    }

    public function collection(Request $request){
        $id = getDecodeToken()['puser_id'];
        $product_id = $request->post('product_id');
        Db::startTrans();
        try {
            $collection = Pusercollection::where(['user_id'=>$id,'product_id'=>$product_id])->find();
            if (empty($collection)){
                $collection = new Pusercollection();
                $collection->user_id = $id;
                $collection->product_id = $product_id;
                $collection->save();
                $data = true;
            }else{
                $collection->delete();
                $data = false;
            }
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功','collection'=>$data]);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'操作失败']);
        }
    }

    public function poster_list(Request $request){
        $id = getDecodeToken()['puser_id'];
        $puser = Puseruser::where('id',$id)->value('puser_id');
        $product = Productuser::where('user_id',$puser)->field('img,first_id,id,product_id,type')->where('is_poster','1')->select();
        foreach ($product as $key=>$value) {
            if (!empty($value['img'])){
                $product[$key]['img'] = http().File::where('id',$value['img'])->value('file_path');
            }else{
                $product[$key]['img'] = http().File::where('id',$value['first_id'])->value('file_path');
            }
        }
        return json(['code'=>'200','msg'=>'操作成功','product'=>$product]);
    }
    public function poster_detail(Request $request){
        $id = getDecodeToken()['puser_id'];
        $product_id = $request->get('product_id');
        $pr = Productuser::where('id',$id)->field('img,first_id,product_id,type,name')->find();
        $puser_id = Puseruser::where('id',$id)->value('puser_id');
        $data = Puser::where('id',$puser_id)->field('appid,appkey')->find();
        $jp = Jproduct::where(['id'=>$product_id])->find();
        $tokenUrl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$data['appid'].'&secret='.$data['appkey'];
        $token = json_decode(httpGet($tokenUrl));
        $data = [
            'path' => 'pages/productdetail/productdetail?product_id='.$jp['id'].'&type='.$jp['type'].'&uid='.$id, //扫码后进入页面
        ];
        $URL = 'https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token='.$token->access_token;
        $json = json_encode($data); //数组加密
        $result = post($URL,$json);
        if(json_decode($result)['errcode']){
            return json(['code'=>'201','msg'=>'生成失败']);
        }
//以下是将二进制流转成file并写入本地

        $data = date('Ymd');
        $path =  $_SERVER['DOCUMENT_ROOT'].'/storage/topic/'.$data;
        if(!file_exists($path)){ //判断目录是否存在
            mkdir($path,0777,true);
        }
        $filename = md5('Y-m-d H:i:s');
        $path = $path.'/'.$filename.'.png'; //最后要写入的目录及文件名
        //  创建将数据流文件写入我们创建的文件内容中
        file_put_contents($path,$result);
        return json(['code'=>'200','msg'=>'操作成功','product'=>http().'/storage/topic/'.$data.'/'.$filename.'.png']);
    }

    public function details(Request $request){
        $product_id = $request->get('product_id');
        $appid = getDecodeToken()['appid'];
        $type = $request->get('type');
        $uid = $request->get('uid');
        $id = Puser::where('appid',$appid)->value('id');
        if($type=='1'){
            //景區
            $product = Productuser::alias('pu')->where('jp.delete_time',null)->where(['pu.product_id'=>$product_id,'pu.status'=>'0','jp.type'=>'1','jp.status'=>'0','pu.user_id'=>$id])
                ->join('j_product jp','jp.id=pu.product_id')
                ->leftjoin('file file','pu.first_id=file.id')
                ->field('pu.id,pu.img,pu.desc,file.file_path,pu.class_name,pu.price,pu.product_id,pu.img_id,jp.get_city,pu.name,pu.id,jp.end_time,pu.video_id,jp.type')
                ->find();
        }else if($type=='2'){
            //綫路
            $product = Productuser::alias('pu')->where('jp.delete_time',null)->where(['pu.status'=>'0','jp.type'=>'2','jp.status'=>'0','pu.user_id'=>$id])
                ->join('j_product jp','jp.id=pu.product_id')
                ->leftjoin('file file','pu.img=file.id')
                ->field('pu.id,file.file_path,pu.class_name,pu.price,pu.img_id,pu.product_id,jp.address,pu.name,pu.id,jp.end_time,jp.type,pu.video_id,jp.day')
                ->find();
        }else{
            return json(['code'=>'201','msg'=>'type不能为空']);
        }
        $product['file_path'] = http().$product['file_path'];
        $product['end_time'] = date('Y-m-s h:i:s',$product['end_time']);
        return json(['code'=>'200','msg'=>'操作成功','product'=>$product]);
    }

    public function collectionlist(Request $request){
        $id = getDecodeToken()['puser_id'];
        $collection = Pusercollection::where('user_id',$id)->select();
        $pid = Puseruser::where('id',$id)->value('puser_id');
        $product = [];
        foreach ($collection as $key=>$val){
            $product[] = Productuser::where('user_id',$pid)->where('product_id',$val['product_id'])->find();
        }
        foreach ($product as $key=>$val){
            $product[$key]['first_id'] = http().File::where('id',$val['first_id'])->value('file_path');
        }
        return json(['code'=>'200','msg'=>'操作成功','product'=>$product]);
    }

    public function collectiondelete(Request $request){
        $id = getDecodeToken()['puser_id'];
        $product_id = $request->post('product_id');
        Db::startTrans();
        try {
            $collection = Pusercollection::where(['product_id'=>$product_id,'user_id'=>$id])->delete();
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'操作失败']);
        }
    }

    public function footprint(Request  $request){
        $id = getDecodeToken()['puser_id'];
        $footprint= PuserFootprint::where('user_id',$id)->select();
        $pid = Puseruser::where('id',$id)->value('puser_id');
        $product = [];
        foreach ($footprint as $key=>$val){
            $product[] = Productuser::where('user_id',$pid)->where('product_id',$val['product_id'])->find();
        }
        foreach ($product as $key=>$val){
            $product[$key]['first_id'] = http().File::where('id',$val['first_id'])->value('file_path');
        }
        return json(['code'=>'200','msg'=>'操作成功','product'=>$product]);
    }

    public function product_class(Request  $request){
        $id = getDecodeToken()['puser_id'];
        $type = $request->get('type');
        switch ($type){
            case 1:
                $class = JproductClass::where('type','2')->select();
                break;
            case 2:
                $class = XproductClass::where('type','3')->select();
                break;
        }
        foreach ($class as $key=>$val){
            $class[$key]['text'] = $val['name'];
            $class[$key]['value'] = $val['id'];
        }
        return json(['code'=>'200','msg'=>'操作成功','class'=>$class]);

    }
}
