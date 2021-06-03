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
        $data['mp_name'] = input('post.mp_name/s','','strip_tags');
        $data['mp_id'] = JproductClass::where(['name'=>$data['mp_name'],'status'=>'0','type'=>'2'])->value('id');
        if(!$data['mp_id']){
            return returnData(['msg'=>'景区门票类型名称不存在','code'=>'201']);
        }
        $cp_type = input('post.cp_type');
        if ($cp_type){
            $cp_type = json_decode($cp_type,true);
            $data['cp_type_str'] = json_encode($cp_type,320);
            $cp_type_id = JproductClass::where(['status'=>'0','type'=>'3'])->whereIn('name',$cp_type)->column('id');

            $data['cp_type'] = json_encode($cp_type_id);

        }
        $yp_type = input('post.yp_type');
        if (empty($yp_type)){
            return returnData(['msg'=>'景区验票类型不能为空','code'=>'201']);
        }else{
            $yp_type = json_decode($yp_type,true);
            $data['yp_type_str'] = json_encode($yp_type,320);

            $yp_type_id = JproductClass::where(['status'=>'0','type'=>'4'])->whereIn('name',$yp_type)->column('id');

            $data['yp_type'] = json_encode($yp_type_id);
        }
        $data['product_code'] = input('post.product_code/s','','strip_tags');
        $data['title'] = input('post.title/s','','strip_tags');
        $data['standard'] = input('post.standard/s','','strip_tags');
        $data['money'] = input('post.money/f','','strip_tags');
        $data['number'] = input('post.number/d','','strip_tags');
        $data['end_time'] = strtotime(input('post.end_time/s','','strip_tags'));
        $data['first_id'] = input('post.first_id/s','','strip_tags');
        $data['video_id'] = input('post.video_id/s','','strip_tags');
        $data['desc'] = input('post.desc/s','','strip_tags');
        $data['state'] = input('post.state/d','','strip_tags');

        $address = input('post.address');
        $address = json_decode($address,true);
        $data['get_province'] = $address['0'];
        $data['get_city'] = $address['1'];
        $data['address'] = json_encode($address,320);
        $data['class_name'] = $data['get_province'].'-'.$data['get_city'].'-'.$data['name'].'-'.$data['mp_name'];

        if(Jproduct::where(['class_name'=>$data['class_name'],'name'=>$data['name'],'type'=>'1'])->value('id')){
            return returnData(['msg'=>'该类产品已存在','code'=>'201']);
        }

        $rule = [
            'name' => 'require',
            'mp_name' => 'require',
            'title' => 'require',
            'money' => 'require',
            'number' => 'require',
            'state' => 'require|in:1,2',
            'address' => 'require',
        ];
        $msg = [
            'name.require' => '景区名称不能为空',
            'mp_name.require' => '景区门票类型名称能为空',
            'title.require' => '景区产品标题不能为空',
            'money.require' => '景区门票价格不能为空',
            'number.require' => '景区门票数量不能为空',
            'state.require' => '请选择是否可以改变单价',
            'state.in' => '状态取值范围是1或2',
            'address.require' => '景区地址必须存在',
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
                    'product_id' => $id,
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
        $where['a.uid'] = $uid;
        $where['a.type'] = '1';
        $num = input('post.num/d','10','strip_tags');
        $status = input('post.status');

        if ($status == '0' || $status == '9'){
            $where['a.status'] = $status;
        }
        $name = input('post.name/s','','strip_tags');
        if ($name){
            $where['a.name'] = $name;
        }
        $mp_name = input('post.mp_name/s','','strip_tags');
        if ($mp_name){
            $where['a.mp_name'] = $mp_name;
        }
        $address = input('post.address');
        if ($address){
            $address = json_decode($address,true);
            $where['a.get_province'] = $address['0'];
            $where['a.get_city'] = $address['1'];
        }

        $data = Jproduct::where($where)->alias('a')->join('file b','b.id = a.first_id','LEFT')->field('a.id,a.type,a.name,a.class_name,a.mp_name,a.cp_type_str cp_type,a.yp_type_str yp_type,a.title,a.money,a.number,a.end_time,a.desc,a.status,a.get_province,a.get_city,b.file_path first_id')->paginate($num);
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
        if(!Jproduct::where(['uid'=>$uid,'status'=>'9','type'=>'1','id'=>$id])->value('id')){
            return returnData(['msg'=>'该产品暂未下架，无法操作！','code'=>'201']);
        }
        $data['money'] = input('post.money/f','','strip_tags');
        if (empty($data['money'])){
            return returnData(['msg'=>'景区门票价格不能为空','code'=>'201']);
        }
        $data['end_time'] = strtotime(input('post.end_time/s','','strip_tags'));
        if($data['end_time'] < time()){
            return returnData(['msg'=>'景区门票有效时间不合法','code'=>'201']);
        }

        $type = input('post.type/d','','strip_tags');
        $number = input('post.number/d','','strip_tags');
        if ($number && $type){
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
        }

        $data['update_time'] = time();

        Db::startTrans();
        try {
            if ($number && $type){
                $produst_data =
                    [
                        'product_id' => $id,
                        'uid' => $uid,
                        'type' => $type,
                        'before_number' => $before_number,
                        'number' => $number,
                        'after_number' => $after_number,
                        'descript' => '管理员('.getDecodeToken()['phone'].')'.$info.'数量: ' . $number,
                        'create_time' => time(),
                    ];
                JproductRecords::insert($produst_data);

                $data['number'] = $after_number;
                addJuserLog(getDecodeToken(),'修改产品库存：'.$number.' 产品id '.$id);
            }

            Jproduct::where(['uid'=>$uid,'status'=>'9','type'=>'1','id'=>$id])->update($data);
            addJuserLog(getDecodeToken(),'修改产品：'.$id.' 产品单价：'.$data['money']);
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
        $status = input('post.status');
        if (empty($id)){
            return returnData(['msg'=>'产品id不能为空','code'=>'201']);
        }
        if(!Jproduct::where(['uid'=>$uid,'type'=>'1','id'=>$id])->value('id')){
            return returnData(['msg'=>'该产品不存在或没有权限','code'=>'201']);
        }
        if ($status == '0' || $status == '9'){
            Db::startTrans();
            try {
                Jproduct::where(['uid'=>$uid,'type'=>'1','id'=>$id])->update(['status'=>$status,'update_time'=>time()]);
                addJuserLog(getDecodeToken(),'上下架产品：'.$id);
                Db::commit();
                return returnData(['msg'=>'操作成功','code'=>'200']);
            }catch (\Exception $e){
                Db::rollback();
                return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
            }

        }
        return returnData(['msg'=>'产品状态不符合规则','code'=>'201']);

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
            Jproduct::where(['uid'=>$uid,'status'=>'9','type'=>'1','id'=>$id])->update(['delete_time'=>time()]);
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
                    'uid' => $uid,
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

    public function productRecord(){
        $uid = getDecodeToken()['id'];
        $num = input('post.num/d','10','strip_tags');

        $type = input('post.type/d','','strip_tags');
        $where = [];
        $where['b.uid'] = $uid;
        $where['b.type'] = '1';
        if($type){
            $where['a.type'] = $type;
        }
        $name = input('post.name/s','','strip_tags');
        if ($name){
            $where['b.name'] = $name;
        }

        $balance_result = new JproductRecords();
        $data = $balance_result->alias('a')->where($where)
            ->join('j_product b','b.id=a.product_id','LEFT')
            ->field('a.id,a.type,a.before_number,a.number,a.after_number,a.descript,a.create_time,b.name,b.class_name')
            ->paginate($num)->toArray();
        return returnData(['data'=>$data,'code'=>'200']);
    }
}