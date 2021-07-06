<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\model\Config as admin_config;
use app\common\model\Sms;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
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

        $data['appid'] = getVariable('appid');
        $data['sercet'] = getVariable('sercet');
        $data['mch_id'] = getVariable('mch_id');
        $data['sub_mch_id'] = getVariable('sub_mch_id');
        $data['key'] = getVariable('key');
        $data['apiclient_cert'] = getVariable('apiclient_cert');
        $data['apiclient_key'] = getVariable('apiclient_key');
        $apiData['body'] = getVariable('body');

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


    public function sms(){
        $config['ali_key'] = getVariable('ali_key');
        $config['ali_secret'] = getVariable('ali_secret');
        $config['ali_money'] = getVariable('ali_money');

        $data = Sms::field('id,TemplateName,TemplateCode,TemplateContent,type,TemplateType,Remark,TemplateStatus,Reason,default')->select();

        return returnData(['data'=>$data,'config'=>$config,'code'=>'200']);
    }

    public function addSmsTemplate()
    {

        $formData = input("post.");
        $array = [
            'TemplateType' => $formData['TemplateType'],
            'TemplateName' => $formData['TemplateName'],
            'TemplateContent' => $formData['TemplateContent'],
            'Remark' => $formData['Remark']
        ];
        $result1 = $this->AlibabaCloud('AddSmsTemplate', $array);
        if ($result1['Message'] == 'OK' && $result1['Code'] == 'OK') {
            $formData["TemplateCode"] = $result1['TemplateCode'];
            $formData["create_time"] = time();
            $result = Sms::create($formData);
        } else {
            $result = '';
        }
        if (empty($result)) {
            return returnData(['msg' => '参数不完整', "code" => 201]);
        } else {
            return returnData(['msg' => '成功', "code" => 200]);
        }

    }

    public function AlibabaCloud($action, $a)
    {
        AlibabaCloud::accessKeyClient("LTAI4G4m3pm6GzdcdWQSfs9m", "rFWJ721dapSvuUxqQR7oyN0aesiOHh")
            ->regionId('cn-hangzhou')
            ->asDefaultClient();
        $result = AlibabaCloud::rpc()
            ->product('Dysmsapi')
            ->version('2017-05-25')
            ->action($action)
            ->method('POST')
            ->host('dysmsapi.aliyuncs.com')
            ->options([
                'query' => array_merge($a),])
            ->request();
        return $result->toArray();
    }

    public function smsTemplateQur()
    {
        $id = input('post.id', 0, 'intval');
        $m = new Sms();
        if ($id) {
            $result1 = $m->where(['id' => $id])->find();
            if ($result1) {
                $array = [
                    'TemplateCode' => $result1['TemplateCode'],
                ];
                $result = $this->AlibabaCloud('QuerySmsTemplate', $array);
                if ($result['Message'] == 'OK' && $result['Code'] == 'OK') {
                    $result11['TemplateStatus'] = $result['TemplateStatus'];
                    $result11['update_time'] = time();
                    if ($result['TemplateStatus'] == '2') {
                        $result11['Reason'] = $result['Reason'];
                    }
                    Sms::update($result11, ['id' => $id]);
                    return returnData(['msg' => "查询成功", "code" => 200]);
                } elseif ($result['Code'] != 'ok') {
                    $result11['Reason'] = $result['Message'];
                    $result11['TemplateStatus'] = '2';
                    $result11['update_time'] = time();
                    Sms::update($result11, ['id' => $id]);
                    return returnData(['msg' => $result['Message'], "code" => 201]);
                } else {
                    return returnData(['msg' => "系统故障，请联系管理员", "code" => 201]);
                }
            }else{
                return returnData(['msg' => '短信编号不存在', "code" => 201]);
            }
        }else{
            return returnData(['msg' => '短信编号不存在', "code" => 201]);
        }

    }

    public function deleteAdmin()
    {
        $TemplateCode = input('post.TemplateCode');
        if ($TemplateCode) {
            $array = [
                'TemplateCode' => $TemplateCode,
            ];
            $result = $this->AlibabaCloud('DeleteSmsTemplate', $array);
            if ($result['Message'] == 'OK' && $result['Code'] == 'OK') {
                Sms::where(['TemplateCode' => $TemplateCode])->delete();
                addAdminLog(getDecodeToken(),'删除短信模板：'.$TemplateCode);
                return returnData(['msg' => "删除成功", "code" => 200]);
            } elseif ($result['Code'] != 'OK') {
                return returnData(['msg' => $result['Message'], "code" => 201]);
            } else {
                return returnData(['msg' => "系统故障，请联系管理员", "code" => 201]);
            }
        }else{
            return returnData(['msg' => '短信编号不存在', "code" => 201]);
        }

    }
    public function editAdmin()
    {
        $default = input('post.default');
        $id = input('post.id');
        if (!empty($default) || !empty($id)) {
            if (Sms::where(['id' => $id])->value('TemplateStatus') != '2') {
                return returnData(['msg' => "短信模板未审核通过，无法更改", "code" => 201]);
            }

            $type = Sms::where(['id' => $id])->value('type');
            if($default == 2){
                if(Sms::where(['type' => $type,'default' => '2'])->value('id')){
                    return returnData(['msg' => "该类型已存在默认，无法操作", "code" => 201]);
                }
            }

            Sms::update(['default' => $default], ['id' => $id]);
            addAdminLog(getDecodeToken(),'编辑短信模板状态：'.$id);
            return returnData(['msg' => "设置成功", "code" => 200]);
        }else{
            return returnData(['msg' => '参数错误', "code" => 201]);
        }
    }


}