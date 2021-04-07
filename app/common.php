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
