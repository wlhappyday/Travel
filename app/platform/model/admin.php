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
class admin extends Model
{
    //

    protected $name = 'p_admin';
    protected $autoWriteTimestamp = true;
    public function product()
    {
        return $this->belongsToMany(j_product::class, 'p_product_relation','product_id','uid');
    }

    /**
     * @field("id,phone,nickname,avatar")
     */
    public function info($id){
        $res = $this->get($id);
        return $res;
    }
}
