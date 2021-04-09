<?php
declare (strict_types = 1);

namespace app\platform\controller;

use think\Request;
use app\platform\model\admin;
use app\platform\model\p_enterprise;
use hg\apidoc\annotation as Apidoc;
/**
 *
 * @Apidoc\Title("账户中心")
 * @Apidoc\Group("user")
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
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function personalsave(Request $request){
        
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


}
