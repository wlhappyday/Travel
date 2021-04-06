<?php
declare (strict_types = 1);

namespace app\platform\middleware;

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
//        $openapi = \OpenApi\scan('../app/platform/controller');
//        header('Content-Type: application/json');
//        file_put_contents('./v2/swagger.json',$openapi->toJson());
        return $next($request);
    //
    }
}
