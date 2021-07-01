<?php
declare (strict_types = 1);

namespace app\common\model;

use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\WithoutField;
use hg\apidoc\annotation\AddField;
use think\Model;

/**
 * @mixin \think\Model
 */
class Pusermy extends Model
{
    //
    protected $name='p_user_my';
    /**
     * @field("name,img,type,address,page")
     */
    public function log($id){
        $res = $this->get($id);
        return $res;
    }
}
