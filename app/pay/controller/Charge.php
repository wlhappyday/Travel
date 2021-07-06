<?php
declare (strict_types=1);

namespace app\pay\controller;


use app\api\model\Padmin;
use app\common\model\JfeeChange;
use app\common\model\OrderCharge;
use think\facade\Db;
use think\Request;
use thans\jwt\facade\JWTAuth;

class Charge
{
    /**
     * @author liujiong
     * @Note  信息费充值
     */
    public function index()
    {
        $data['user_id'] = getDecodeToken()['id'];
        $data['type'] = getDecodeToken()['type'];
        if(!in_array($data['type'],[2,3,4])){
            return returnData(['code' => '-1', 'msg' => "非法请求"]);
        }

        $data['order_no'] =  make_order_no();
        $data['money'] = sprintf("%.2f", input('post.money/f', ''));
        $data['pay_type'] = input('post.pay_type/d', '');

        if($data['money'] < 0){
            return returnData(['code' => '201', 'msg' => "充值金额不合法"]);
        }
        if(!in_array($data['pay_type'],[1,2])){
            return returnData(['code' => '201', 'msg' => "充值方式错误"]);
        }
        if (OrderCharge::where(['order_no' => $data['order_no']])->count()) {
            return returnData(['code' => '201', 'msg' => "订单号（order_no）重复"]);
        }

        $data['create_time'] = time();

        $suffix = request()->isMobile() ? 'mobile' : 'pc';

        Db::startTrans();
        try {
            OrderCharge::insert($data);
            if($suffix!='pc'&&isQQBrowser()=='WeChat') {
                $result = $this->weChatJs($data['order_no']);
            }else{
                $result = $this->weChatNativesh($data['order_no']);
            }
            Db::commit();
            return returnData($result);
        }catch (\Exception $e){
            Db::rollback();
//            p($e->getMessage());
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }


    }

