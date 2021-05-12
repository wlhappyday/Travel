<?php
declare (strict_types = 1);

namespace app\platform\controller;

use app\api\model\Padmin;
use app\platform\model\Adminlogin;
use think\facade\Db;
use think\Request;
use think\facade\validate;
use app\platform\model\Admin;
use app\common\model\File;
use app\platform\model\P_enterprise;
use app\platform\model\Balancerecords;
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
        $admin = Admin::field('phone,nickname,avatar,position,weach,QQ,address,user_name')->where('status',0)->find($uid);
        $admin['img_id'] =$admin['avatar'];
        $admin['avatar'] = http().File::where('id',$admin['avatar'])->value('file_path');
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
            $admin = Admin::where('id',$uid)->field('weach,QQ,avatar,nickname,phone,position,address')->find();
            $admin->weach = $data['weach'];
            $admin->QQ = $data['QQ'];
            $admin->avatar = $data['avatar'];
            $admin->nickname = $data['nickname'];
            $admin->phone = $data['phone'];
            $admin->position = $data['position'];
            $admin->address = $data['address'];
            $admin->save();
            $data['info'] = '修改个人信息';
            $login = new Adminlogin();
            $login->log($data);
            addPadminLog(getDecodeToken(),'修改用户信息：'.$uid);
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
        $enterprise = P_enterprise::where(['uid'=>$uid])->find();
        return json(['code'=>'200','msg'=>'操作成功','p_enterprise'=>$enterprise]);
    }

    /**
     * @Apidoc\Title("企业信息保存")
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
        $rule = [
            'title'=>'require|length:5,50',
            'content'=>'require|length:60,255',
            'code'=>'require|max:50',
            'representative'=>'require|max:100',
            'phone'=>'require|mobile',
            'email'=>'require|email',
            'qualifications'=>'require|length:10,255',
            'special_qualifications'=>'require|length:10,255',
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
            'qualifications.length'=>'企业资质必须10-255个字符',
            'email.require'=>'邮箱不能为空',
            'email.email'=>'邮箱格式不正确',
            'special_qualifications.require'=>'特殊资质不能为空',
            'special_qualifications.length'=>'特殊资质必须10-255个字符',
            'address.require'=>'地址不能为空',
            'address.length'=>'地址必须10-255个字符',
        ];
        $validate = Validate::rule($rule)->message($msg);
        if (!$validate->check($request->post())) {
            return json(['code'=>'201','msg'=>'操作成功','sign'=>$validate->getError()]);
        }
        try {
            $admin = P_enterprise::where('uid',$uid)->field('content,phone,title,code,representative,email,address,qualifications')->find();

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
            addPadminLog(getDecodeToken(),'修改企业信息：'.$uid);
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
            $admin = Admin::field('passwd,passwd_salt')->find($uid);
            $pwd = checkPasswd($password,$admin);
            if(!$pwd){
                return json(['code'=>'201','msg'=>'操作成功','sign'=>'旧密码不正确']);
            }
            $admin->save();
            addPadminLog(getDecodeToken(),'修改密码：'.$uid);
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
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
        $Balancerecords = Balancerecords::paginate(20);
        return json(['code'=>'200','msg'=>'操作成功',['Balancerecords'=>$Balancerecords]]);
    }
}
