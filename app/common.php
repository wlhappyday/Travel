<?php
// 这是系统自动生成的公共文件
function p($arr){
    echo "<pre>";
    var_dump($arr);
    die();
}

/**
 * 生成随机字符串
 *
 * @param $length
 *
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
 * @author LvGang
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
 * @author LvGang
 * @Note  验证密码
 * @param string $passwd    用户提交的明文密码
 * @param array  $data      用户数据 包含 加密后的密码、盐值
 * @return bool
 */
function checkPasswd($passwd, $data)
{
    return $data['passwd'] == md5($data['passwd_salt'] . $passwd);
}
function returnData(array $data,int $code){
    return json($data)->code($code);
}
//生成验签
function signToken($userDate){
    $key='!@#$%*&';         //这里是自定义的一个随机字串，应该写在config文件中的，解密时也会用，相当    于加密中常用的 盐  salt
    $token=array(
        "iss"=>$key,        //签发者 可以为空
        "aud"=>'',          //面象的用户，可以为空
        "iat"=>time(),      //签发时间
        "nbf"=>time()+3,    //在什么时候jwt开始生效  （这里表示生成100秒后才生效）
        "exp"=> time()+200, //token 过期时间
        "data"=>$userDate
    );
    //  print_r($token);
    $jwt = JWT::encode($token, $key, "HS256");  //根据参数生成了 token
    return $jwt;
}
