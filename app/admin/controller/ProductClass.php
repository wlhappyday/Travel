<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\model\XproductClass;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\facade\Validate;
use think\Request;
use app\common\model\JproductClass;

class ProductClass
{

    public function list(){
        $state = input('post.state/d','','strip_tags');

        if($state == 1){
            $data = JproductClass::where(['status'=>'0'])->field('id,type,name')->select();
        }elseif ($state == 2){
            $data = XproductClass::where(['status'=>'0'])->field('id,type,name')->select();
        }

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
    }

    /**
     * @author liujiong
     * @Note  添加分类
     */
    public function add(){
        $state= input('post.state/d','1');
        $type = input('post.type/d','1');
        $name = input('post.name/s','','strip_tags');

        if (!in_array($type,[1,2,3,4])){
            return returnData(['msg'=>'分类类型不符合规则','code'=>'201']);
        }
        if (empty($name)){
            return returnData(['msg'=>'分类名称不能为空','code'=>'201']);
        }
        if($state == 1){
            $result = new JproductClass();
            $info = '添加景区分类：'.$name;
        }elseif ($state == 2){
            $result = new XproductClass();
            $info = '添加线路分类：'.$name;
        }
        $data = $result->where(['type'=>$type,'name'=>$name,'status'=>'0'])->find();
        if(!empty($data)){
            return returnData(['msg'=>'该分类已存在','code'=>'201']);
        }


        Db::startTrans();
        try {
            $result->insert([
                'type' =>  $type,
                'name' =>  $name,
                'create_time' =>  time(),
            ]);
            addAdminLog(getDecodeToken(),$info);
            Db::commit();
            return returnData(['msg'=>'操作成功','code'=>'200']);
        }catch (\Exception $e){
            Db::rollback();
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }
    }
    /**
     * @author liujiong
     * @Note  删除分类
     */
    public function del(){
        $state= input('post.state/d','1');
        $id = input('post.id/d');

        if($state == 1){
            $result = new JproductClass();
            $info = '删除景区分类：'.$id;
        }elseif ($state == 2){
            $result = new XproductClass();
            $info = '删除线路分类：'.$id;
        }
        $data = $result->where(['id'=>$id,'status'=>'0'])->find();
        if(!empty($data)){
            return returnData(['msg'=>'数据错误','code'=>'201']);
        }

        Db::startTrans();
        try {
            $result->where(['id'=>$id])->update([
                'status' =>  '9',
                'create_time' =>  time(),
            ]);
            addAdminLog(getDecodeToken(),$info);
            Db::commit();
            return returnData(['msg'=>'操作成功','code'=>'200']);
        }catch (\Exception $e){
            Db::rollback();
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }
    }
}