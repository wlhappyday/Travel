<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\WithoutField;
use hg\apidoc\annotation\AddField;
/**
 * @mixin \think\Model
 */
class Orderdetails extends Model
{
    //
    use SoftDelete;
    protected $name = 'order_details';

    /**
     * @field("name,id_card,order_id,delete_time,admission_ticket_type,phone,type")
     */
    public function order_detail($id){
        $res = $this->get($id);
        return $res;
    }
}
