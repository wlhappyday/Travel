<?php
declare (strict_types = 1);

namespace app\platform\controller;
use app\platform\model\Adminlogin;
use hg\apidoc\annotation as Apidoc;
use think\Request;
use app\platform\model\Config;
use \think\facade\Validate;
use think\facade\Db;
use app\platform\model\P_user;
/**
 *
 * @Apidoc\Title("用户接口")
 * @Apidoc\Group("user")
 */
class User
{

    /**
     * @Apidoc\Title("用户列表")
     * @Apidoc\Desc("平台商管理自己的用户端用户")
     * @Apidoc\Url("platform/user/list")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned ("admin",type="object",desc="平台商列表",
     *     @Apidoc\Returned ("total",type="number",desc="分页总数"),
     *     @Apidoc\Returned ("per_page",type="int",desc="首页"),
     *     @Apidoc\Returned ("last_page",type="int",desc="最后一页"),
     *     @Apidoc\Returned ("current_page",type="int",desc="当前页"),
     *     @Apidoc\Returned ("data",type="object",desc="平台商列表",ref="app\platform\model\p_user\info",
     *    )
     *  )
     */
    public function list(Request $request)
    {
        $uid =$request->uid;//平台商用户id
        $pagenum = $request->get('pagenum');
        $user_name = $request->get('user_name');
        $phone = $request->get('phone');
        $start_time = $request->get('start_time');
        $end_time = $request->get('end_time');
        $user = P_user::where(['status'=>'0','uid'=>$uid])->where([['user_name','like','%'.$user_name.'%'],['phone','like','%'.$phone.'%']]);
        if ($start_time){
            $user->whereTime('create_time', '>=', strtotime($start_time));
        }
        if ($end_time){
            $user->whereTime('create_time', '<=', strtotime($end_time));
        }
        $admin = $user->order('id','Desc')->paginate($pagenum)->toArray();
        return json(['code'=>'200','admin'=>$admin]);
    }

    /**
     * @Apidoc\Title("用户创建")
     * @Apidoc\Desc("平台商管理添加用户端用户")
     * @Apidoc\Url("platform/user/create")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("user_name", type="char(15)",require=true, desc="账号" )
     * @Apidoc\Param("newpassword", type="char(32)",require=true, desc="密码 长度为6-16" )
     * @Apidoc\Param("newpassword_confirm", type="number",require=true, desc="确定密码" )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("msg",type="string",desc="任务提示")
     */
    public function create(Request $request)
    {
        $uid = $request->uid;
        $data = $request->post();
        $rule = [
            'user_name'=>'require|unique:p_user|length:4,15',
            'newpassword'=>'require|length:6,15|confirm',
            'rate'=>'require|number'
        ];
        $msg = [
            'user_name.require'=>'账号必填',
            'user_name.unique'=>'账号已存在',
            'user_name.length'=>'账号必须4-15位之内',
            'newpassword.require' => '新密码必填',
            'newpassword.length' => '密码必须6-15位之内',
            'newpassword.confirm' => '两次密码不一致',
            'rate.require' => '费率必填',
            'rate.number' => '费率必须为数字',
        ];
        $validate = Validate::rule($rule)->message($msg);
        if (!$validate->check($request->post())) {
            return json(['code'=>'201','msg'=>'操作成功','sign'=>$validate->getError()]);
        }
        Db::startTrans();
        try {
            $pwd = encryptionPasswd($data['newpassword']);
            $data['uid'] = $uid;
            $user= new P_user();
            $user->user_name=$data['user_name'];
            $user->passwd=$pwd['passwd'];
            $user->passwd_salt=$pwd['passwd_salt'];
            $user->uid=$uid;
            $user->avatar=6;
            $user->rate=$request->post('rate');
            $user->save();
            addPadminLog(getDecodeToken(),'创建用户端用户：'.$user['id']);
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功','user'=>$user]);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'操作成功','sign'=>$e->getMessage()]);
        }
        return json(['code'=>'-1','msg'=>'网络延迟']);
    }
    /**
     * @Apidoc\Title("用户删除")
     * @Apidoc\Desc("平台商管理删除用户端用户")
     * @Apidoc\Url("platform/user/delete")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Param("id", type="int(11)",require=true, desc="操作的用户" )
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("msg",type="string",desc="任务提示")
     */
    public function delete(Request $request)
    {
        $uid = $request->uid;
        $id = $request->post('id');
        Db::startTrans();
        try {
            $user = P_user::where(['id'=>$id,'uid'=>$uid])->find();
            if (!$user){
                return json(['code'=>'200','msg'=>'操作成功','sign'=>'抱歉，没有这条数据']);
            }
            $user->delete();
            addPadminLog(getDecodeToken(),'删除用户端用户：'.$id);
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功','user'=>$user]);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'操作成功','sign'=>$e->getMessage()]);
        }
        return json(['code'=>'-1','msg'=>'网络延迟']);
    }

    /**
     * @Apidoc\Title("用户开启禁用")
     * @Apidoc\Desc("平台商管理用户开启禁用")
     * @Apidoc\Url("platform/user/status")
     * @Apidoc\Method("POST")
     * @Apidoc\Param("status", type="int(11)",require=true, desc="状态 9为禁用 0开启" )
     * @Apidoc\Param("id", type="int(11)",require=true, desc="操作的用户" )
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("msg",type="string",desc="任务提示")
     */
    public function status(Request $request)
    {
        $uid = $request->uid;
        $id = $request->post('id');
        $status = $request->post('status');
        Db::startTrans();
        try {
            $user = P_user::where(['id'=>$id,'uid'=>$uid])->find();
            if (!$user){
                return json(['code'=>'200','msg'=>'操作成功','sign'=>'抱歉，没有这条数据']);
            }
            $user->status = $status;
            $user->save();
            addPadminLog(getDecodeToken(),'禁用用户端用户：'.$id);
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功','user'=>$user]);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'操作成功','sign'=>$e->getMessage()]);
        }
        return json(['code'=>'-1','msg'=>'网络延迟']);
    }

    public function userrate(Request $request){
        $uid = $request->uid;
        $id = $request->post('id');
        $rate = $request->post('rate');

        if ($rate && $id){
            Db::startTrans();
            try {
                $user = P_user::where(['uid'=>$uid,'id'=>$id])->find();
                $user->rate = $rate;
                $user->save();
                addPadminLog(getDecodeToken(),'修改用户费率：'.$id);
                Db::commit();
                return json(['code'=>'200','msg'=>'操作成功','user'=>$user]);
            }catch (\Exception $e){
                Db::rollback();
                return json(['code'=>'201','msg'=>'操作成功','sign'=>$e->getMessage()]);
            }
        }
        return json(['code'=>'201','msg'=>'操作成功','sign'=>'参数不能为空']);
    }

}
