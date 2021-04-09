<?php
declare (strict_types = 1);

namespace app\platform\controller;
use hg\apidoc\annotation as Apidoc;
use think\Request;
use think\Validate;
use app\platform\model\p_user;
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
        $user = p_user::where(['status'=>'0','uid'=>$uid])->paginate(10);
        return json(['code'=>'200','admin'=>$user]);
    }

    /**
     * @Apidoc\Title("用户创建")
     * @Apidoc\Desc("平台商管理添加用户端用户")
     * @Apidoc\Url("platform/user/create")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("username", type="number",require=true, desc="账号" )
     * @Apidoc\Param("password", type="number",require=true, desc="密码 长度为6-16" )
     * @Apidoc\Param("newpassword", type="number",require=true, desc="确定密码" )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("msg",type="string",desc="任务提示")
     */
    public function create(Request $request)
    {
        $data = $request->get();
        $user= new p_user();

        $flag  = $user->add($data);
        if($flag != 1){
            return json(['code'=>'201','msg'=>$flag]);
        }
        return json(['code'=>'200','msg'=>'注册成功']);
    }

    /**
     * @Apidoc\Title("用户修改")
     * @Apidoc\Desc("平台商管理修改用户端用户")
     * @Apidoc\Url("platform/user/save")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("id", type="number",require=true, desc="操作用户的id" )
     * @Apidoc\Param("username", type="number",require=true, desc="账号" )
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     * @Apidoc\Returned("msg",type="string",desc="任务提示")
     */
    public function save(Request $request)
    {
        $data = $request->get();
        $user= new p_user();
        $flag  = $user->edit($data);
        if($flag != 1){
            return json(['code'=>'201','msg'=>$flag]);
        }
        return json(['code'=>'200','msg'=>'修改成功']);
    }

}
