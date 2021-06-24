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
class Puserhomenavigation extends Model
{
    //
    protected $name='p_user_homenavigation';

    /**
     * @field("id,type,title,img,page_id")
     */
    public function navigation($id){
        $res = $this->get($id);
        return $res;
    }
}
