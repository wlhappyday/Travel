<?php
declare (strict_types = 1);

namespace app\applets\middleware;

class token
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
        $request->appid = getDecodeToken()['appid'];
        if (array_key_exists('puser_id',getDecodeToken())){
            $request->puser_id = getDecodeToken()['puser_id'];
            return $next($request);
        }else{
            if ($request['s']=='/applets/index/index'){
                return $next($request);
            }
            if ($request['s']=='/applets/index/search'){
                return $next($request);
            }
            return json(['code'=>'201','msg'=>'请先登录']);
        }

    }
}
