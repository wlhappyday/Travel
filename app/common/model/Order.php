<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\WithoutField;
use hg\apidoc\annotation\AddField;
/**
 * @mixin \think\Model
 */
class Order extends Model
{
    //
    protected $name='order';
    /**
     * @field("order_id,payment_order_id,order_status,coupon_price,order_amount,total_amount,add_time,pay_time,refund_price,surplus_price,is_checkout,store_type,goods_name,goods_num,goods_price,refund_num,refund_price")
     */
    public function order_list($id){
        $res = $this->get($id);
        return $res;
    }

}
