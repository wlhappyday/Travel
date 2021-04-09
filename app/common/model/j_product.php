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
class j_product extends Model
{
    use SoftDelete;
    protected $name = 'j_product';
    protected $autoWriteTimestamp = true;
    protected $deleteTime = 'delete_time';
}
