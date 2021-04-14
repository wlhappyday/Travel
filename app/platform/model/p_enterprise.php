<?php
declare (strict_types = 1);

namespace app\platform\model;

use think\Model;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\WithoutField;
use hg\apidoc\annotation\AddField;
/**
 * @mixin \think\Model
 */
class p_enterprise extends Model
{
    protected $name = 'p_enterprise';
    protected $autoWriteTimestamp = true;

    /**
     * @field("content,title,code,representative,phone,email,address,qualifications,special_qualifications")
     */
    public function field($id){
        $res = $this->get($id);
        return $res;
    }
}
