<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\WithoutField;
use hg\apidoc\annotation\AddField;
/**
 * @mixin \think\Model
 */
class Puserenterprise extends Model
{
    //
    protected $name = 'p_user_enterprise';

    /**
     * @field("id,title,content,code,representative,phone,email,qualifications,special_qualifications,address")
     */
    public function zhu($id){
        $res = $this->get($id);
        return $res;
    }
}
