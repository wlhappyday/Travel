<?php
declare (strict_types = 1);

namespace app\line\controller;

use app\common\model\Accounts;
use app\common\model\Padmin;
use app\common\model\PfzAccount;
use app\common\model\Xuser;
use app\common\service\Sign;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\facade\Validate;
use think\Request;

class FzAccount
{

    /**
     * @author liujiong
     * @Note  分账接收方审核不通过
     */
    public function statusNo(){
        $uid = getDecodeToken()['id'];
        if(!Xuser::where(['id'=>$uid,'status'=>'0'])->find()){
            return returnData(['msg'=>'该用户已被紧用','code'=>'201']);
        }
        $id = input('post.id/d','','strip_tags');
        if(!PfzAccount::where(['id'=>$id,'status'=>'1'])->find()){
            return returnData(['msg'=>'没有需要审核的分账接收方','code'=>'201']);
        }
        $status = input('post.status/d','','strip_tags');
        $desc = input('post.desc/s','','strip_tags');
        if ($status == 3){
            Db::startTrans();
            try {
                PfzAccount::where(['id'=>$id,'uid'=>$uid,'state'=>'2'])->update(['status'=>$status,'update_time'=>time(),'desc'=>$desc]);
                addXuserLog(getDecodeToken(),'分账接收方审核不通过：'.$id);
                Db::commit();
                return returnData(['msg'=>'操作成功','code'=>'200']);
            }catch (\Exception $e){
                Db::rollback();
                return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
            }
        }else{
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }



    }
    public function statusYes(){
        $uid = getDecodeToken()['id'];
        if(!Xuser::where(['id'=>$uid,'status'=>'0'])->find()){
            return returnData(['msg'=>'该用户已被紧用','code'=>'201']);
        }
        $id = input('post.id/d','','strip_tags');
        if(!PfzAccount::where(['id'=>$id,'status'=>'1'])->find()){
            return returnData(['msg'=>'没有需要审核的分账接收方','code'=>'201']);
        }
        $status = input('post.status/d','','strip_tags');
        $data['name'] = input('post.name/s','','strip_tags');
        $data['account'] = input('post.account/s','','strip_tags');
        $data['type'] = input('post.type/s','','strip_tags');
        $data['relation_type'] = input('post.relation_type/s','','strip_tags');

        $mch_id = input('post.mch_id/s','','strip_tags');
        $sub_mch_id = input('post.sub_mch_id/s','','strip_tags');

        if (empty($id) || empty($mch_id) || empty($sub_mch_id) || empty($status) || empty($data['name']) || empty($data['account']) || empty($data['type']) || empty($data['relation_type'])){
            return returnData(['msg'=>'参数错误','code'=>'201']);
        }

        $pid = PfzAccount::where(['id'=>$id,'state'=>'2','uid'=>$uid])->value('pid');
        if(Padmin::where(['id'=>$pid,'mch_id'=>$mch_id,'sub_mch_id'=>$sub_mch_id])->find()){
            $accountData = Accounts::where(['mch_id'=>$mch_id,'state'=>'1'])->find();
        }else{
            return returnData(['msg'=>'平台商收款账号不存在','code'=>'201']);
        }


        $data['appid'] = getVariable('pay_id');
        $pay_key = getVariable('pay_key');

        $mch_id = empty($accountData['mch_id'])?$mch_id:$accountData['mch_id'];
        $mch_key = $accountData['key'];
        $appid = $accountData['appid'];
        $sercet = $accountData['sercet'];
        $apiclient_cert = $accountData['apiclient_cert'];
        $apiclient_key = $accountData['apiclient_key'];
        $data['extend'] = json_encode([
            'mch_id'=>$mch_id,
            'sub_mch_id'=>$sub_mch_id,
            'key'=>$mch_key,
            'appid'=>$appid,
            'sercet'=>$sercet,
            'apiclient_cert'=>$apiclient_cert,
            'apiclient_key'=>$apiclient_key,
        ]);

        if ($status == 2){
            Db::startTrans();
            try {
                $apiUrl = empty(getVariable('api_url'))?'https://apibei.payunke.com':getVariable('api_url');
                $url = $apiUrl.'/index/travelFzAccountAdd';

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

                unset($data['appid']);
                unset($data['sign']);
                unset($data['extend']);
                $data['status'] = $status;
                $data['update_time'] = time();

                if($result['code'] != '200'){
                    $data['desc'] = $result['msg'];
                    PfzAccount::where(['id'=>$id,'state'=>'2','uid'=>$uid])->update($data);
                    Db::commit();
                    addXuserLog(getDecodeToken(),'分账接收方审核不通过：失败原因：'.$result['msg']);
                    return returnData(['msg'=>$result['msg'],'code'=>'201']);
                }else{
                    PfzAccount::where(['id'=>$id,'state'=>'2','uid'=>$uid])->update($data);
                    Db::commit();
                    addXuserLog(getDecodeToken(),'分账接收方审核通过：'.$id);
                    return returnData(['msg'=>'操作成功','code'=>'200']);
                }

            }catch (\Exception $e){
                Db::rollback();
                return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
            }
        }else{
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }



    }

    /**
     * @author liujiong
     * @Note  分账接收方列表
     */
    public function list(){
        $uid = getDecodeToken()['id'];
        $num = input('post.num/d','10','strip_tags');
        $where = [];
        $where['state'] = '2';
        $where['uid'] = $uid;

        $status = input('post.status');
        if ($status){
            $where['a.status'] = $status;
        }
        $uname = input('post.uname/s','','strip_tags');
        if ($uname){
            $where['b.user_name'] = $uname;
        }
        $name = input('post.name/s','','strip_tags');
        if ($name){
            $where['a.name'] = $name;
        }
        $phone = input('post.phone/s','','strip_tags');
        if ($phone){
            $where['b.phone'] = $phone;
        }
        $sub_mch_id = input('post.sub_mch_id/s','','strip_tags');
        if ($sub_mch_id){
            $where['a.sub_mch_id'] = $sub_mch_id;
        }
        $result = new PfzAccount();
        $data = $result->alias('a')
            ->where($where)
            ->join('p_admin b','b.id=a.pid','LEFT')
            ->field('a.id,a.status,a.mch_id,a.sub_mch_id,a.create_time,a.name,a.account,a.type,a.relation_type,b.user_name,b.phone,a.desc')
            ->paginate($num)
            ->toArray();

        return returnData(['data'=>$data,'code'=>'200']);

    }


}