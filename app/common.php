<?php
// 这是系统自动生成的公共文件
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use app\common\model\AdminLog;
use app\common\model\Config;
use app\common\model\JfeeChange;
use app\common\model\Jproduct;
use app\common\model\JproductReview;
use app\common\model\JuserBalanceRecords;
use app\common\model\JuserLog;
use app\common\model\Order;
use app\common\model\OrderCharge;
use app\common\model\PadminBalanceRecords;
use app\common\model\PadminLog;
use app\common\model\Puserbalancerecords;
use app\common\model\Puserlog;
use app\common\model\PuserUserBalanceRecords;
use app\common\model\Sms;
use app\common\model\XuserBalanceRecords;
use app\common\model\XuserLog;
use app\platform\model\Product_relation;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\response\Json;

function p($arr)
{
    echo "<pre>";
    var_dump($arr);
    die();
}

/**
 * 生成随机字符串
 * @author WjngJiamao
 * @param $length
 * @return null|string
 */
function get_rand_char($length): ?string
{
    $str = null;
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max = strlen($strPol) - 1;
    for ($i = 0;
         $i < $length;
         $i++) {
        $str .= $strPol[rand(0, $max)];
    }
    return $str;
}

function curl_post_ssl($url, $xmldata, $apiData)
{
    $ch = curl_init();
    $params[CURLOPT_URL] = $url;    //请求url地址
    $params[CURLOPT_HEADER] = false; //是否返回响应头信息
    $params[CURLOPT_RETURNTRANSFER] = true; //是否将结果返回
    $params[CURLOPT_FOLLOWLOCATION] = true; //是否重定向
    $params[CURLOPT_POST] = true;
    $params[CURLOPT_POSTFIELDS] = $xmldata;
    $params[CURLOPT_SSL_VERIFYPEER] = false;
    $params[CURLOPT_SSL_VERIFYHOST] = false;
    //以下是证书相关代码
    $params[CURLOPT_SSLCERTTYPE] = 'PEM';
    $params[CURLOPT_SSLCERT] = $apiData['apiclient_cert'];
    $params[CURLOPT_SSLKEYTYPE] = 'PEM';
    $params[CURLOPT_SSLKEY] = $apiData['apiclient_key'];
    curl_setopt_array($ch, $params); //传入curl参数
    $content = curl_exec($ch); //执行
    curl_close($ch); //关闭连接
    return $content;
}
function postHTTPS($url, $post_data = '', $timeout = 5){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    if ($post_data != '') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_REFERER, 'http://api.payunk.com/');
    $file_contents = curl_exec($ch);
    curl_close($ch);
    return $file_contents;
}

function ToXml($values): string
{
    if (!is_array($values)
        || count($values) <= 0) {
        header("Content-type:text/html;charset=utf-8");
        print_r("数组数据异常！");
        exit;
    }

    $xml = "<xml>";
    foreach ($values as $key => $val) {
        if (is_numeric($val)) {
            $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
        } else {
            $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
    }
    $xml .= "</xml>";
    return $xml;
}

function FromXml($xml)
{
    if (!$xml) {
        return '';
    }
    //将XML转为array
    //禁止引用外部xml实体
    libxml_disable_entity_loader();
    return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA), JSON_UNESCAPED_UNICODE), true);
}

