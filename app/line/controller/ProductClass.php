<?php
declare (strict_types = 1);

namespace app\line\controller;

use app\common\model\XproductClass;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\Request;

class ProductClass
{
    /**
     * @author liujiong
     * @Note  添加分类
     */
    public function add(){
        $type = input('post.type/d','1');
        $name = input('post.name/s','','strip_tags');

        if (!in_array($type,[1,2,3,4])){
            return returnData(['msg'=>'分类类型不符合规则','code'=>'201']);
        }
        if (empty($name)){
            return returnData(['msg'=>'分类名称不能为空','code'=>'201']);
        }

        $data = XproductClass::where(['type'=>$type,'name'=>$name,'status'=>'0'])->find();
        if(!empty($data)){
            return returnData(['msg'=>'该分类已存在','code'=>'201']);
        }

        Db::startTrans();
        try {
            XproductClass::insert([
                'type' =>  $type,
                'name' =>  $name,
                'create_time' =>  time(),
            ]);
            addXuserLog(getDecodeToken(),'添加分类：'.$name);
            Db::commit();
            return returnData(['msg'=>'操作成功','code'=>'200']);
        }catch (\Exception $e){
            Db::rollback();
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }
    }

    /**
     * @author liujiong
     * @Note  分类列表
     */
    public function list(){

        $type = input('post.type/d','1');

        if (!in_array($type,[1,2,3,4,9])){
            return returnData(['msg'=>'分类类型不符合规则','code'=>'201']);
        }

        if($type == '9'){
            $data = XproductClass::where(['status'=>'0'])->field('id,type,name')->select();
            foreach ($data as $k=>$v){
                if($v['type'] == 1){
                    $result['type1'][] = $v['name'];
                }elseif ($v['type'] == 2){
                    $result['type2'][] = $v['name'];
                }elseif ($v['type'] == 3){
                    $result['type3'][] = $v['name'];
                }elseif ($v['type'] == 4){
                    $result['type4'][] = $v['name'];
                }
            }
            return returnData(['data'=>$result,'code'=>'200']);
        }else{
            $data = XproductClass::where(['status'=>'0','type'=>$type])->column('name');
            return returnData(['data'=>$data,'code'=>'200']);
        }
    }
}