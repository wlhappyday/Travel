<?php
declare (strict_types = 1);

namespace app\platform\model;

use think\Model;
use think\model\concern\SoftDelete;
/**
 * @mixin \think\Model
 */
class product_relation extends Model
{
    use SoftDelete;
    protected $name = 'p_product_relation';
    protected $autoWriteTimestamp = true;
    //
}
