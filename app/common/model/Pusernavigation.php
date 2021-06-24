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
class Pusernavigation extends Model
{
    //
    protected $name = 'p_user_navigation';
    protected $autoWriteTimestamp = true;

    /**
     * @field("navigation_id,img,imgs,page_id,title")
     */
    public function navigation($id){
        $res = $this->get($id);
        return $res;
    }
}
