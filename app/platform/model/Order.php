<?php
declare (strict_types = 1);

namespace app\platform\model;

use think\Model;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\WithoutField;
use hg\apidoc\annotation\AddField;
use think\model\concern\SoftDelete;

/**
 * @mixin \think\Model
 */
class Order extends Model
{
    //
    use SoftDelete;
    protected $name = 'p_order';
    protected $autoWriteTimestamp = true;
}