function weixinpay($data, $apiData, $trade_type = 'refund',$orderInfo=[])
{
    $chanrgeResult = new OrderCharge();
    $data['sign'] = weixinsign($data, $apiData['key']);
    if ($trade_type == 'refund') {
        $apiUrl = "https://api.mch.weixin.qq.com/secapi/pay/refund";
        $xml = curl_post_ssl($apiUrl, ToXml($data), $apiData);
    } elseif ($trade_type == 'refundOrder') {
        $apiUrl = "https://api.mch.weixin.qq.com/pay/orderquery";
        $xml = curl_post_ssl($apiUrl, ToXml($data), $apiData);
    } elseif ($trade_type == 'fenzhang') {
        $data['sign'] = createWechatPaySignWithHash($data, $apiData['key']);
        $apiUrl = "https://api.mch.weixin.qq.com/secapi/pay/multiprofitsharing";
        $xml = curl_post_ssl($apiUrl, ToXml($data), $apiData);
    } elseif ($trade_type == 'fenzhangwj') {
        $data['sign'] = createWechatPaySignWithHash($data, $apiData['key']);
        $apiUrl = "https://api.mch.weixin.qq.com/secapi/pay/profitsharingfinish";
        $xml = curl_post_ssl($apiUrl, ToXml($data), $apiData);
    } elseif ($trade_type == 'NATIVE' || $trade_type == 'JSAPI') {
        $apiUrl = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $xml = postHTTPS($apiUrl, ToXml($data));
    } else {
        return ["code" => -1, "msg" => "请求错误"];
    }
    if (empty($apiUrl)) {
        return ["code" => -1, "msg" => "参数错误"];
    }


    $result = FromXml($xml);

    if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
        switch ($trade_type){
            case 'NATIVE' :
                $url = $result['code_url'];//二维码连接
                $chanrgeResult->where(['order_no'=>$orderInfo['order_no']])->update(['qrcode'=>$url]);
                return $url;
                break;
            case 'JSAPI':
                $success_url=url('api/index/success');
                $error_url=url('api/index/success');
                $wx_h5_data = array(
                    'appId'=>$data['appid'],
                    'nonceStr'=>md5(time()),
                    'package'=>'prepay_id='.$result['prepay_id'],
                    'signType'=>'MD5',
                    'timeStamp'=>time()
                );
                $wx_h5_sign = weixinsign($wx_h5_data,$apiData['key']);
                $wx_h5_data['paySign'] = $wx_h5_sign;
                $wx_h5_data['success_url']=$success_url;
                $wx_h5_data['error_url']=$error_url;
                $wx_h5_data['out_trade_no']=$orderInfo['order_no'];
                weixinJsPay($wx_h5_data);
                exit;
                break;
            default:
                return $result;
                break;
        }

    } else {
        echo(json_encode($result, JSON_UNESCAPED_UNICODE));
        exit;
    }
}

function numberOrderId()
{
    return date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8) . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8) . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

