<?php
declare (strict_types = 1);

namespace app\user\controller;

use app\api\model\Puser;
use app\common\model\Puserlog;
use app\common\model\Puserenterprise;
use app\common\model\Puserbalancerecords;
use think\facade\Db;
use think\facade\validate;
use think\Request;
use app\platform\model\P_user;
use app\common\model\File;
use hg\apidoc\annotation as Apidoc;
class Account
{
    /**
     * @Apidoc\Title("个人信息")
     * @Apidoc\Desc("查看用户端自己的信息")
     * @Apidoc\Url("user/account/personal")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned("p_enterprise",type="object",desc="景区",ref="app\platform\model\P_user\personal")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function personal(Request $request)
    {
        $id = $request->id;
        try{
            $admin = P_user::field('phone,nickname,avatar,position,weach,QQ,address,user_name')->find($id);
            $admin['avatar'] = http().File::where('id',$admin['avatar'])->value('file_path');
            if ($admin){
                return json(['code'=>'200','msg'=>'操作成功','admin'=>$admin]);
            }else{
                return json(['code'=>'201','msg'=>'操作成功','sign'=>'没有该用户信息']);
            }
        }catch (\Exception $e){
            return json(['code'=>'201','msg'=>'操作成功','sign'=>$e->getMessage()]);
        }

    }
    /**
     * @Apidoc\Title("个人信息保存")
     * @Apidoc\Desc("用户端保存自己的信息")
     * @Apidoc\Url("user/account/personal_save")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Param("weach", type="string",require=false, desc="微信账号" )
     * @Apidoc\Param("QQ", type="number",require=false, desc="QQ" )
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
            $admin = P_user::where('id',$uid)->field('weach,QQ,avatar,nickname,phone,position,address')->find();
            $admin->weach = $data['weach'];
            $admin->QQ = $data['QQ'];
            $admin->avatar = $data['avatar'];
            $admin->nickname = $data['nickname'];
            $admin->phone = $data['phone'];
            $admin->position = $data['position'];
            $admin->address = $data['address'];
            $admin->save();

            $res['info'] = '修改个人信息';
            $login = new Puserlog();
            $login->log($res);
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功','admin'=>$admin]);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'操作成功','sign'=>$e->getMessage()]);
        }
    }

    /**
     * @Apidoc\Title("企业信息")
     * @Apidoc\Desc("查看用户端绑定的企业信息")
     * @Apidoc\Url("user/account/enterprise")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned("p_enterprise",type="object",desc="景区",ref="app\common\model\Puserenterprise\zhu")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function enterprise(Request $request){
        $id = $request->id;
        try{
            $enterprise = Puserenterprise::where(['user_id'=>$id])->find();
            if (!$enterprise['qualifications']){
                $enterprise['qualifications'] = File::where(['id'=>$enterprise['qualifications']])->value('file_path');
            }
            if (!$enterprise['special_qualifications']){
                $enterprise['special_qualifications'] = File::where(['id'=>$enterprise['special_qualifications']])->value('file_path');
            }
            return json(['code'=>'200','msg'=>'操作成功','p_enterprise'=>$enterprise]);
        }catch (\Exception $e){
            return json(['code'=>'201','msg'=>'操作成功','sign'=>$e->getMessage()]);
        }
    }


    /**
     * @Apidoc\Title("企业信息保存")
     * @Apidoc\Desc("保存自己的信息")
     * @Apidoc\Url("user/account/enterprise_save")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Param(ref="app\platform\model\p_enterprise\field" )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function enterprisesave(Request $request){
        $id = $request->id;
        $data = $request->post();
        Db::startTrans();
        $rule = [
            'title'=>'require|length:5,50',
            'content'=>'require|length:60,255',
            'code'=>'require|max:50',
            'representative'=>'require|max:100',
            'phone'=>'require|mobile',
            'email'=>'require|email',
            'qualifications'=>'require|number',
            'special_qualifications'=>'require|number',
            'address'=>'require|length:10,255',
        ];
        $msg = [
            'title.require'=>'企业全称不能为空',
            'title.length'=>'企业全称必须5-50个字符',
            'content.require'=>'企业简称不能为空',
            'content.length'=>'企业简称必须60-255个字符',
            'code.require'=>'企业全称不能为空',
            'code.length'=>'企业全称最大字符为50',
            'representative.require'=>'企业负责人不能为空',
            'representative.max'=>'企业负责人最大字符为50',
            'phone.require'=>'手机号不能为空',
            'phone.mobile'=>'手机号格式不正确',
            'qualifications.require'=>'企业资质不能为空',
            'qualifications.number'=>'企业资质必须为图片id',
            'email.require'=>'邮箱不能为空',
            'email.email'=>'邮箱格式不正确',
            'special_qualifications.require'=>'特殊资质不能为空',
            'special_qualifications.number'=>'企业资质必须为图片id',
            'address.require'=>'地址不能为空',
            'address.length'=>'地址必须10-255个字符',
        ];
        $validate = Validate::rule($rule)->message($msg);
        if (!$validate->check($request->post())) {
            return json(['code'=>'201','msg'=>'操作成功','sign'=>$validate->getError()]);
        }
        try {
            $admin = Puserenterprise::where('user_id',$id)->field('content,phone,title,code,representative,email,address,qualifications')->find();

            if (empty($admin)){
                $admin = new P_enterprise();
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
            $res['info'] = '修改企业信息';
            $login = new Puserlog();
            $login->log($res);
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功','admin'=>$admin]);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'操作成功','sign'=>$e->getMessage()]);
        }
        return json(['code'=>'-1','msg'=>'网络繁忙']);
    }
    /**
     * @Apidoc\Title("账户明细")
     * @Apidoc\Desc("账户明细")
     * @Apidoc\Url("platform/account/Balancerecords")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Returned("Balancerecords",type="object",desc="景区",ref="app\platform\model\Balancerecords\doc")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function Balancerecords(Request $request){
        $uid = $request->uid;
        $Balancerecords = Puserbalancerecords::paginate(20);
        return json(['code'=>'200','msg'=>'操作成功',['Balancerecords'=>$Balancerecords]]);
    }
}
