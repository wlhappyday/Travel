<?php
declare (strict_types = 1);

namespace app\platform\validate;

use think\Validate;

class User extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'username' => 'require|unique:p_user',
        'password' => 'require|length:6,16|confirm',
    ];


    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'username.require'=>'账号不能为空',
        'username.unique'=>'账号已存在',
        'newpassword.requireWith' => '确认密码不能为空',
        'newpassword.confirm'     => '两个新密码不一致',
    ];

    /**
     * create 验证场景 编辑用户信息
     */
    public function scenecreate()
    {
        return $this
            ->remove('username', 'unique:p_user')
            ->remove('password', 'require|confirm')
            ->append('newpassword', 'requireWith:password|confirm:password');
    }

}
