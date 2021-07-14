<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class Paccount extends Model
{
    protected $name = 'p_accounts';
    use SoftDelete;
    protected $autoWriteTimestamp = true;

}
