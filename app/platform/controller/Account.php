<?php
declare (strict_types = 1);

namespace app\platform\controller;

use think\facade\Db;
use think\Request;
use think\exception\ValidateException;
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
     * @Apidoc\Param("weach", type="number",require=false, desc="微信账号" )
     * @Apidoc\Param("qq", type="number",require=false, desc="QQ" )
     * @Apidoc\Param("avatar", type="number",require=false, desc="用户头像url" )
     * @Apidoc\Param("nickname", type="number",require=false, desc="姓名" )
     * @Apidoc\Param("position", type="number",require=false, desc="职位" )
     * @Apidoc\Param("address", type="number",require=false, desc="所在地" )
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
     * @Apidoc\Param("weach", type="number",require=false, desc="微信账号" )
     * @Apidoc\Param("qq", type="number",require=false, desc="QQ" )
     * @Apidoc\Param("avatar", type="number",require=false, desc="用户头像url" )
     * @Apidoc\Param("nickname", type="number",require=false, desc="姓名" )
     * @Apidoc\Param("position", type="number",require=false, desc="职位" )
     * @Apidoc\Param("address", type="number",require=false, desc="所在地" )
     * @Apidoc\Param("phone", type="number",require=false, desc="手机号" )
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function enterprisesave(Request $request){
        $uid = $request->uid;
        $type = $request->type;
        $data = $request->post();
        Db::startTrans();
        try {
            $admin = p_enterprise::where('id',$uid)->field('content,title,code,representative,phone,email,address')->find();
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

}
