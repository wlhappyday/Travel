<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;
/**
 * @mixin \think\Model
 */
class Puseruser extends Model
{
    //
    use SoftDelete;
    protected $name='p_user_user';
    protected $autoWriteTimestamp = true;
}