function weixinJsPay($wx_h5_data)
{
    $out_trade_no = isset($wx_h5_data['out_trade_no']) ? $wx_h5_data['out_trade_no'] : '';
    $success_url = isset($wx_h5_data['success_url']) ? $wx_h5_data['success_url'] : '';
    $error_url = isset($wx_h5_data['error_url']) ? $wx_h5_data['error_url'] : '';
    $wx_h5_html = '<script type="text/javascript">';
    $wx_h5_html .= 'function onBridgeReady(){
									 WeixinJSBridge.invoke(
						      \'getBrandWCPayRequest\', {
						         "appId":"' . $wx_h5_data['appId'] . '",
						         "timeStamp":"' . $wx_h5_data['timeStamp'] . '",
						         "nonceStr":"'.$wx_h5_data['nonceStr'].'",   
						         "package":"'.$wx_h5_data['package'].'",
						         "signType":"'.$wx_h5_data['signType'].'",
						         "paySign":"'.$wx_h5_data['paySign'].'"
						      },
						      function(res){
						      if(res.err_msg == "get_brand_wcpay_request:ok" ){
						            window.location.href="'.$success_url.'";
						      }else{
						          window.location.href="'.$error_url.'?ordernumber='.$out_trade_no.'";
						      }
						      
						   }); 
						}
						window.onload = function(){
                            callpay(); 
                        }
						function callpay()
                            	{
                            		if (typeof WeixinJSBridge == "undefined"){
                            			if( document.addEventListener ){
                            				document.addEventListener(\'WeixinJSBridgeReady\', onBridgeReady, false);
                            			}else if (document.attachEvent){
                            				document.attachEvent(\'WeixinJSBridgeReady\', onBridgeReady); 
                            				document.attachEvent(\'onWeixinJSBridgeReady\', onBridgeReady);
                            			}
                            		}else{
                            			onBridgeReady();
                            		}
                            	}
								';
    $wx_h5_html .= '</script>';
    print_r($wx_h5_html);
    exit;
}

function createWechatPaySignWithHash($data, $mach_key): string
{
    $para = array();
    foreach ($data as $key => $val) {
        if ($key == "sign" || $key == "key" || $val == "") continue;
        else    $para[$key] = $data[$key];
    }
    ksort($para);
    reset($para);
    $arg = "";
    foreach ($para as $key => $val) {
        $arg .= $key . "=" . charset_encode($val, "UTF-8", "UTF-8") . "&";
    }
    $prestr = substr($arg, 0, -1);  //去掉最后一个&号
    $str = $prestr . "&key=" . $mach_key;
    return strtoupper(hash_hmac("sha256", $str, $mach_key));
}

function weixinsign($parameter, $keyword, $type = 1): string
{
    //签名步骤一：按字典序排序参数
    $para = array();
    foreach ($parameter as $key => $val) {
        if ($key == "sign" || $key == "key" || $val == "") continue;
        else $para[$key] = $parameter[$key];
    }
    ksort($para);
    reset($para);
    $arg = "";
    foreach ($para as $key => $val) {
        $arg .= $key . "=" . charset_encode($val, "UTF-8", "UTF-8") . "&";
    }
    $prestr = substr($arg, 0, -1);  //去掉最后一个&号
    //签名步骤二：在string后加入KEY
    $prestr .= "&key=" . $keyword;
    if ($type == 1) {
        //签名步骤三：MD5加密
        $mysign = md5($prestr);
    } else {
        $s = hash_hmac('sha256', $prestr, $keyword, true);
        $mysign = base64_encode($s);
    }
    return strtoupper($mysign);
}

function charset_encode($input, $_output_charset = "UTF-8", $_input_charset = "UTF-8")
{
    if ($_input_charset == $_output_charset || $input == null) {
        $output = $input;
    } elseif (function_exists("mb_convert_encoding")) {
        $output = mb_convert_encoding($input, $_output_charset, $_input_charset);
    } elseif (function_exists("iconv")) {
        $output = iconv($_input_charset, $_output_charset, $input);
    } else die("sorry, you have no libs support for charset change.");
    return $output;
}

function genggaijiage($order, $transaction_id = ""): bool
{
    if ($order['order_status'] == 2) {
        Order::update(["order_status" => 3, "transaction_id" => $transaction_id, "pay_time" => time()], ["order_id" => $order['order_id']]);
        $pAdminBalanceRecordsMoney = bcmul(bcsub($order['p_price'], $order["store_price"]), $order["goods_num"]);
        PadminBalanceRecords::create(["data_id" => $order['order_id'], "uid" => $order['p_id'], "p_price" => bcmul($order['p_price'], $order["goods_num"]), "money" => $pAdminBalanceRecordsMoney]);
        $dateEEEEEEE = ["data_id" => $order['order_id'], "uid" => $order['store_id'], "p_price" => bcmul($order['store_price'], $order["goods_num"]), "money" => bcmul($order['store_price'], $order["goods_num"])];
        $pUserBalancerMoney = bcmul(bcsub($order['goods_price'], $order["p_price"]), $order["goods_num"]);
        Puserbalancerecords::create(["data_id" => $order['order_id'], "uid" => $order['store_id'], "p_price" => bcmul($order['goods_price'], $order["goods_num"]), "money" => $pUserBalancerMoney]);
        if ($order['store_type'] == 1) {
            JuserBalanceRecords::create($dateEEEEEEE);
        } elseif ($order['store_type'] == 2) {
            XuserBalanceRecords::create($dateEEEEEEE);
        }
        if (!empty($order["u_user_id"]) && bccomp($order['good_distribution'], "0.00") > 0) {
            PuserUserBalanceRecords::create(["data_id" => $order['order_id'], "uid" => $order['u_user_id'], "p_price" => $order["order_amount"], "money" => bcmul($order["order_amount"], $order['good_distribution'])]);
        }
        return true;
    } elseif ($order['order_status'] == 4) {
        $pAdminBalanceRecordsMoney = bcmul(bcsub($order['p_price'], $order["store_price"]), $order["surplus_num"]);
        PadminBalanceRecords::update(["p_price" => bcmul($order['p_price'], $order["surplus_num"]), "money" => $pAdminBalanceRecordsMoney], ["data_id" => $order['order_id'], "uid" => $order['p_id']]);
        $dateEEEEEEE = ["p_price" => bcmul($order['store_price'], $order["surplus_num"]), "money" => bcmul($order['store_price'], $order["surplus_num"])];
        $dateEEEEEEEEEE = ["data_id" => $order['order_id'], "uid" => $order['store_id']];
        $pUserBalancerMoney = bcmul(bcsub($order['goods_price'], $order["p_price"]), $order["surplus_num"]);
        Puserbalancerecords::update(["p_price" => bcmul($order['goods_price'], $order["surplus_num"]), "money" => $pUserBalancerMoney], ["data_id" => $order['order_id'], "uid" => $order['p_user_id']]);
        if ($order['store_type'] == 1) {
            JuserBalanceRecords::update($dateEEEEEEE, $dateEEEEEEEEEE);
        } elseif ($order['store_type'] == 2) {
            XuserBalanceRecords::update($dateEEEEEEE, $dateEEEEEEEEEE);
        }
        if (!empty($order["u_user_id"]) && bccomp($order['good_distribution'], "0.00") > 0) {
            PuserUserBalanceRecords::update(["p_price" => $order["surplus_price"], "money" => bcmul($order["surplus_price"], $order['good_distribution'])], ["data_id" => $order['order_id'], "uid" => $order['u_user_id']]);
        }
        return true;
    } elseif ($order['order_status'] == 5) {
        PadminBalanceRecords::update(["type" => 2, "money" => 0, "p_price" => 0], ["data_id" => $order['order_id'], "uid" => $order['p_id']]);
        $dateEEEEEEE = ["type" => 2, "money" => 0, "p_price" => 0];
        $dateEEEEEEEEEE = ["data_id" => $order['order_id'], "uid" => $order['store_id']];
        Puserbalancerecords::update(["type" => 2, "money" => 0, "p_price" => 0], ["data_id" => $order['order_id'], "uid" => $order['p_user_id']]);
        if ($order['store_type'] == 1) {
            JuserBalanceRecords::update($dateEEEEEEE, $dateEEEEEEEEEE);
        } elseif ($order['store_type'] == 2) {
            XuserBalanceRecords::update($dateEEEEEEE, $dateEEEEEEEEEE);
        }
        if (!empty($order["u_user_id"]) && bccomp($order['good_distribution'], "0.00") > 0) {
            PuserUserBalanceRecords::create(["type" => 2, "money" => 0, "p_price" => 0], ["data_id" => $order['order_id'], "uid" => $order['u_user_id']]);
        }
        return true;
    } else {
        return false;
    }
}
function genggaijiageMen($order, $transaction_id = ""): bool
{
    if ($order['order_status'] == 2) {
        Order::update(["order_status" => 3, "transaction_id" => $transaction_id, "pay_time" => time()], ["order_id" => $order['order_id']]);
        $pAdminBalanceRecordsMoney = bcmul(bcsub($order['p_price'], $order["store_price"]), $order["goods_num"]);
        PadminBalanceRecords::create(["data_id" => $order['order_id'], "uid" => $order['p_id'], "p_price" => bcmul($order['p_price'], $order["goods_num"]), "money" => $pAdminBalanceRecordsMoney]);
        $dateEEEEEEE = ["data_id" => $order['order_id'], "uid" => $order['store_id'], "p_price" => bcmul($order['store_price'], $order["goods_num"]), "money" => bcmul($order['store_price'], $order["goods_num"])];
        if ($order['store_type'] == 1) {
            JuserBalanceRecords::create($dateEEEEEEE);
        } elseif ($order['store_type'] == 2) {
            XuserBalanceRecords::create($dateEEEEEEE);
        }
        return true;
    } elseif ($order['order_status'] == 4) {
        $pAdminBalanceRecordsMoney = bcmul(bcsub($order['p_price'], $order["store_price"]), $order["surplus_num"]);
        PadminBalanceRecords::update(["p_price" => bcmul($order['p_price'], $order["surplus_num"]), "money" => $pAdminBalanceRecordsMoney], ["data_id" => $order['order_id'], "uid" => $order['p_id']]);
        $dateEEEEEEE = ["p_price" => bcmul($order['store_price'], $order["surplus_num"]), "money" => bcmul($order['store_price'], $order["surplus_num"])];
        $dateEEEEEEEEEE = ["data_id" => $order['order_id'], "uid" => $order['store_id']];
        if ($order['store_type'] == 1) {
            JuserBalanceRecords::update($dateEEEEEEE, $dateEEEEEEEEEE);
        } elseif ($order['store_type'] == 2) {
            XuserBalanceRecords::update($dateEEEEEEE, $dateEEEEEEEEEE);
        }
        return true;
    } elseif ($order['order_status'] == 5) {
        PadminBalanceRecords::update(["type" => 2, "money" => 0, "p_price" => 0], ["data_id" => $order['order_id'], "uid" => $order['p_id']]);
        $dateEEEEEEE = ["type" => 2, "money" => 0, "p_price" => 0];
        $dateEEEEEEEEEE = ["data_id" => $order['order_id'], "uid" => $order['store_id']];
        if ($order['store_type'] == 1) {
            JuserBalanceRecords::update($dateEEEEEEE, $dateEEEEEEEEEE);
        } elseif ($order['store_type'] == 2) {
            XuserBalanceRecords::update($dateEEEEEEE, $dateEEEEEEEEEE);
        }
        return true;
    } else {
        return false;
    }
}

function getSign($secret, $data): string
{
    // 去空
    $data = array_filter($data);
    //签名步骤一：按字典序排序参数
    ksort($data);
    if ($data['pay_type'] == 'AliRoyalty') {
        foreach ($data['royalty_parameters'] as $k => $v) {
            ksort($data['royalty_parameters'][$k]);
        }
    }
    $string_a = http_build_query($data);
    $string_a = urldecode($string_a);
    //签名步骤二：在string后加入mch_key
    $string_sign_temp = $string_a . "&key=" . $secret;
    // var_dump($string_sign_temp);
    //签名步骤三：MD5加密
    $sign = md5($string_sign_temp);
    // 签名步骤四：所有字符转为大写
    return strtoupper($sign);
}

function post($url, $data)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $output = curl_exec($ch);
    //   var_dump(curl_error($ch));die();
    curl_close($ch);
    return $output;
}

/**
 * @param $passwd   string
 * @return array
 * @author WjngJiamao
 * @Note   密码加密
 */
function encryptionPasswd(string $passwd): array
{
    $passwdSalt = get_rand_char(20);

    return ['passwd' => md5($passwdSalt . $passwd), 'passwd_salt' => $passwdSalt];
}

/**
 * @param string $passwd 用户提交的明文密码
 * @param array $data 用户数据 包含 加密后的密码、盐值
 * @return bool
 * @author WjngJiamao
 * @Note  验证密码
 */
function checkPasswd(string $passwd, $data): bool
{
    return $data['passwd'] == md5($data['passwd_salt'] . $passwd);
}

function returnData(array $data, int $code = 200, array $header = []): Json
{
    return json($data)->code($code)->header($header);
}

/**
 * @author WjngJiamao
 * @Note  获取请求头中的Token并解析
 */
function getDecodeToken(): array
{
    $payload = JWTAuth::auth();
    $date = [];
    foreach ($payload as $key => $value) {
        $date[$key] = $value->getValue();
    }
    return $date;
}

function aliSmsSend($phone,$userType,$user_id,$type){
    $sms_result = new Sms();
    $datas= $sms_result->where(['type'=>$type])->paginate(10,false, ['query'=>request()->param()])->toArray()['data'][0];

    $TemplateCode=$datas['TemplateCode'];
    if($type == '3'){
        $code2=rand('100000','999999');
        $code=json_encode(['code'=>$code2]);
    }
    $result=aliSms($code,$phone,$TemplateCode);
    switch ($result['Code']){
        case "OK":
            $fee_result = new JfeeChange();
            $fee_result->subFee($userType,$user_id);
            return ['code'=>1000,'msg'=>'获取成功'];
            break;
        case "isv.MOBILE_NUMBER_ILLEGAL":
            return ['code'=>404,'msg'=>'手机号有误，请重新填写'];
            break;
        default:
            return ['code'=>404,'msg'=>'验证码发送失败，请重试'];
            break;
    }

}
/**
 * @throws ClientException
 * @throws ServerException
 */
function aliSms($date, $phone, $TemplateCode = "SMS_202815323"): array
{
    AlibabaCloud::accessKeyClient("LTAI4G4m3pm6GzdcdWQSfs9m", "rFWJ721dapSvuUxqQR7oyN0aesiOHh")
        ->regionId('cn-hangzhou')
        ->asDefaultClient();
    $result = AlibabaCloud::rpc()
        ->product('Dysmsapi')
        ->version('2017-05-25')
        ->action('SendSms')
        ->method('POST')
        ->host('dysmsapi.aliyuncs.com')
        ->options([
            'query' => [
                'PhoneNumbers' => $phone,
                'SignName' => "荣朴科技",
                'TemplateCode' => $TemplateCode,
                'TemplateParam' => $date,
            ],
        ])
        ->request();
    return $result->toArray();
}

/**
 * @Note  验证Token
 */
function isUserToken($data, $type)
{
    if ($type != $data['type']) {
        return false;
    }
    if ($type == '1') {
        $data = (new app\api\model\Admin)->where(['id' => $data['id'], 'phone' => $data['phone']])->value('id');
    } elseif ($type == '2') {
        $data = (new app\api\model\Padmin)->where(['id' => $data['id'], 'phone' => $data['phone'], 'status' => '0'])->value('id');
    }elseif ($type == '3'){
        $data = (new app\api\model\Juser)->where(['id' => $data['id'], 'phone' => $data['phone'], 'status' => '0'])->value('id');
    }elseif ($type == '4'){
        $data = (new app\api\model\Xuser)->where(['id' => $data['id'], 'phone' => $data['phone'], 'status' => '0'])->value('id');
    }
    if($data){
        return true;
    }else{
        return false;
    }

}

/**
 * @Note   增加日志记录
 */
function addXuserLog($data,$info){
    $x_user_log = new XuserLog();
    return $x_user_log->addData($data,$info);
}
function addJuserLog($data,$info){
    $x_user_log = new JuserLog();
    return $x_user_log->addData($data,$info);
}
function addPadminLog($data,$info){
    $x_user_log = new PadminLog();
    return $x_user_log->addData($data,$info);
}
function addAdminLog($data,$info){
    $x_user_log = new AdminLog();
    return $x_user_log->addData($data,$info);
}
function addPuserLog($data,$info){
    $x_user_log = new Puserlog();
    return $x_user_log->addData($data,$info);
}

/**
 * @author xi 2019/5/23 12:44
 * @Note
 */
function getIp($type = ''): array
{
    if (isset($_SERVER)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $realip = $_SERVER['REMOTE_ADDR'];
        }
    } else {
        if (getenv('HTTP_X_FORWARDED_FOR')) {
            $realip = getenv('HTTP_X_FORWARDED_FOR');
        } else if (getenv('HTTP_CLIENT_IP')) {
            $realip = getenv('HTTP_CLIENT_IP');
        } else {
            $realip = getenv('REMOTE_ADDR');
        }
    }
    if(empty($type)){
        if ($realip == '::1' || $realip == '127.0.0.1') {
            $realip = '127.0.0.1';
            $city = getCity();
        } else {
            $city = getCity($realip);
        }
        return ['ip' => $realip, 'address' => $city['city'], 'province' => $city['province']];
    }
    return ['ip' => $realip];

}
function getCity($ip = ''): array//获取地区
{
    $url = "http://api.map.baidu.com/location/ip?ak=M7Mc1jF8vmzGNx7XL1TAgHbBWB8oyuwv&ip=" . $ip;
    $ip = json_decode(file_get_contents($url), true);
    if (!isset($ip['content'])) {
        return ['city' => '未知', 'province' => '未知'];
    }
    if (!isset($ip['content']['address'])) {
        return ['city' => '未知', 'province' => '未知'];
    }
    $data['city'] = $ip['content']['address'];
    $data['province'] = $ip['content']['address_detail']['province'];
    return $data;
}
//获取域名
function http(): string
{
    $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
    $SERVER_NAME = $_SERVER['SERVER_NAME'];
    return $http_type . $SERVER_NAME;
}

