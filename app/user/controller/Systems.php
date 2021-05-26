<?php
declare (strict_types = 1);

namespace app\user\controller;

use app\platform\model\Product_relation;
use think\facade\Db;
use think\Request;
use app\user\model\Config;
use app\common\model\File;
use hg\apidoc\annotation as Apidoc;
/**
 *
 * @Apidoc\Title("系统配置")
 * @Apidoc\Group("product")
 */
class Systems
{

    /**
     * @Apidoc\Title("产品应用列表")
     * @Apidoc\Desc("用户获取平台商绑定的产品")
     * @Apidoc\Url("user/systems/config")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("type", type="number",require=true, desc="1为小程序配置")
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function config(Request $request){
        $id = $request->id;
        $type = $request->get('type');
        $config = Config::where(['mid'=>$id,'type'=>$type])->where('title','<>','Carousel_img')->field('title,value')->select()->toArray();
        $carousel_img = Config::where(['mid'=>$id,'type'=>$type,'title'=>'Carousel_img'])->find()['value'];
        foreach ($carousel_img as $key => $value){
            $carousel_img->$key = http().File::where('id',$value)->value('file_path');
        }
        return json(['code'=>'200','msg'=>'操作成功','carousel_img'=>$carousel_img,'config'=>$config]);
    }

    public function config_do(Request $request){
        $id = $request->id;
        $type = $request->type;
        $list = [
            ['title'=>'thinkphp','value'=>'thinkphp@qq.com','mid'=>'1'],
            ['title'=>'onethink','value'=>[1,2],'mid'=>'1']
        ];
        $data = $request->post('data');
        Db::startTrans();
        try {
            Config::where(['mid'=>$id,'type'=>$type])->delete();
            $config = new Config;
            $config->saveAll($data);
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'网络繁忙']);
        }
    }
}
