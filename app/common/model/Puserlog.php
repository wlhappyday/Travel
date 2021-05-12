<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class Puserlog extends Model
{
    protected $name = 'p_user_log';
    protected $autoWriteTimestamp = true;

    public function log($data){
        $data['ip'] = $_SERVER['REMOTE_ADDR'];
        $data['url'] = $_SERVER['REQUEST_URI'];
        $data['user_id'] = getDecodeToken()['id'];
        $data['username'] = getDecodeToken()['userName'];
        return $this->create($data);
    }
}
