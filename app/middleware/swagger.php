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
        $request->uid = getDecodeToken()['id'];
        $request->type = getDecodeToken()['type'];
        return $next($request);
    }
}
