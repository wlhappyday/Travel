<?php
declare (strict_types = 1);

namespace app\common\model;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\WithoutField;
use hg\apidoc\annotation\AddField;
use think\Model;

/**
 * @mixin \think\Model
 */
class Ptemplatemessage extends Model
{
    //
    protected $name = 'p_user_template_message';

    /**
     * @field("order_pay,order_cancel,order_delivery,order_refund,infosave,enroll_error,account_change,verify_result,withdrawal_success,withdrawal_error,distribution_examine")
     */
    public function templatemessage($id){
        $res = $this->get($id);
        return $res;
    }
}
