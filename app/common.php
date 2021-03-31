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
