<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\WithoutField;
use hg\apidoc\annotation\AddField;
/**
 * @mixin \think\Model
 */
class Puseruser extends Model
{
    //
    use SoftDelete;
    protected $name='p_user_user';
    protected $autoWriteTimestamp = true;

    /**
     * @field("openid,nickname,phone,avatar,sex,money,address,create_time,last_time")
     */
    public function log($id){
        $res = $this->get($id);
        return $res;
    }

    /**
     * @field("id,avatar,nickname,name,phone,is_distcenter,offline_count,distcenter_time,distcenters_time")
     */
    public function zhu($id){
        $res = $this->get($id);
        return $res;
    }
}
