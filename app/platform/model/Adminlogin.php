<?php
declare (strict_types = 1);

namespace app\platform\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class Adminlogin extends Model
{
    protected $name = 'p_admin_log';
    protected $autoWriteTimestamp = true;

    public function log($data){
        $data['ip'] = $_SERVER['REMOTE_ADDR'];
        $data['url'] = $_SERVER['REQUEST_URI'];
        $data['uid'] = getDecodeToken()['id'];
        $data['username'] = getDecodeToken()['userName'];
        return $this->create($data);
    }
}
