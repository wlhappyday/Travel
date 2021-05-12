<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class Puserbalancerecords extends Model
{
    //
    protected $name = 'p_user_balance_records';
    protected $autoWriteTimestamp = true;
}
