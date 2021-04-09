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
class j_product_class extends Model
{
    //
    protected $name = 'j_product_class';
    protected $autoWriteTimestamp = true;
}