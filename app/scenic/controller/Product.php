<?php
declare (strict_types = 1);

namespace app\scenic\controller;

use app\common\model\Jproduct;
use app\common\model\JproductRecords;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\facade\Validate;
use think\Request;
use app\common\model\JproductClass;

class Product
{

    /**
     * @author liujiong
     * @Note  添加产品
     */
    public function add(){
        $data['uid'] = getDecodeToken()['id'];
        $data['type'] = '1';

        $data['name'] = input('post.name/s','','strip_tags');
//        $data['jq_name'] = input('post.jq_name/s','','strip_tags');
//        if(!JproductClass::where(['name'=>$data['jq_name'],'status'=>'0','type'=>'1'])->value('id')){
//            return returnData(['msg'=>'景区产品分类名称不存在','code'=>'201']);
//        }
        $data['mp_name'] = input('post.mp_name/s','','strip_tags');
        $data['mp_id'] = JproductClass::where(['name'=>$data['mp_name'],'status'=>'0','type'=>'2'])->value('id');
        if(!$data['mp_id']){
            return returnData(['msg'=>'景区门票类型名称不存在','code'=>'201']);
        }
        $cp_type = input('post.cp_type/s','','strip_tags');
        if (empty($cp_type)){
            return returnData(['msg'=>'景区出票信息不能为空','code'=>'201']);
        }else{
            $data['cp_type_str'] = $cp_type;
            $cp_type = explode(',',$cp_type);
            $cp_type_id = JproductClass::where(['status'=>'0','type'=>'3'])->whereIn('name',$cp_type)->column('id');

            $data['cp_type'] = json_encode($cp_type_id);
        }
        $yp_type = input('post.yp_type/s','','strip_tags');
        if (empty($yp_type)){
            return returnData(['msg'=>'景区验票类型不能为空','code'=>'201']);
        }else{
            $data['yp_type_str'] = $yp_type;
            $yp_type = explode(',',$yp_type);
            $yp_type_id = JproductClass::where(['status'=>'0','type'=>'4'])->whereIn('name',$yp_type)->column('id');

            $data['yp_type'] = json_encode($yp_type_id);
        }
        $data['product_code'] = input('post.product_code/s','','strip_tags');
        $data['title'] = input('post.title/s','','strip_tags');
        $data['standard'] = input('post.standard/s','','strip_tags');
        $data['address'] = input('post.address/s','','strip_tags');
        $data['money'] = input('post.money/f','','strip_tags');
        $data['number'] = input('post.number/d','','strip_tags');
//        $data['not_time'] = input('post.not_time/s','','strip_tags');
        $data['end_time'] = input('post.end_time/d','','strip_tags');
        $data['first_id'] = input('post.first_id/s','','strip_tags');
        $data['img_id'] = input('post.img_id/s','','strip_tags');
        $data['video_id'] = input('post.video_id/s','','strip_tags');
        $data['material'] = input('post.material/s','','strip_tags');
        $data['desc'] = input('post.desc/s','','strip_tags');
        $data['class_name'] = $data['name'].'-'.$data['mp_name'];

        if(!Jproduct::where(['jq_name'=>$data['jq_name'],'name'=>$data['name'],'type'=>'1'])->value('id')){
            return returnData(['msg'=>'该类产品已存在','code'=>'201']);
        }

        $rule = [
            'name' => 'require',
//            'jq_name' => 'require',
            'mp_name' => 'require',
            'title' => 'require',
            'money' => 'require',
            'number' => 'require',
        ];
        $msg = [
            'name.require' => '景区名称不能为空',
//            'jq_name.require' => '景区产品名称不能为空',
            'mp_name.require' => '景区门票类型名称能为空',
            'title.require' => '景区产品标题不能为空',
            'money.require' => '景区门票价格不能为空',
            'number.require' => '景区门票数量不能为空',
//            'name.unique' => '产品名称已存在',
        ];

        $validate = Validate::rule($rule)->message($msg);
        if (!$validate->check($data)) {
            return json(['code'=>'201','msg'=>$validate->getError()]);
        }

        if($data['end_time'] < time()){
            return returnData(['msg'=>'景区门票有效时间不合法','code'=>'201']);
        }
        $data['create_time'] = time();
        Db::startTrans();
        try {
            $id = Jproduct::insertGetId($data);

            $produst_data =
                [
                    'produst_id' => $id,
                    'before_number' => '0',
                    'number' => $data['number'],
                    'after_number' => $data['number'],
                    'descript' => '管理员('.getDecodeToken()['phone'].')增加数量: ' . $data['number'],
                    'create_time' => time(),
                ];

            JproductRecords::insert($produst_data);

            addJuserLog(getDecodeToken(),'添加产品：'.$data['name']);
            Db::commit();
            return returnData(['msg'=>'操作成功','code'=>'200']);
        }catch (\Exception $e){
            Db::rollback();
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }
    }

    public function list(){
        $uid = getDecodeToken()['id'];
        $where = [];
        $where['uid'] = $uid;
        $where['type'] = '1';
        $name = input('post.name/s','','strip_tags');
        if ($name){
            $where['name'] = $name;
        }
        $jq_name = input('post.jq_name/s','','strip_tags');
        if ($jq_name){
            $where['jq_name'] = $jq_name;
        }
        $mp_name = input('post.mp_name/s','','strip_tags');
        if ($mp_name){
            $where['mp_name'] = $mp_name;
        }
        $data = Jproduct::where($where)->field('id,type,name,jq_name,mp_name,cp_type_str cp_type,yp_type_str yp_type,title,money,number,end_time,desc,img_id,video_id')->select();
//        p($data);
        return returnData(['data'=>$data,'code'=>'200']);
    }

