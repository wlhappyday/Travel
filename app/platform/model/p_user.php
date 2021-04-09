<?php
declare (strict_types = 1);

namespace app\platform\model;
use think\facade\Validate;
use think\Model;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\WithoutField;
use hg\apidoc\annotation\AddField;
use think\model\concern\SoftDelete;
use think\facade\Db;
/**
 * @mixin \think\Model
 */
class p_user extends Model
{
    //
    use SoftDelete;
    protected $name = 'p_user';
    protected $autoWriteTimestamp = true;

    public function add($data){
        try {
            validate('app\platform\validate\User')
                ->scene('create')
                ->check($data);
            self::save([
                'username'=>$data['username'],
                'password'=>md5($data['password']),
                'uid'=>$data['uid']
            ]);
        }catch (\Exception $e){
            return $e->getMessage();
        }
        return 1;
    }

    public function edit($data){
        $validate = Validate::rule([
            'username'  => 'require|length:4,25|unique:p_user',
        ]);
        $validate->message([
            'username.require'  => '账号不能为空',
            'username.length' => '账号长度为4-25个字符之间',
            'username.unique'=>'该字段已存在'
        ]);
        if (!$validate->check($data)) {
            return $validate->getError();
        }
        Db::startTrans();
        try{
            self::where(['id'=>$data['id']])->save([
                'username'=>$data['username'],
            ]);
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            return $e->getMessage();
        }
        return 1;
    }

    /**
     * @field("username,last_time,create_time,last_ip,status")
     */
    public function info($id){
        $res = $this->get($id);
        return $res;
    }
}