    public function weChatNativesh($order_no){
        if(!$order_no){
            return ['code'=>0,'msg'=>'找不到订单号'];
        }
        $orderInfo = OrderCharge::where(['order_no'=>$order_no])->find();
        if(empty($orderInfo)){
            return json(['code'=>0,'msg'=>'orderInfo->找到不到订单信息']);
        }
        if($orderInfo['status']=='2'){
            return ['code'=>0,'msg'=>'订单已支付'];
        }

        $apiData['appid'] = getVariable('appid');
        $apiData['sercet'] = getVariable('sercet');
        $apiData['mch_id'] = getVariable('mch_id');
//        $apiData['sub_mch_id'] = getVariable('sub_mch_id');
        $apiData['key'] = getVariable('key');
//        $apiData['apiclient_cert'] = getVariable('apiclient_cert');
//        $apiData['apiclient_key'] = getVariable('apiclient_key');
        $apiData['body'] = getVariable('body');

        $data = [];

        $data['appid'] = $apiData['appid'];
        $data['mch_id'] = $apiData['mch_id'];
        $data['nonce_str'] = md5($orderInfo['order_no']);
        $data['body'] = $apiData['body'];
        $data['out_trade_no'] = $orderInfo['order_no'];
        $data['product_id'] = $orderInfo['order_no'];
        $data['total_fee'] = $orderInfo['money']*100;
        $data['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];
        $data['notify_url'] = 'http://api.liujiong.top/wx.php';
//        $data['notify_url'] = url('pay/Charge/notifyurl');
        $data['trade_type'] = 'NATIVE';
        $data['device_info'] = 'WEB';

        $url = weixinpay($data,$apiData,$data['trade_type'],$orderInfo);

        return ['code'=>200,'msg'=>'成功','url'=>$url,'order_no'=>$orderInfo['order_no']];
    }
    public function weChatJs($order_no){
        if(empty($order_no)){
            $order_no=input('order_no');
        }
        $orderInfo = OrderCharge::where(['order_no'=>$order_no])->find();
        if(empty($orderInfo)){
            return json(['code'=>0,'msg'=>'orderInfo->找到不到订单信息']);
        }
        if($orderInfo['status']=='2'){
            return ['code'=>0,'msg'=>'订单已支付'];
        }
        $apiData['appid'] = getVariable('appid');
        $apiData['sercet'] = getVariable('sercet');

        $url='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$apiData['appid'].'&redirect_uri='.urlencode(url('pay/Charge/weChatJssh')).'&response_type=code&scope=snsapi_base&state='.$order_no.'#wechat_redirect';

        $this->redirect($url);
    }
    public function weChatJssh(){
        if(empty(input('state'))){
            die('别闹呀！');
        }

        $order_no=input('state');
        //获取订单信息
        $orderInfo = OrderCharge::where(['order_no'=>$order_no])->find();
        if(empty($orderInfo)){
            return json(['code'=>0,'msg'=>'orderInfo->找到不到订单信息']);
        }
        if($orderInfo['status']=='2'){
            return ['code'=>0,'msg'=>'订单已支付'];
        }

        $apiData['appid'] = getVariable('appid');
        $apiData['sercet'] = getVariable('sercet');
        $apiData['mch_id'] = getVariable('mch_id');
//        $apiData['sub_mch_id'] = getVariable('sub_mch_id');
        $apiData['key'] = getVariable('key');
//        $apiData['apiclient_cert'] = getVariable('apiclient_cert');
//        $apiData['apiclient_key'] = getVariable('apiclient_key');
        $apiData['body'] = getVariable('body');

        $data = [];

        $data['openid']=$this->toOpenid($apiData,$_GET['code']);
        $data['appid'] = $apiData['appid'];
        $data['mch_id'] = $apiData['mch_id'];
        $data['nonce_str'] = md5($orderInfo['order_no']);
        $data['body'] = $apiData['body'];
        $data['out_trade_no'] = $orderInfo['order_no'];
        $data['product_id'] = $orderInfo['order_no'];
        $data['total_fee'] = $orderInfo['money']*100;
        $data['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];
        $data['notify_url'] = 'http://api.liujiong.top/wx.php';
//        $data['notify_url'] = url('pay/Charge/notifyurl');
        $data['trade_type'] = 'JSAPI';
        $data['device_info'] = 'WEB';

        weixinpay($data,$apiData,$data['trade_type'],$orderInfo);
    }
    /**
     * @author liujiong
     * @Note  信息费充值回调
     */
    public function notifyurl(){
//        $post = file_get_contents("php://input");
//        $return_result = FromXml($post);//XML转数组
//        file_put_contents('wechatJdaFznotifyurl.html', '<p>'.json_encode($return_result,true).'</p>',FILE_APPEND);

        $return_result = json_decode('{"appid":"wxaf1f2c344b7ffa78","bank_type":"OTHERS","cash_fee":"1","device_info":"WEB","fee_type":"CNY","is_subscribe":"N","mch_id":"1501670811","nonce_str":"05d945313a2715463ccf03cd84511ddc","openid":"oB3nOw2zkFH2zNTRNBnDo8Vi_mTQ","out_trade_no":"E7021180420197050551","result_code":"SUCCESS","return_code":"SUCCESS","sign":"F2C64E2880AE2587C5F6D2714C0C267B","time_end":"20210702154446","total_fee":"1","trade_type":"NATIVE","transaction_id":"4200001203202107026388099179"}',true);


        $order_no = $return_result["out_trade_no"]; // 平台订单编号
        $cash_fee =  $return_result["cash_fee"];
        $paymoney = intval($cash_fee)/100; //支付的金额
        if ($return_result['return_code'] == 'SUCCESS' && $return_result['result_code'] == 'SUCCESS' ) {
            $orderInfo=OrderCharge::where(['order_no'=>$order_no])->find();

            /*** 获取通道的签名参数 start ***/

            $apiData['appid'] = getVariable('appid');
            $apiData['sercet'] = getVariable('sercet');
            $apiData['mch_id'] = getVariable('mch_id');
//        $apiData['sub_mch_id'] = getVariable('sub_mch_id');
            $apiData['key'] = getVariable('key');
//        $apiData['apiclient_cert'] = getVariable('apiclient_cert');
//        $apiData['apiclient_key'] = getVariable('apiclient_key');
            $apiData['body'] = getVariable('body');

            if(empty($apiData) || $apiData == null) return json(['code'=>0,'msg'=>'api参数错误']);
            ksort($return_result);
            $buff = "";
            foreach ($return_result as $k => $v) {
                if ($k != "sign" && $v != "" && !is_array($v)) {
                    $buff .= $k . "=" . $v . "&";
                }
            }
            $buff = trim($buff, "&");
            //签名步骤二：在string后加入KEY
            $string = $buff . "&key=" . $apiData['key'];
            //签名步骤三：MD5加密
            $string = md5($string);
            //签名步骤四：所有字符转为大写
            $my_sign = strtoupper($string);
            if ($my_sign == $return_result['sign']) {
                OrderCharge::update(['status'=>2,'pay_time'=>time(),'pay_trade_no'=>$return_result['transaction_id']],['order_no'=>$orderInfo['order_no']]);
                //添加信息费
                $j_fee_result = new JfeeChange();
                $j_fee_result->addFee($orderInfo['order_no']);
                echo '<xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>';
            }else{
                exit('sign_FAIL');
            }
        }else{
            //支付失败
            echo "支付失败";
        }
    }

    public function monitorOrders()
    {
        $order_no = input('post.order_no/d', '');

        if(!$order_no){
            return returnData(['code' => '201', 'msg' => "参数不存在"]);
        }
        $order = OrderCharge::where(['order_no' => $order_no, "status" => 2])->find();
        if (empty($order)) {
            return returnData(["code" => 201, "msg" => "待支付"]);
        } else {
            return returnData(["code" => 200, "msg" => "支付成功"]);
        }
    }

    public function toOpenid($array,$code,$type=''){
        if(empty($type)){
            $url='https://api.weixin.qq.com/sns/oauth2/access_token?appid='.str_replace(" ",'',$array['appid']).'&secret='.str_replace(" ",'',$array['secret']).'&code='.$code.'&grant_type=authorization_code';
        }else{
            $url='https://api.weixin.qq.com/sns/jscode2session?appid='.str_replace(" ",'',$array['appid']).'&secret='.str_replace(" ",'',$array['secret']).'&js_code='.$code.'&grant_type=authorization_code';
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        $result=json_decode($file_contents,true);
        if(!isset($result['openid'])){
            p(json_encode($array));
            echo json_encode(['code' => -1, 'msg' => 'openid获取失败，请检查账号!'.json_encode($result)."curlError:".curl_error($ch)],JSON_UNESCAPED_UNICODE);die();
        }
        $openid=$result['openid'];
        return $openid;
    }

    /**
     * @author liujiong
     * @Note  用户余额充值
     */
    public function charge()
    {
        $id = getDecodeToken()['id'];
        $type = getDecodeToken()['type'];
        if($type != 2){
            return returnData(['code' => '-1', 'msg' => "非法请求"]);
        }

        $user_data['money'] = sprintf("%.2f", input('post.money/f', ''));
        if($user_data['money'] < 0){
            return returnData(['code' => '201', 'msg' => "充值金额不合法"]);
        }
        $pay_id = Padmin::where(['id'=>$id])->value('pay_user_id');
        if(empty($pay_id)){
            return returnData(['code' => '201', 'msg' => "参数不合法"]);
        }

        Db::startTrans();
        try {
            $apiUrl = empty(getVariable('api_url'))?'https://apibei.payunke.com':getVariable('api_url');
            $user_data['channel_id'] = '21';
            $user_data['user_id'] = $pay_id;
            $url = $apiUrl.'/index/Recharge';
            $post_data = json_encode($user_data);
            $ch = curl_init ();
            $header = [
                'Content-Type: application/json',
                'Content-Length: ' . strlen ( $post_data )
            ];

            curl_setopt ( $ch, CURLOPT_HTTPHEADER, $header );
            curl_setopt ( $ch, CURLOPT_URL, $url );
            curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt ( $ch, CURLOPT_POST, 1 );
            curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, false );
            curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post_data );
            $output = curl_exec ( $ch );
            curl_close ( $ch );

            $result = json_decode($output,true);
            Db::commit();
            return returnData($result);
        }catch (\Exception $e){
            Db::rollback();
//            p($e->getMessage());
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }


    }
}