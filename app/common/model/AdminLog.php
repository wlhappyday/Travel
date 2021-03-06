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
class AdminLog extends Model
{
    //
    protected $name = 'admin_log';

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
}
