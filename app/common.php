<?php
// 这是系统自动生成的公共文件
use thans\jwt\facade\JWTAuth;

function p($arr){
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
/**
 * @author WjngJiamao
 * @Note   密码加密
 * @param $passwd   明文密码
 * @return array
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
function getDecodeToken(){
    $payload = JWTAuth::auth();
    $date=[];
    foreach ($payload as $key => $value){
        $date[$key]=$value->getValue();
    }
    return $date;
}

/**
 * @author liujiong
 * @Note  验证Token
 */
function isUserToken($data,$type){
    if($type != $data['type']){
        return false;
    }
    if($type == '1'){
        $data = app\api\model\Admin::where(['id'=>$data['id'],'phone'=>$data['phone']])->value('id');
    }elseif ($type == '2'){
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
 * @author liujiong
 * @Note   增加日志记录
 * @param $data   token解密信息
 * @param $info   操作内容
 */
function addXuserLog($data,$info){
    $x_user_log = new \app\common\model\XuserLog();
    return $x_user_log->addData($data,$info);
}
function addJuserLog($data,$info){
    $x_user_log = new \app\common\model\JuserLog();
    return $x_user_log->addData($data,$info);
}
function addPadminLog($data,$info){
    $x_user_log = new \app\common\model\PadminLog();
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