<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\model\Accounts as admin_account;
use app\common\model\Padmin;
use app\common\service\Sign;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\facade\Validate;
use think\Request;

class Accounts
{
    /**
     * @author liujiong
     * @Note  添加服务商
     */
    public function add(){
        $data['appid'] = input('post.appid/s','','strip_tags');
        $data['sercet'] = input('post.sercet/s','','strip_tags');
        $data['mch_id'] = input('post.mch_id/s','','strip_tags');
        $data['key'] = input('post.key/s','','strip_tags');
        $data['name'] = input('post.name/s','','strip_tags');
        $data['apiclient_cert'] = input('post.apiclient_cert/s','','strip_tags');
        $data['apiclient_key'] = input('post.apiclient_key/s','','strip_tags');

        $rule = [
            'appid' => 'require',
            'sercet' => 'require',
            'mch_id' => 'require',
            'key' => 'require',
            'apiclient_cert' => 'require',
            'apiclient_key' => 'require',
            'name' => 'require|unique:p_accounts',
        ];
        $msg = [
            'appid.require' => '公众号appid不存在',
            'sercet.require' => '公众号sercet不存在',
            'mch_id.require' => '微信商户号不存在',
            'key.require' => '商户号秘钥不存在',
            'apiclient_cert.require' => '微信证书公钥不存在',
            'apiclient_key.require' => '微信证书私钥不存在',
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
            admin_account::insert($data);
            addAdminLog(getDecodeToken(),'添加服务商账号：'.$data['name'].' 微信服务商商户号: '.$data['mch_id']);
            Db::commit();
            return returnData(['msg'=>'操作成功','code'=>'200']);
        }catch (\Exception $e){
            Db::rollback();
//            p($e->getMessage());
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }
    }

    /**
     * @author liujiong
     * @Note  修改平台商收款账号
     */
    public function update(){
        $data['id'] = input('post.id/s','','strip_tags');
        $data['appid'] = input('post.appid/s','','strip_tags');
        $data['sercet'] = input('post.sercet/s','','strip_tags');
        $data['mch_id'] = input('post.mch_id/s','','strip_tags');
        $data['key'] = input('post.key/s','','strip_tags');
        $data['name'] = input('post.name/s','','strip_tags');
        $data['apiclient_cert'] = input('post.apiclient_cert/s','','strip_tags');
        $data['apiclient_key'] = input('post.apiclient_key/s','','strip_tags');

        $rule = [
            'id' => 'require',
            'appid' => 'require',
            'sercet' => 'require',
            'mch_id' => 'require',
            'key' => 'require',
            'apiclient_cert' => 'require',
            'apiclient_key' => 'require',
            'name' => 'require|unique:p_accounts',
        ];
        $msg = [
            'id.require' => 'id不存在',
            'appid.require' => '公众号appid不存在',
            'sercet.require' => '公众号sercet不存在',
            'mch_id.require' => '微信商户号不存在',
            'key.require' => '商户号秘钥不存在',
            'apiclient_cert.require' => '微信证书公钥不存在',
            'apiclient_key.require' => '微信证书私钥不存在',
            'name.require' => '收款账号昵称不存在',

            'name.unique' => '收款账号昵称已存在',
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
            admin_account::where(['id'=>$uid])->update($data);
            addAdminLog(getDecodeToken(),'修改平台商收款账号：'.$data['name']);
            Db::commit();
            return returnData(['msg'=>'操作成功','code'=>'200']);
        }catch (\Exception $e){
            Db::rollback();
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }

    }

    /**
     * @author liujiong
     * @Note  获取账号信息
     */
    public function list(){
        $num = input('post.num/d','10','strip_tags');

        $mch_id = input('post.mch_id/s','','strip_tags');
        $where = [];
        if ($mch_id){
            $where['mch_id'] = $mch_id;
        }
        $name = input('post.name/s','','strip_tags');
        if ($name){
            $where['name'] = $name;
        }
        $appid = input('post.appid/s','','strip_tags');
        if ($appid){
            $where['appid'] = $appid;
        }

        $result = new admin_account();
        $data = $result->where($where)
            ->field('id,name,mch_id,appid,state,create_time')
            ->paginate($num);

        if($data){
            return returnData(['data'=>$data,'code'=>'200']);
        }else{
            return returnData(['msg'=>'该用户不存在或已被紧用','code'=>'201']);
        }

    }

    /**
     * @author liujiong
     * @Note  禁用收款账号
     */
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
            $info = '开启收款账号: '.$uid;

        }else{
            $info = '关闭收款账号: '.$uid;
        }
        $data['update_time'] = time();

        Db::startTrans();
        try {
            admin_account::where(['id'=>$uid])->save($data);
            addAdminLog(getDecodeToken(),$info);
            Db::commit();
            return returnData(['msg'=>'操作成功','code'=>'200']);
        }catch (\Exception $e){
            Db::rollback();
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }
    }

    /**
     * @author liujiong
     * @Note  删除收款账号
     */
    public function isDelete(){
        $uid = input('post.id/d','','strip_tags');

        if (empty($uid)){
            return returnData(['msg'=>'参数错误','code'=>'201']);
        }
        $data['delete_time'] = time();

        Db::startTrans();
        try {
            admin_account::where(['id'=>$uid])->save($data);
            addAdminLog(getDecodeToken(),'删除收款账号 '.$uid);
            Db::commit();
            return returnData(['msg'=>'操作成功','code'=>'200']);
        }catch (\Exception $e){
            Db::rollback();
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }
    }


    /**
     * @author liujiong
     * @Note  上传证书
     */
    public function upload(){
        $temp = explode(".", $_FILES["file"]["name"]);

        $mch_id = getVariable('pay_id');
        if(empty($mch_id)){
            return returnData(['msg'=>'通讯设置参数错误','code'=>'201']);
        }
        $temp1=end($temp);

        if($temp1 != 'pem'){
            return returnData(['msg'=>'文件格式不符','code'=>'201']);
        }
        $time= $mch_id.'_'.time().rand(10000000, 99999999).'.'. $temp1;
        $name = "ACPtest2/".$time;
        move_uploaded_file($_FILES["file"]["tmp_name"], $name);

        $info = pathinfo($name);
        $tmp_name = $_SERVER['DOCUMENT_ROOT'].'/'.$info['dirname'].'/'.$info['basename'];

        $img = $this->upload_img($name,$tmp_name);
//        p($img);
        if($img['code'] == 1){
            return returnData(['msg'=>'操作成功', 'media_id' =>$name]);
        }else{
            return returnData(['msg'=>$img['msg'],'code'=>'201']);
        }

    }

    //上传官方服务器
    public function upload_img($name,$tmp_name){
        $mch_id = getVariable('pay_id');
        $mch_key = getVariable('pay_key');
        $apiUrl = empty(getVariable('api_url'))?'https://apibei.payunke.com':getVariable('api_url');

        if(empty($mch_id) || empty($mch_key)){
            return ['code' => -1, 'msg' =>'通讯设置参数错误！'];
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

    public function getAccount(){
        $uid = input('post.id/d','','strip_tags');

        if (empty($uid)){
            return returnData(['msg'=>'参数错误','code'=>'201']);
        }

        $data = admin_account::where(['id'=>$uid])->field('id,name,appid,sercet,mch_id,key,apiclient_cert,apiclient_key')->find();

        return returnData(['msg'=>'操作成功', 'data' =>$data]);
    }



}