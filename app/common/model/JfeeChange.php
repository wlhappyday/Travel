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
}
