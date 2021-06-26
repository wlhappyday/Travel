<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\WithoutField;
use hg\apidoc\annotation\AddField;
use think\model\concern\SoftDelete;
/**
 * @mixin \think\Model
 */
class PadminLog extends Model
{
    //
    protected $name = 'p_admin_log';
    public function addData($data,$info){
        $arr = getIp();
        return $this->insert([
            'uid'       => $data['id'],
            'uname'  => $data['userName'],
            'info'      => $info,
            'ip'        => $arr['ip'],
            'address'   => $arr['address'],
            'create_time'=> time(),
        ]);
    }
//
    /**
     * @field("user_name,info,ip,address,create_time")
     */
    public function log($id){
        $res = $this->get($id);
        return $res;
    }
}
