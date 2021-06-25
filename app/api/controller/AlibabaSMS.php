<?php


namespace app\api\controller;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use app\common\model\Sms;
use think\response\Json;


class AlibabaSMS
{
    /**
     * @throws ClientException
     * @throws ServerException
     */
    public function addSmsTemplate(): Json
    {
        if ($this->request->isPost()) {
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

    }

    /**
     * @throws ClientException
     * @throws ServerException
     */
    public function AlibabaCloud($action, $a): array
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

    /**
     * @throws ClientException
     * @throws ServerException
     */
    public function smsTemplateQur(): Json
    {
        if ($this->request->isPost()) {
            $id = input('post.id', 0, 'intval');
            $m = model('Sms');
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
                }
            }
        }
    }

    public function editAdmin(): Json
    {
        if ($this->request->isPost()) {
            $id = input('post.id');
            if (Sms::where(['id' => $id])->value('TemplateStatus') != '1') {
                return returnData(['msg' => "短信模板未审核通过，无法更改", "code" => 201]);
            }
            Sms::update(['default' => '2'], ['type' => Sms::where(['id' => $id])->value('type'), 'default' => '1']);
            Sms::update(['default' => '1'], ['id' => $id]);
            return returnData(['msg' => "设置成功", "code" => 200]);
        }
    }

    /**
     * @throws ClientException
     * @throws ServerException
     */
    public function deleteAdmin()
    {
        if ($this->request->isPost()) {
            $id = input('post.id');
            $m = model('Sms');
            if ($id) {
                $array = [
                    'TemplateCode' => $id,
                ];
                $result = $this->AlibabaCloud('DeleteSmsTemplate', $array);
                if ($result['Message'] == 'OK' && $result['Code'] == 'OK') {
                    Sms::where(['TemplateCode' => $id])->delete();
                    return returnData(['msg' => "删除成功", "code" => 200]);
                } elseif ($result['Code'] != 'OK') {
                    return returnData(['msg' => $result['Message'], "code" => 201]);
                } else {
                    return returnData(['msg' => "系统故障，请联系管理员", "code" => 201]);
                }
            }
        }
    }

    public function smssz()
    {
        $datas = Sms::where('delete_time', '=', 0)->select();
        return $this->fetch();
    }

    public function editSmsTemplate()
    {
        $postData = input('param.id');
        if (!$postData) {
            return;
        }
        $datas = model('Sms')->where(['id' => $postData])->find();
        $this->assign("vo", $datas);
        return $this->fetch('edit_sms_template');
    }

    /**
     * @throws ClientException
     * @throws ServerException
     */
    public function sendSMS($phone, $date): array
    {
//        $phone = "17639349939";
//        $date = json_encode(['balance'=>"21.22"]);
        return aliSms($date, $phone);
    }
}