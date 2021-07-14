<?php
declare (strict_types = 1);

namespace app\platform\model;

use think\Model;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\WithoutField;
use hg\apidoc\annotation\AddField;
/**
 * @mixin \think\Model
 */
class Balancerecords extends Model
{
    //
    protected $name = 'p_admin_balance_records';
    protected $autoWriteTimestamp = true;

    /**
     * @field("id,type,scene,uid,before_money,money,after_money,data_id,descript,create_time")
     */
    public function doc($id){
        $res = $this->get($id);
        return $res;
    }

}
