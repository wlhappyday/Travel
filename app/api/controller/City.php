<?php

namespace app\api\controller;

use app\common\model\Area;
use think\exception\ValidateException;

class City
{
    public function list(){
        $data = Area::where(['type'=>2])->field('code,name')->order('code asc')->select()->toArray();

        foreach ($data as $k=>$v){
            $arr = Area::where(['parent_code'=>$v['code']])->field('code,name')->order('code asc')->select()->toArray();
            foreach ($arr as $key=>$val){
                $arr[$key]['data'] = Area::where(['parent_code'=>$val['code']])->field('code,name')->order('code asc')->select()->toArray();
            }
            $data[$k]['data'] = $arr;
        }
echo json_encode($data,320);
        return returnData(['data'=>$data,'code'=>'200']);
    }

    /**
     * @author liujiong
     * @Note  获取城市名称
     */
    public function getcity(){
        $code = input('post.code/d');
        if(empty($code)){
            return returnData(['msg'=>'城市编码不存在','code'=>'201']);
        }
        $data = Area::where(['code'=>$code,'type'=>'4'])->field('name,parent_code')->find();
        if(!$data){
            return returnData(['msg'=>'编码错误，请传入县级编码','code'=>'201']);
        }
        $city_data = Area::where(['code'=>$data['parent_code']])->field('name,parent_code')->find();
        $province_data = Area::where(['code'=>$city_data['parent_code']])->field('name,parent_code')->find();

        $name = $province_data['name'].'-'.$city_data['name'].'-'.$data['name'];
        return returnData(['name'=>$name,'code'=>'200']);
    }
}