//GET提交
function httpGet($url)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_URL, $url);
    $res = curl_exec($curl);
    curl_close($curl);
    return $res;
}

//创建产品审核
function ProductReviewAdd($data, $product_id): array
{
    $product_result = new Jproduct();
    $uid = $product_result->where(['id' => $product_id, 'type' => '1', 'mp_id' => '6', 'status' => '0'])->value('uid');
    if (!$uid) {
        return ['msg' => '该产品不符合规则', 'code' => '201'];
    }

    $result = new JproductReview();
    $res = $result->where(['uid' => $uid, 'product_id' => $product_id, 'pid' => $data['id']])->whereIn('state', [1, 2])->value('id');
    if ($res) {
        return ['msg' => '已存在该产品', 'code' => '201'];
    }
    (new think\facade\Db)->startTrans();
    try {
        $result->insert(['product_id' => $product_id, 'uid' => $uid, 'pid' => $data['id'], 'create_time' => time()]);
        addPadminLog($data, '创建产品审核：' . $data['id']);
        (new think\facade\Db)->commit();
        return ['msg' => '创建成功', 'code' => '200'];
    } catch (Exception $e) {
        (new think\facade\Db)->rollback();
        return ['code' => '201', 'msg' => '网络异常'];
    }
}

function product_relation($pid, $product_id, $Review_id)
{
    $j_product = Jproduct::where(['status' => '0', 'id' => $product_id, 'mp_id' => '6'])->find();
    if ($j_product) {
        Db::startTrans();
        try {
            $productReview = JproductReview::find($Review_id);
            $productReview->state = 2;
            $productReview->update_time = time();
            $productReview->save();
            $product_relation = new Product_relation();
            if ($productReview['state'] == '2') {
                $relation = Product_relation::where(['uid' => $pid, 'product_id' => $product_id])->find();
                if ($relation) {
                    return ['code' => '201', 'msg' => '已绑定该产品'];
                }
                $product_relation->save([
                    'uid' => $pid,
                    'type' => $j_product['type'],
                    'product_id' => $product_id,
                    'price' => $j_product['money'],
                    'mp_id' => $j_product['mp_id']
                ]);
                Db::commit();
                return ['code' => '200', 'msg' => '操作成功'];
            } else {
                return ['code' => '201', 'msg' => '该产品未审核'];
            }
        } catch (Exception $e) {
            Db::rollback();
            return ['code' => '201', 'msg' => '网络异常'];
        }
    } else {
        return ['code' => '201', 'msg' => '该产品被禁用或删除'];
    }

}


