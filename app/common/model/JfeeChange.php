<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\WithoutField;
use hg\apidoc\annotation\AddField;
use think\model\concern\SoftDelete;

/**
 * @mixin \think\Model
 */
class JfeeChange extends Model
{
    protected $name = 'j_fee_change';

    /**
     * @field("id,before_money,money,after_money,state,data_id,create_time")
     */
    public function fee($id){
        $res = $this->get($id);
        return $res;
    }

    public function addFee($orderNo){
        try{
            if(!$orderNo || empty($orderNo) || $orderNo=='0'){
                return json(['code'=>0,'msg'=>'orderNo->参数错误']);
            }
            $order =OrderCharge::where(['status'=>2,'order_no'=>$orderNo])->find();
            if(empty($order)){
                return json(['code'=>0,'msg'=>'orderInfo->找到不到订单信息']);
            }

            if($this->where(['data_id'=>$orderNo])->value('id')){
                return json(['code'=>0,'msg'=>'该订单已存在，无法再次修改信息费']);
            }
            $data['state'] = '1';
            $data['data_id'] = $orderNo;
            $data['create_time'] = time();
            if($order['type'] == '2'){
                $user = Padmin::where(['id'=>$order['user_id']])->field('id,user_name,amount')->find();
                $money = bcadd($user['amount'],$order['money'],4);

                $data['type'] = '3';
                $data['uid'] = $order['user_id'];

                Padmin::where(['id'=>$order['user_id']])->update(['amount'=>$money]);
                $user['userName'] = $user['user_name'];
                addPadminLog($user,'用户充值信息费：'.$order['money'].'元');
            }elseif ($order['type'] == '3'){
                $user = Juser::where(['id'=>$order['user_id']])->field('id,user_name,amount')->find();
                $money = bcadd($user['amount'],$order['money'],4);

                $data['type'] = '1';
                $data['uid'] = $order['user_id'];

                Juser::where(['id'=>$order['user_id']])->update(['amount'=>$money]);
                $user['userName'] = $user['user_name'];
                addJuserLog($user,'用户充值信息费：'.$order['money'].'元');
            }elseif ($order['type'] == '4'){
                $user = Xuser::where(['id'=>$order['user_id']])->field('id,user_name,amount')->find();
                $money = bcadd($user['amount'],$order['money'],4);

                $data['type'] = '2';
                $data['uid'] = $order['user_id'];

                Xuser::where(['id'=>$order['user_id']])->update(['amount'=>$money]);
                $user['userName'] = $user['user_name'];
                addXuserLog($user,'用户充值信息费：'.$order['money'].'元');
            }
            $data['before_money'] = $user['amount'];
            $data['money'] = $order['money'];
            $data['after_money'] = $money;

            $return = $this->insertGetId($data);
            return $return;
        } catch (\Exception $e) {

            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }

    public function subFee($type,$uid){
        try{
            $data['state'] = '2';
            $data['data_id'] = $uid;
            $data['create_time'] = time();
            $sub_money = getVariable('ali_money');
            if($type == '2'){
                $user = Padmin::where(['id'=>$uid])->field('id,user_name,amount')->find();
                $money = bcsub($user['amount'],$sub_money,4);

                $data['type'] = '3';
                $data['uid'] = $uid;

                Padmin::where(['id'=>$uid])->update(['amount'=>$money]);
                $user['userName'] = $user['user_name'];
                addPadminLog($user,'用户扣除信息费：'.$sub_money.'元');
            }elseif ($type == '3'){
                $user = Juser::where(['id'=>$uid])->field('id,user_name,amount')->find();
                $money = bcsub($user['amount'],$sub_money,4);

                $data['type'] = '1';
                $data['uid'] = $uid;

                Juser::where(['id'=>$uid])->update(['amount'=>$money]);
                $user['userName'] = $user['user_name'];
                addJuserLog($user,'用户扣除信息费：'.$sub_money.'元');
            }elseif ($type == '4'){
                $user = Xuser::where(['id'=>$uid])->field('id,user_name,amount')->find();
                $money = bcsub($user['amount'],$sub_money,4);

                $data['type'] = '2';
                $data['uid'] = $uid;

                Xuser::where(['id'=>$uid])->update(['amount'=>$money]);
                $user['userName'] = $user['user_name'];
                addXuserLog($user,'用户扣除信息费：'.$sub_money.'元');
            }
            $data['before_money'] = $user['amount'];
            $data['money'] = $sub_money;
            $data['after_money'] = $money;

            $return = $this->insertGetId($data);
            return $return;
        } catch (\Exception $e) {

            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }

}
