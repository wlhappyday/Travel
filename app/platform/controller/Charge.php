<?php
declare (strict_types = 1);

namespace app\platform\controller;

use app\api\model\Padmin;
use app\common\model\OrderCharge;
use app\common\service\Sign;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\Request;

class Charge
{
    /**
     * @author liujiong
     * @Note  信息费充值列表
     */
    public function list(){
        $where = [];

        $num = input('post.num/d','10','strip_tags');
        $order_no = input('post.order_no/s','','strip_tags');
        if ($order_no){
            $where['a.order_no'] = $order_no;
        }
        $pay_trade_no = input('post.pay_trade_no/s','','strip_tags');
        if ($pay_trade_no){
            $where['a.pay_trade_no'] = $pay_trade_no;
        }
        $status = input('post.status/d','','strip_tags');
        if ($status){
            $where['a.status'] = $status;
        }
        $order_result = new OrderCharge();
        $start_time = input('post.start_time/s','','strip_tags');
        if ($start_time){
            $order_result->whereTime('create_time', '>=', strtotime($start_time));
        }
        $end_time = input('post.end_time/s','','strip_tags');
        if ($end_time){
            $order_result->whereTime('create_time', '<=', strtotime($end_time));
        }
        $where['type'] = '2';
//p($where);
        $data = $order_result->alias('a')
            ->where($where)
            ->field('a.id,a.order_no,a.pay_trade_no,a.money,a.status,a.create_time,a.pay_time')
            ->order('a.id desc')
            ->paginate($num)->toarray();

        return returnData(['data'=>$data,'code'=>'200']);
    }
    /**
     * @author liujiong
     * @Note  更新用户余额
     */
    public function updMoney(){
        $id = getDecodeToken()['id'];

        $user = Padmin::where(['id'=>$id])->field('pay_user_id,cl_id,cl_key')->find();

        if(empty($user)){
            return returnData(['code' => '201', 'msg' => "参数不合法"]);
        }

        $pay_key = $user['cl_key'];
        $data['appid'] = $user['cl_id'];
//        $pay_key = 'ybZ000ydj7fK333IDXEYdzQVuxVRDnMg';
//        $data['appid'] = '1093340';

        Db::startTrans();
        try {
            $apiUrl = empty(getVariable('api_url'))?'https://apibei.payunke.com':getVariable('api_url');
            $url = $apiUrl.'/index/updMoney';

            $sign = (new Sign())->getSign($pay_key,$data);
            $data['sign'] = $sign;
            $post_data = json_encode($data);
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
            if($result['code'] != '200'){
                Db::commit();
                return returnData(['msg'=>$result['msg'],'code'=>'201']);
            }else{
                Padmin::where(['id'=>$id])->update(['money'=>$result['money']]);
                Db::commit();
                addPadminLog(getDecodeToken(),'更新用户余额：'.$result['money']);
                return returnData(['msg'=>'操作成功','code'=>'200']);
            }
        }catch (\Exception $e){
            Db::rollback();
//            p($e->getMessage());
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }


    }
    /**
     * @author liujiong
     * @Note  用户余额明细
     */
    public function moneyList(){
        $id = getDecodeToken()['id'];
        $page = input('post.page/d','','strip_tags');

        if ($page){
            $data['page'] = $page;
        }else{
            $data['page'] = '';
        }
        $type = input('post.type/d','','strip_tags');

        if ($type){
            $data['type'] = $type;
        }else{
            $data['type'] = '';
        }
        $user = Padmin::where(['id'=>$id])->field('pay_user_id,cl_id,cl_key')->find();

        if(empty($user)){
            return returnData(['code' => '201', 'msg' => "参数不合法"]);
        }

        $pay_key = $user['cl_key'];
        $data['appid'] = $user['cl_id'];
//        $pay_key = 'ybZ000ydj7fK333IDXEYdzQVuxVRDnMg';
//        $data['appid'] = '1093340';

        Db::startTrans();
        try {
            $apiUrl = empty(getVariable('api_url'))?'https://apibei.payunke.com':getVariable('api_url');
            $url = $apiUrl.'/index/moneyList';

            $sign = (new Sign())->getSign($pay_key,$data);
            $data['sign'] = $sign;
            $post_data = json_encode($data);
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

            if($result['code'] != '200'){
                Db::commit();
                return returnData(['msg'=>$result['msg'],'code'=>'201']);
            }else{
                Db::commit();
                return returnData($result);
            }
        }catch (\Exception $e){
            Db::rollback();
//            p($e->getMessage());
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }


    }
    /**
     * @author liujiong
     * @Note  用户余额充值订单
     */
    public function moneyCharge(){
        $id = getDecodeToken()['id'];
        $page = input('post.page/d','','strip_tags');

        if ($page){
            $data['page'] = $page;
        }else{
            $data['page'] = '';
        }
        $type = input('post.type/d','','strip_tags');

        if ($type){
            $data['type'] = $type;
        }else{
            $data['type'] = '';
        }
        $user = Padmin::where(['id'=>$id])->field('pay_user_id,cl_id,cl_key')->find();

        if(empty($user)){
            return returnData(['code' => '201', 'msg' => "参数不合法"]);
        }

        $pay_key = $user['cl_key'];
        $data['appid'] = $user['cl_id'];
//        $pay_key = 'ybZ000ydj7fK333IDXEYdzQVuxVRDnMg';
//        $data['appid'] = '1093340';

        Db::startTrans();
        try {
            $apiUrl = empty(getVariable('api_url'))?'https://apibei.payunke.com':getVariable('api_url');
            $url = $apiUrl.'/index/moneyCharge';

            $sign = (new Sign())->getSign($pay_key,$data);
            $data['sign'] = $sign;
            $post_data = json_encode($data);
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
            if($result['code'] != '200'){
                Db::commit();
                return returnData(['msg'=>$result['msg'],'code'=>'201']);
            }else{
                Db::commit();
                return returnData($result);
            }
        }catch (\Exception $e){
            Db::rollback();
//            p($e->getMessage());
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }


    }



}