/**
 * @param string $name
 *
 * @return array
 * @author LvGang
 * @Note   获取系统变量
 *
 */
function getVariable(string $name = '')
{
    $result = new Config();
    return $result->where(['title' => $name])->value('value');
}

/**
 * 生成唯一订单号
 */
function make_order_no(){
    $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
    $orderSn =
        $yCode[intval(date('Y')) - 2017] . strtoupper(dechex(date('m'))) . date(
            'd') . substr(time(), -5) . substr(microtime(), 3, 6) . sprintf(
            '%04d', rand(10000, 99999));
    $order_result = new OrderCharge();

    if($order_result->where(['order_no' => $orderSn])->find())make_order_no();
    return $orderSn;
}
function isQQBrowser(){
    // p(strpos($_SERVER['HTTP_USER_AGENT'], 'QQ') );
    if(strpos($_SERVER['HTTP_USER_AGENT'], 'QQ') !== false){
        if(strpos($_SERVER['HTTP_USER_AGENT'], '_SQ_') !== false){
            return "QQ";  //QQ内置浏览器
        }elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'WeChat') !== false){
            return "WeChat";//微信浏览器
        }else{
            return "QQBrowser";  //QQ浏览器
        }
    }elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false){
        return "WeChat";//微信浏览器
    }elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false){
        return false;
    }
    return false;
}