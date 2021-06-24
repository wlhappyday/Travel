<?php
declare (strict_types = 1);

namespace app\applets\middleware;

use app\common\model\Puseruser;
use thans\jwt\facade\JWTAuth;

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
        $response = $next($request);
        if ($request['s']=='/applets/index/index'){
            return $response;
        }
        if ($request['s'] == '/applets/index/tabBar'){
            return $response;
        }
        if ($request['s'] == '/applets/product/detail'){
            return $response;
        }
        $user = Puseruser::where(['appid'=>getDecodeToken()['appid'],'openid'=>getDecodeToken()['openid']])->find();
        if ($user){
            if ($user['type']!='1'){
                return json(['code'=>'-1','msg'=>'当前用户已被禁用']);
            }
        }else{
            return json(['code'=>'-1','msg'=>'当前用户不存在']);
        }
        return $response;
    }
}
