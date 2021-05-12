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
class Productuser extends Model
{
    //
    protected $name = 'p_productuser';
    protected $json = ['img_id'];

    public function Product(){
        return $this->hasOne(J_product::class, 'id','product_id');
    }
}
