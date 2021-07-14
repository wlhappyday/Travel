<?php
declare (strict_types = 1);

namespace app\common\model;

use app\platform\model\Productuser;
use think\Model;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\WithoutField;
use hg\apidoc\annotation\AddField;
use think\model\concern\SoftDelete;

/**
 * @mixin \think\Model
 */
class OrderCharge extends Model
{
    //
    protected $name='order_charge';
    use SoftDelete;
    protected $autoWriteTimestamp = true;
    protected $deleteTime = 'delete_time';

}