    /**
     * @author liujiong
     * @Note  修改产品
     */
    public function update(){
        $uid = getDecodeToken()['id'];
        $id = input('post.id/d','','strip_tags');
        if (empty($id)){
            return returnData(['msg'=>'产品id不能为空','code'=>'201']);
        }
        if(!Jproduct::where(['uid'=>$uid,'status'=>'0','type'=>'1','id'=>$id])->value('id')){
            return returnData(['msg'=>'该产品不存在或没有权限','code'=>'201']);
        }
        $data['money'] = input('post.money/f','','strip_tags');
        if (empty($data['money'])){
            return returnData(['msg'=>'景区门票价格不能为空','code'=>'201']);
        }
//        $data['number'] = input('post.number/d','','strip_tags');
//        if (empty($data['number'])){
//            return returnData(['msg'=>'景区门票数量不能为空','code'=>'201']);
//        }
        $data['end_time'] = input('post.end_time/d','','strip_tags');
        if($data['end_time'] < time()){
            return returnData(['msg'=>'景区门票有效时间不合法','code'=>'201']);
        }
        $data['update_time'] = time();

        Db::startTrans();
        try {
            Jproduct::where(['uid'=>$uid,'status'=>'0','type'=>'1','id'=>$id])->update($data);
            addJuserLog(getDecodeToken(),'修改产品：'.$id);
            Db::commit();
            return returnData(['msg'=>'操作成功','code'=>'200']);
        }catch (\Exception $e){
            Db::rollback();
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }

    }

    /**
     * @author liujiong
     * @Note  下架产品
     */
    public function update_status(){
        $uid = getDecodeToken()['id'];
        $id = input('post.id/d','','strip_tags');
        if (empty($id)){
            return returnData(['msg'=>'产品id不能为空','code'=>'201']);
        }
        if(!Jproduct::where(['uid'=>$uid,'status'=>'0','type'=>'1','id'=>$id])->value('id')){
            return returnData(['msg'=>'该产品不存在或没有权限','code'=>'201']);
        }

        Db::startTrans();
        try {
            Jproduct::where(['uid'=>$uid,'type'=>'1','id'=>$id])->update(['status'=>'9','update_time'=>time()]);
            addJuserLog(getDecodeToken(),'删除产品：'.$id);
            Db::commit();
            return returnData(['msg'=>'操作成功','code'=>'200']);
        }catch (\Exception $e){
            Db::rollback();
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }

    }

    /**
     * @author liujiong
     * @Note  删除产品
     */
    public function delete(){
        $uid = getDecodeToken()['id'];
        $id = input('post.id/d','','strip_tags');
        if (empty($id)){
            return returnData(['msg'=>'产品id不能为空','code'=>'201']);
        }
        if(!Jproduct::where(['uid'=>$uid,'status'=>'9','type'=>'1','id'=>$id])->value('id')){
            return returnData(['msg'=>'该产品暂未下架，无法操作！','code'=>'201']);
        }

        Db::startTrans();
        try {
            Jproduct::where(['uid'=>$uid,'status'=>'0','type'=>'1','id'=>$id])->update(['delete_time'=>time()]);
            addJuserLog(getDecodeToken(),'删除产品：'.$id);
            Db::commit();
            return returnData(['msg'=>'操作成功','code'=>'200']);
        }catch (\Exception $e){
            Db::rollback();
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }

    }

    /**
     * @author liujiong
     * @Note  增加减少库存
     */
    public function operationNumber(){
        $uid = getDecodeToken()['id'];
        $id = input('post.id/d','','strip_tags');
        $type = input('post.type/d','','strip_tags');
        $number = input('post.number/d','','strip_tags');
        if (empty($id) || empty($type) || empty($number)){
            return returnData(['msg'=>'产品id不能为空','code'=>'201']);
        }

        if(!Jproduct::where(['uid'=>$uid,'status'=>'9','type'=>'1','id'=>$id])->value('id')){
            return returnData(['msg'=>'该产品暂未下架，无法操作！','code'=>'201']);
        }

        $before_number = Jproduct::where(['uid'=>$uid,'status'=>'9','type'=>'1','id'=>$id])->value('number');

        if($type == '1'){
           $after_number = $before_number + $number;
           $info = '增加';
        }elseif ($type == '2'){
           $after_number = $before_number - $number;
           $info = '减少';
        }
        if($after_number < 0){
            return returnData(['msg'=>'库存不能小于零！','code'=>'201']);
        }

        Db::startTrans();
        try {
            $produst_data =
                [
                    'produst_id' => $id,
                    'type' => $type,
                    'before_number' => $before_number,
                    'number' => $number,
                    'after_number' => $after_number,
                    'descript' => '管理员('.getDecodeToken()['phone'].')'.$info.'数量: ' . $number,
                    'create_time' => time(),
                ];

            JproductRecords::insert($produst_data);

            Jproduct::where(['uid'=>$uid,'type'=>'1','id'=>$id])->update(['number'=>'$after_number','update_time'=>time()]);

            addJuserLog(getDecodeToken(),'修改产品库存：'.$number.' 产品id '.$id);
            Db::commit();
            return returnData(['msg'=>'操作成功','code'=>'200']);
        }catch (\Exception $e){
            Db::rollback();
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }

    }
}