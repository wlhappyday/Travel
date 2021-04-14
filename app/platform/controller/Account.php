<?php
declare (strict_types = 1);

namespace app\platform\controller;

use app\api\model\Padmin;
use think\facade\Db;
use think\Request;
use think\facade\validate;
use app\platform\model\admin;
use app\platform\model\p_enterprise;
use hg\apidoc\annotation as Apidoc;
/**
 *
 * @Apidoc\Title("账户中心")
 * @Apidoc\Group("account")
 */
class Account
{
    /**
     * @Apidoc\Title("个人信息")
     * @Apidoc\Desc("查看自己的信息")
     * @Apidoc\Url("platform/account/personal")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function personal(Request $request)
    {
        $uid = $request->uid;
        $admin = admin::field('phone,nickname,avatar,position,weach,QQ,address,user_name')->where('status',0)->find($uid);

        if ($admin){
            return json(['code'=>'200','msg'=>'操作成功','admin'=>$admin]);
        }else{
            return json(['code'=>'202','msg'=>'操作成功','sign'=>'没有该用户信息']);
        }
    }

    /**
     * @Apidoc\Title("个人信息保存")
     * @Apidoc\Desc("保存自己的信息")
     * @Apidoc\Url("platform/account/personal_save")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Param("weach", type="string",require=false, desc="微信账号" )
     * @Apidoc\Param("qq", type="number",require=false, desc="QQ" )
     * @Apidoc\Param("avatar", type="string",require=false, desc="用户头像url" )
     * @Apidoc\Param("nickname", type="string",require=false, desc="姓名" )
     * @Apidoc\Param("position", type="string",require=false, desc="职位" )
     * @Apidoc\Param("address", type="string",require=false, desc="所在地" )
     * @Apidoc\Param("phone", type="number",require=false, desc="手机号" )
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function personalsave(Request $request){
        $uid = $request->uid;
        $type = $request->type;
        $data = $request->post();
        Db::startTrans();
        try {
            $admin = admin::where('id',$uid)->field('weach,QQ,avatar,nickname,phone,position,address')->find();
            $admin->weach = $data['weach'];
            $admin->QQ = $data['qq'];
            $admin->avatar = $data['avatar'];
            $admin->nickname = $data['nickname'];
            $admin->phone = $data['phone'];
            $admin->position = $data['position'];
            $admin->address = $data['address'];
            $admin->save();
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功','admin'=>$admin]);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'操作成功','sign'=>$e->getMessage()]);
        }
        return json(['code'=>'-1','msg'=>'网络繁忙']);
    }
    /**
     * @Apidoc\Title("企业信息")
     * @Apidoc\Desc("查看平台商绑定的企业信息")
     * @Apidoc\Url("platform/account/enterprise")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function enterprise(Request $request){
        $uid = $request->uid;
        $enterprise = p_enterprise::where(['uid'=>$uid])->find();
        return json(['code'=>'200','msg'=>'操作成功','p_enterprise'=>$enterprise]);
    }

    /**
     * @Apidoc\Title("个人信息保存")
     * @Apidoc\Desc("保存自己的信息")
     * @Apidoc\Url("platform/account/enterprise_save")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Param(ref="app\platform\model\p_enterprise\field" )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function enterprisesave(Request $request){
        $uid = $request->uid;
        $type = $request->type;
        $data = $request->post();
        Db::startTrans();
        try {
            $admin = p_enterprise::where('uid',$uid)->field('content,phone,title,code,representative,email,address,qualifications')->find();
            if (empty($admin)){
                $admin = new p_enterprise();
                $admin->uid = $uid;
            }
            $admin->title = $data['title'];
            $admin->content = $data['content'];
            $admin->code = $data['code'];
            $admin->representative = $data['representative'];
            $admin->phone = $data['phone'];
            $admin->email = $data['email'];
            $admin->qualifications = $data['qualifications'];
            $admin->special_qualifications = $data['special_qualifications'];
            $admin->address = $data['address'];
            $admin->save();
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功','admin'=>$admin]);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'操作成功','sign'=>$e->getMessage()]);
        }
        return json(['code'=>'-1','msg'=>'网络繁忙']);
    }

    /**
     * @Apidoc\Title("修改密码")
     * @Apidoc\Desc("修改密码")
     * @Apidoc\Url("platform/account/password")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Param("password", type="string",require=true, desc=" 旧密码" )
     * @Apidoc\Param("newpassword", type="string",require=true, desc="新密码" )
     * @Apidoc\Param("newpassword_confirm", type="string",require=true, desc="确认密码" )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */

    public function password(Request $request){
        $uid = $request->uid;
        $newpassword = $request->post('newpassword');
        $password = $request->post('password');
        $rule = [
            'password'=>'require',
            'newpassword'=>'require|min:6|confirm'
        ];
        $msg = [
            'password.require'=>'旧密码必填',
            'newpassword.require' => '新密码必填',
            'newpassword.min' => '密码必须6位以上',
            'newpassword.confirm' => '两次密码不一致',//confirm自动相互验证
        ];
        $validate = Validate::rule($rule)->message($msg);
        if (!$validate->check($request->post())) {
            return json(['code'=>'201','msg'=>'操作成功','sign'=>$validate->getError()]);
        }


        Db::startTrans();
        try {
            $admin = admin::field('passwd,passwd_salt')->find($uid);
            $pwd = checkPasswd($password,$admin);
            if(!$pwd){
                return json(['code'=>'201','msg'=>'操作成功','sign'=>'旧密码不正确']);
            }
            $admin->save();
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'操作成功','sign'=>$e->getMessage()]);
        }
        return json(['code'=>'-1','msg'=>'网络繁忙']);
    }
    public function login(Request $request){
        $username = $request->post('username');
        $password = $request->post('passwd');
        $where['user_name'] = '13193568362';
        $where['passwd'] = '85d5e3a4be2061dfad4d71bea1ae7705';
        $Padmin = Padmin::where($where)->find();

    }

}
