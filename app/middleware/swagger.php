<?php
declare (strict_types = 1);

namespace app\middleware;

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
        if(!isUserToken(getDecodeToken(),2)){
            return json(['msg'=>'操作成功','code'=>'202','sign'=>'Token错误或用户不存在或已被禁用']);
        }
        $request->uid = getDecodeToken()['id'];
        $request->type = getDecodeToken()['type'];
        return $next($request);
    }
}
