<?php
declare (strict_types = 1);

namespace app\middleware;
use app\platform\model\P_user;
class swagger
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        if ($request['s'] == '/user/order/download'){
            return $next($request);
        }
        $request->type = getDecodeToken()['type'];
        //如果登录账户为用户端进入判断主要是区分平台商id和用户端id
        //uid为平台商
        //id为用户端
        if(getDecodeToken()['type'] == '5'){
            $admin = P_user::where('id',getDecodeToken()['id'])->value('uid');
            $request->uid = $admin;
            $request->id = getDecodeToken()['id'];
        }else{
            $request->uid = getDecodeToken()['id'];
        }
        return $next($request);
    }
}
