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
class Pcarousel extends Model
{
    protected $name = 'p_user_carousel';
    //

    /**
     * @field("carousel_id,type,img,page")
     */
    public function carousel($id){
        $res = $this->get($id);
        return $res;
    }
}
