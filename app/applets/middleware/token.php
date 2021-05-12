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
        dd('123132');
        return $next($request);
    }
}
