<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\model\Config as admin_config;
use app\common\model\Padmin;
use app\common\service\Sign;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\facade\Validate;
use think\Request;

class Config
{
    /**
     * @author liujiong
     * @Note  支付参数绑定
     */
    public function wxAccount(){
        $data['api_url'] = getVariable('api_url');
        $data['appid'] = getVariable('appid');
        $data['sercet'] = getVariable('sercet');
        $data['mch_id'] = getVariable('mch_id');
        $data['key'] = getVariable('key');
        $data['apiclient_cert'] = getVariable('apiclient_cert');
        $data['apiclient_key'] = getVariable('apiclient_key');

        return returnData(['data'=>$data,'code'=>'200']);
    }

    /**
     * @author liujiong
     * @Note  畅联支付参数绑定
     */
    public function changLian(){
        $data['api_url'] = getVariable('api_url');
        $data['pay_id'] = getVariable('pay_id');
        $data['pay_key'] = getVariable('pay_key');

        return returnData(['data'=>$data,'code'=>'200']);
    }

    /**
     * @author liujiong
     * @Note  添加和修改
     */
    public function addEditValue()
    {
        $postData = input('post.');
        foreach ($postData as $k=>$v){
            $count = admin_config::where(['title'=>$k])->count();
            if($count){
                admin_config::where(['title'=>$k])->update(['value'=>$v]);
            }else{
                admin_config::insert(['title'=>$k,'value'=>$v]);
            }
        }
        addAdminLog(getDecodeToken(),'修改公共配置：'.json_encode($postData,320));
        return returnData(['msg'=>'操作成功','code'=>'200']);
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


}