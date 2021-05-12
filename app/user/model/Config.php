<?php
declare (strict_types = 1);

namespace app\user\model;

use think\Model;
/**
 * @mixin \think\Model
 */
class Config extends Model
{
    //
    protected $name = 'p_user_config';
    protected $json = ['value'];
}
