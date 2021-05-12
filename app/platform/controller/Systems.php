<?php
declare (strict_types = 1);

namespace app\platform\controller;


use think\Request;
use app\platform\model\Config;
class Systems
{
    public function list(Request $request)
    {
        $uid = $request->uid;
        $type = $request->get('type');
        try{
            $config = Config::where(['uid'=>$uid,'type'=>$type])->field('title,value,type')->select()->toArray();
            return json(['code'=>'200','msg'=>'操作成功','config'=>$config]);
        }catch (\Exception $e){
            return json(['code'=>'201','msg'=>'操作成功','sign'=>$e->getMessage()]);
        }
    }


}
