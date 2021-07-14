<?php
declare (strict_types = 1);

namespace app\platform\controller;

use app\common\model\Paccount as p_account;
use app\common\model\Padmin;
use app\common\service\Sign;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\facade\Validate;
use think\Request;

class Paccount
{
    //添加用户
    public function add(){
        $data['pid'] = getDecodeToken()['id'];
        $data['sub_mch_id'] = input('post.sub_mch_id/s','','strip_tags');
//        $data['mch_id'] = input('post.mch_id/s','','strip_tags');
//        $data['key'] = input('post.key/s','','strip_tags');
        $data['name'] = input('post.name/s','','strip_tags');
//        $data['apiclient_cert'] = input('post.apiclient_cert/s','','strip_tags');
//        $data['apiclient_key'] = input('post.apiclient_key/s','','strip_tags');

        $rule = [
            'sub_mch_id' => 'require',
//            'mch_id' => 'require',
//            'key' => 'require',
//            'apiclient_cert' => 'require',
//            'apiclient_key' => 'require',
            'name' => 'require|unique:p_accounts',
        ];
        $msg = [
            'sub_mch_id.require' => '微信子商户号不存在',
//            'mch_id.require' => '微信商户号不存在',
//            'key.require' => '商户号秘钥不存在',
//            'apiclient_cert.require' => '微信证书公钥不存在',
//            'apiclient_key.require' => '微信证书私钥不存在',
            'name.require' => '收款账号昵称不存在',

            'name.unique' => '收款账号昵称已存在',
        ];

        $validate = Validate::rule($rule)->message($msg);
        if (!$validate->check($data)) {
            return json(['code'=>'201','msg'=>$validate->getError()]);
        }
        $data['create_time'] = time();
        $data['update_time'] = time();

        Db::startTrans();
        try {
            p_account::insert($data);
            addPadminLog(getDecodeToken(),'添加收款账号：'.$data['name'].' 微信商户号: '.$data['mch_id']);
            Db::commit();
            return returnData(['msg'=>'操作成功','code'=>'200']);
        }catch (\Exception $e){
            Db::rollback();
//            p($e->getMessage());
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }
    }


    //修改平台商收款账号
    public function update(){
        $data['id'] = input('post.id/s','','strip_tags');
        $data['sub_mch_id'] = input('post.sub_mch_id/s','','strip_tags');
//        $data['mch_id'] = input('post.mch_id/s','','strip_tags');
//        $data['key'] = input('post.key/s','','strip_tags');
//        $data['name'] = input('post.name/s','','strip_tags');
//        $data['apiclient_cert'] = input('post.apiclient_cert/s','','strip_tags');
//        $data['apiclient_key'] = input('post.apiclient_key/s','','strip_tags');
//        $data['state'] = input('post.state/d','','strip_tags');

        if($data['state'] == '1'){
            if(p_account::where(['pid'=>getDecodeToken()['id'],'state'=>'1'])->whereNotIn('id',$data['id'])->value('id')){
                return returnData(['msg'=>'已存在开启的收款账号！','code'=>'201']);
            }
        }

        $rule = [
            'id' => 'require',
            'sub_mch_id' => 'require',
//            'mch_id' => 'require',
//            'key' => 'require',
//            'apiclient_cert' => 'require',
//            'apiclient_key' => 'require',
            'name' => 'require|unique:p_accounts',
            'state' => 'require|in:1,2',
        ];
        $msg = [
            'id.require' => '用户id不存在',
            'sub_mch_id.require' => '微信子商户号不存在',
//            'mch_id.require' => '微信商户号不存在',
//            'key.require' => '商户号秘钥不存在',
//            'apiclient_cert.require' => '微信证书公钥不存在',
//            'apiclient_key.require' => '微信证书私钥不存在',
            'name.require' => '收款账号昵称不存在',
            'state.require' => '收款账号状态不存在',
            'name.unique' => '收款账号昵称已存在',
            'state.in' => '用户状态必须在 1,2 范围内',
        ];

        $validate = Validate::rule($rule)->message($msg);
        if (!$validate->check($data)) {
            return json(['code'=>'201','msg'=>$validate->getError()]);
        }

        $data['update_time'] = time();

        Db::startTrans();
        try {
            $uid = $data['id'];
            unset($data['id']);
            p_account::where(['id'=>$uid])->update($data);
            addPadminLog(getDecodeToken(),'修改平台商收款账号：'.$data['name']);
            Db::commit();
            return returnData(['msg'=>'操作成功','code'=>'200']);
        }catch (\Exception $e){
            Db::rollback();
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }

    }


    //获取账号信息
    public function list(){
        $num = input('post.num/d','10','strip_tags');

        $mch_id = input('post.sub_mch_id/s','','strip_tags');
        $where = [];
        if ($mch_id){
            $where['sub_mch_id'] = $mch_id;
        }
        $name = input('post.name/s','','strip_tags');
        if ($name){
            $where['name'] = $name;
        }
        $state = input('post.state/d');

        if ($state){
            $where['state'] = $state;
        }

        $result = new p_account();
        $data = $result->where($where)
            ->field('id,name,sub_mch_id,state,create_time')
            ->paginate($num);

        if($data){
            return returnData(['data'=>$data,'code'=>'200']);
        }else{
            return returnData(['msg'=>'该用户不存在或已被紧用','code'=>'201']);
        }

    }


