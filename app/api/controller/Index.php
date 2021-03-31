<?php
declare (strict_types = 1);

namespace app\api\controller;
use think\Request;
class Index
{
    public function login(Request $request){
        $token = $request->buildToken('__token__', 'sha1');
    }
}
