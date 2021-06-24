<?php
/**
 * @Author Quincy  2019/1/23 上午11:06
 * @Note 签名算法
 */

namespace app\common\service;


class Sign
{

    public $error = null;

    public function getError()
    {
        return $this->error;
    }

    /**
     * @author LvGang
     * @Note  生成签名
     * @param $secret   商户密钥
     * @param $data     参与签名的参数
     * @return string
     */
    public function getSign($secret, $data)
    {
        // echo('<pre>');
        // 去空
        if(isset($data['sign'])){
            unset($data['sign']);
        }
        $data = array_filter($data);
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = http_build_query($data);

        $string_a = urldecode($string_a);

        //签名步骤二：在string后加入mch_key
        $string_sign_temp = $string_a . "&key=" . $secret;

        //echo $string_sign_temp;
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);

        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);
        // p($result);
        return $result;
    }


    /**
     * @author LvGang
     * @Note   验证签名
     * @param $data
     * @param $orderStatus
     * @return bool
     */
    public function verifySign($data, $orderStatus = 2,$admin='') {
        // 验证参数中是否有签名
        if (!isset($data['sign']) || !$data['sign']) {
            $this->error = '没传签名字符串';
            return false;
        }
        // 要验证的签名串
        $sign = $data['sign'];
        unset($data['sign']);
        // 从数据库获取商户密钥
        $tableName = 'Users';
        $secret = model($tableName)->where(['mch_id'=>$data['appid']])->lock(true)->value('mch_key');
        if(!$secret){
            $secret = model('Merchants')->where(['mch_id'=>$data['appid']])->lock(true)->value('mch_key');
        }
        // 生成新的签名、验证传过来的签名
        $sign2 = $this->getSign($secret, $data);
        if ($sign != $sign2) {
            $this->error = '签名错误';
            return false;
        }
        return true;
    }
}