    //禁用收款账号
    public function updState(){
        $uid = input('post.id/d','','strip_tags');
        $data['state'] = input('post.state/d');

        if (empty($uid) || !isset($data['state'])){
            return returnData(['msg'=>'参数错误','code'=>'201']);
        }
        if (!in_array($data['state'],[1,2])){
            return returnData(['msg'=>'账号状态不符合规则','code'=>'201']);
        }
        if($data['state'] == 1){
            $id = getDecodeToken()['id'];
            if(p_account::where(['pid'=>$id,'state'=>'1'])->value('id')){
                return returnData(['msg'=>'已存在开启的收款账号！','code'=>'201']);
            }else{
                $info = '开启收款账号: '.$uid;
            }
        }else{
            $info = '关闭收款账号: '.$uid;
        }
        $data['update_time'] = time();

        Db::startTrans();
        try {
            p_account::where(['id'=>$uid])->save($data);
            addPadminLog(getDecodeToken(),$info);
            Db::commit();
            return returnData(['msg'=>'操作成功','code'=>'200']);
        }catch (\Exception $e){
            Db::rollback();
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }
    }


    //删除收款账号
    public function isDelete(){
        $uid = input('post.id/d','','strip_tags');

        if (empty($uid)){
            return returnData(['msg'=>'参数错误','code'=>'201']);
        }
        $data['delete_time'] = time();

        Db::startTrans();
        try {
            p_account::where(['id'=>$uid])->save($data);
            addPadminLog(getDecodeToken(),'删除收款账号 '.$uid);
            Db::commit();
            return returnData(['msg'=>'操作成功','code'=>'200']);
        }catch (\Exception $e){
            Db::rollback();
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }
    }


    //上传证书
    public function upload(){
        $temp = explode(".", $_FILES["file"]["name"]);

        $id = getDecodeToken()['id'];
        $user = Padmin::where(['id'=>$id,'status'=>'0'])->field('mch_id,mch_key')->find();
        $mch_id = $user['mch_id'];

        $temp1=end($temp);
        $time= $mch_id.'_'.time().rand(10000000, 99999999).'.'. $temp1;
        $name = "ACPtest2/".$time;
        move_uploaded_file($_FILES["file"]["tmp_name"], $name);

        $info = pathinfo($name);
        $tmp_name = $_SERVER['DOCUMENT_ROOT'].'/'.$info['dirname'].'/'.$info['basename'];

        $img = $this->upload_img($name,$tmp_name,$user);
//        p($img);
        if($img['code'] == 1){
            return returnData(['msg'=>'操作成功', 'media_id' =>$name]);
        }else{
            return returnData(['msg'=>$img['msg'],'code'=>'201']);
        }

    }

    //上传官方服务器
    public function upload_img($name,$tmp_name,$user){
        $mch_id = $user['mch_id'];
        $mch_key = $user['mch_key'];
        $apiUrl = empty(getVariable('api_url'))?'https://apibei.payunke.com':getVariable('api_url');

        if(empty($mch_id) || empty($mch_key)){
            return ['code' => 1, 'msg' =>'通讯设置参数错误！'];
        }

        $data['appid'] = $mch_id;
        $data['name'] = $name;
        $sign = (new Sign())->getSign($mch_key,$data);
        $data['sign'] = $sign;

        $url = $apiUrl.'/index/upload_img';
        $ch = curl_init();

        $post_data = array (
            "appid" => $mch_id,
            "name" => $name,
            "sign" => $sign,
            "upload" => new \CURLFile($tmp_name),
        );

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
 	    curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
 	    curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, false );

        $result = curl_exec($ch);
        //  释放cURL句柄
        curl_close($ch);
        $result=json_decode($result,true);

        return $result;
    }


    public function getMchId(){
        $id = getDecodeToken()['id'];

        $data = Padmin::where(['id'=>$id])->field('mch_id,sub_mch_id')->find();

        return returnData(['msg'=>'操作成功', 'data' =>$data]);
    }

    public function updMchId(){
        $id = getDecodeToken()['id'];

        $data['sub_mch_id'] = input('post.sub_mch_id/s','','strip_tags');
        $data['mch_id'] = input('post.mch_id/s','','strip_tags');
        if (empty($data['sub_mch_id']) || empty($data['mch_id'])){
            return returnData(['msg'=>'参数错误','code'=>'201']);
        }

        Db::startTrans();
        try {
            Padmin::where(['id'=>$id])->save($data);
            addPadminLog(getDecodeToken(),'编辑收款账号 '.$id);
            Db::commit();
            return returnData(['msg'=>'操作成功','code'=>'200']);
        }catch (\Exception $e){
            Db::rollback();
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }
    }



}