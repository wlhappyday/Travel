<?php
declare (strict_types = 1);

namespace app\platform\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class product_relation extends Model
{
    protected $name = 'p_product_relation';
    protected $autoWriteTimestamp = true;
    //
}
