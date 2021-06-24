<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class Accounts extends Model
{
    protected $name = 'accounts';
    use SoftDelete;
    protected $autoWriteTimestamp = true;

}
