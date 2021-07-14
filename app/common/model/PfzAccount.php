<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class PfzAccount extends Model
{
    protected $name = 'p_fz_account';
    use SoftDelete;
    protected $autoWriteTimestamp = true;

}
