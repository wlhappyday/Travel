<?php
// 这是系统自动生成的公共文件
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use app\common\model\AdminLog;
use app\common\model\Config;
use app\common\model\Jproduct;
use app\common\model\JproductReview;
use app\common\model\JuserBalanceRecords;
use app\common\model\JuserLog;
use app\common\model\Order;
use app\common\model\PadminBalanceRecords;
use app\common\model\PadminLog;
use app\common\model\Puserbalancerecords;
use app\common\model\Puserlog;
use app\common\model\XuserBalanceRecords;
use app\common\model\XuserLog;
use app\platform\model\Product_relation;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;

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
function get_rand_char($length){
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

function genggaijiage($order): bool
{
    if ($order['order_status'] == 2) {
        Order::update(["order_status" => 3, "transaction_id" => $order['order_id'], "pay_time" => time()], ["order_id" => $order['order_id']]);
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
        PadminBalanceRecords::update(["type" => 2], ["data_id" => $order['order_id'], "uid" => $order['p_id']]);
        $dateEEEEEEE = ["type" => 2];
        $dateEEEEEEEEEE = ["data_id" => $order['order_id'], "uid" => $order['store_id']];
        Puserbalancerecords::update(["type" => 2], ["data_id" => $order['order_id'], "uid" => $order['p_user_id']]);
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
    //   curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    $output = curl_exec($ch);
    //   var_dump(curl_error($ch));die();
    curl_close($ch);
    return $output;
}

/**
 * @param $passwd   明文密码
 * @return array
 * @author WjngJiamao
 * @Note   密码加密
 */
function encryptionPasswd($passwd)
{
    $passwdSalt = get_rand_char(20);

    return ['passwd' => md5($passwdSalt . $passwd), 'passwd_salt' => $passwdSalt];
}
/**
 * @author WjngJiamao
 * @Note  验证密码
 * @param string $passwd    用户提交的明文密码
 * @param array  $data      用户数据 包含 加密后的密码、盐值
 * @return bool
 */
function checkPasswd($passwd, $data)
{
    return $data['passwd'] == md5($data['passwd_salt'] . $passwd);
}
function returnData(array $data,int $code = 200,array $header=[]){
    return json($data)->code($code)->header($header);
}

/**
 * @author WjngJiamao
 * @Note  获取请求头中的Token并解析
 */
function getDecodeToken()
{
    $payload = JWTAuth::auth();
    $date = [];
    foreach ($payload as $key => $value) {
        $date[$key] = $value->getValue();
    }
    return $date;
}

/**
 * @throws ClientException
 * @throws ServerException
 */
function aliSms($date, $phone, $TemplateCode = "SMS_202815323")
{
    AlibabaCloud::accessKeyClient("LTAI4G4m3pm6GzdcdWQSfs9m", "rFWJ721dapSvuUxqQR7oyN0aesiOHh")
        ->regionId('cn-hangzhou')
        ->asDefaultClient();
//    p( [
//        'RegionId' => "cn-hangzhou",
//        'PhoneNumbers' => $phone,
//        'SignName' => "荣朴科技",
//        'TemplateCode' => $TemplateCode,
//        'TemplateParam' => $date,
//    ]);
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
        $data = app\api\model\Admin::where(['id' => $data['id'], 'phone' => $data['phone']])->value('id');
    } elseif ($type == '2') {
        $data = app\api\model\Padmin::where(['id'=>$data['id'],'phone'=>$data['phone'],'status'=>'0'])->value('id');
    }elseif ($type == '3'){
        $data = app\api\model\Juser::where(['id'=>$data['id'],'phone'=>$data['phone'],'status'=>'0'])->value('id');
    }elseif ($type == '4'){
        $data = app\api\model\Xuser::where(['id'=>$data['id'],'phone'=>$data['phone'],'status'=>'0'])->value('id');
    }
    if($data){
        return true;
    }else{
        return false;
    }

}

/**
 * @Note   增加日志记录
 * @param $data   token解密信息
 * @param $info   操作内容
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
function getIp($type='') {
    static $realip;
    $city='';
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
        if($realip=='::1'||$realip=='127.0.0.1'){
            $realip='127.0.0.1';
            $city=getCity();
        }else{
            $city=getCity($realip);
        }
        return ['ip'=>$realip,'address'=>$city['city'],'province'=>$city['province']];
    }
    return ['ip'=>$realip];

}
function getCity($ip = '')//获取地区
{
    $url = "http://api.map.baidu.com/location/ip?ak=M7Mc1jF8vmzGNx7XL1TAgHbBWB8oyuwv&ip=".$ip;
    $ip=json_decode(file_get_contents($url),true);
    if(!isset($ip['content'])){
        return ['city'=>'未知','province'=>'未知'];
    }
    if(!isset($ip['content']['address'])){
        return ['city'=>'未知','province'=>'未知'];
    }
    $data['city'] = $ip['content']['address'];
    $data['province'] = $ip['content']['address_detail']['province'];
    return $data;
}
//获取域名
function http(){
    $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
    $SERVER_NAME = $_SERVER['SERVER_NAME'];
    $SERVER_NAME = '192.168.0.124';

    //
    return $http_type.$SERVER_NAME;
}

//GET提交
    function httpGet($url) {
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
function ProductReviewAdd($data,$product_id){
    $product_result = new Jproduct();
    $uid = $product_result->where(['id'=>$product_id,'type'=>'1','mp_id'=>'6','status'=>'0'])->value('uid');
    if (!$uid){
        return ['msg'=>'该产品不符合规则','code'=>'201'];
    }

    $result = new JproductReview();
    $res = $result->where(['uid'=>$uid,'product_id'=>$product_id,'pid'=>$data['id']])->whereIn('state',[1,2])->value('id');
    if($res){
        return ['msg'=>'已存在该产品','code'=>'201'];
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

    function product_relation($pid,$product_id,$Review_id){
        $j_product = Jproduct::where(['status'=>'0','id'=>$product_id,'mp_id'=>'6'])->find();
        if ($j_product){
            Db::startTrans();
            try {
                $productReview = JproductReview::find($Review_id);
                $productReview->state = 2;
                $productReview->update_time = time();
                $productReview->save();
                $product_relation = new Product_relation();
                if ($productReview['state']=='2'){
                    $relation = Product_relation::where(['uid'=>$pid,'product_id'=>$product_id])->find();
                    if ($relation){
                        return ['code'=>'201','msg'=>'已绑定该产品'];
                    }
                    $product_relation->save([
                        'uid'  =>  $pid,
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
        }else{
            return ['code'=>'201','msg'=>'该产品被禁用或删除'];
        }

    }

/**
 * @author LvGang
 * @Note   获取系统变量
 *
 * @param string $name
 *
 * @return array
 */
function getVariable($name = '')
{
    $result = new Config();
    return $result->where(['title'=>$name])->value('value');
}