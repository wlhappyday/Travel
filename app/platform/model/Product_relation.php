<?php
declare (strict_types = 1);

namespace app\platform\model;

use think\Model;
/**
 * @mixin \think\Model
 */
class Product_relation extends Model
{
    protected $name = 'p_product_relation';
    protected $autoWriteTimestamp = true;
    //

    //关联产品
    public function Product(){
        return $this->hasone(J_product::class,'id','product_id');
    }
}
