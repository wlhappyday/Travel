<?php
declare (strict_types = 1);

namespace app\line\controller;

use app\common\model\Jproduct;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\facade\Validate;
use think\Request;
use app\common\model\XproductClass;


class Product
{

    /**
     * @author liujiong
     * @Note  添加产品
     */
    public function add(){
        $data['uid'] = getDecodeToken()['id'];
        $data['type'] = '2';
//        $data = input('post.');
        $data['name'] = input('post.name/s','','strip_tags');
//        $data['yw_name'] = input('post.yw_name/s','','strip_tags');
        $data['cx_name'] = input('post.cx_name/s','','strip_tags');
        $data['jt_qname'] = input('post.jt_qname/s','','strip_tags');
        $data['jt_fname'] = input('post.jt_fname/s','','strip_tags');
        $data['xl_name'] = input('post.xl_name/s','','strip_tags');
        $data['set_city'] = input('post.set_city/d','','strip_tags');
        $data['get_city'] = input('post.get_city/d','','strip_tags');
        $data['product_code'] = input('post.product_code/s','','strip_tags');
        $data['title'] = input('post.title/s','','strip_tags');
        $data['standard'] = input('post.standard/s','','strip_tags');
        $data['address'] = input('post.address/s','','strip_tags');
        $data['money'] = input('post.money/f','','strip_tags');

        $data['day'] = input('post.day/d','','strip_tags');
        $data['end_day'] = input('post.end_day/d');
        $data['end_time'] = strtotime(input('post.end_time/s','','strip_tags'));
//        $data['not_time'] = input('post.not_time/s','','strip_tags');
        $data['first_id'] = input('post.first_id/s','','strip_tags');
        $data['img_id'] = input('post.img_id/s','','strip_tags');
        $data['video_id'] = input('post.video_id/s','','strip_tags');
        $data['material'] = input('post.material/s','','strip_tags');
        $data['desc'] = input('post.desc/s','','strip_tags');

        $data['class_name'] = $data['cx_name'].'-'.$data['jt_qname'].'-'.$data['jt_fname'].'-'.$data['xl_name'];

        $rule = [
            'name' => 'require|unique:j_product',
//            'yw_name' => 'require',
            'cx_name' => 'require',
            'jt_qname' => 'require',
            'jt_fname' => 'require',
            'xl_name' => 'require',
            'set_city' => 'require|different:get_city',
            'get_city' => 'require|different:set_city',
            'day' => 'require',
            'end_day' => 'require',
            'title' => 'require',
            'money' => 'require',
        ];
        $msg = [
            'name.require' => '产品名称不能为空',
//            'yw_name.require' => '业务分类不能为空',
//            'yw_name.unique' => '业务分类名称不存在11',
            'cx_name.require' => '出行方式不能为空',
            'jt_qname.require' => '交通方式(去程)不能为空',
            'jt_fname.require' => '交通方式(反程)不能为空',
            'xl_name.require' => '线路产品分类不能为空',
            'set_city.require' => '出发城市不能为空',
            'set_city.different' => '出发城市与目的城市一致',
            'get_city.require' => '目的城市不能为空',
            'day.require' => '行程天数不能为空',
            'end_day.require' => '发团前X天报名截止不能为空',
            'title.require' => '产品标题不能为空',
            'money.require' => '团期价格不能为空',
            'name.unique' => '产品名称已存在',
        ];

        $validate = Validate::rule($rule)->message($msg);
        if (!$validate->check($data)) {
            return json(['code'=>'201','msg'=>$validate->getError()]);
        }
//        if(!XproductClass::where(['name'=>$data['yw_name'],'status'=>'0','type'=>'2'])->value('id')){
//            return returnData(['msg'=>'业务分类名称不存在','code'=>'201']);
//        }
        if(!XproductClass::where(['name'=>$data['cx_name'],'status'=>'0','type'=>'4'])->value('id')){
            return returnData(['msg'=>'出行方式名称不存在','code'=>'201']);
        }
        if(!XproductClass::where(['name'=>$data['jt_qname'],'status'=>'0','type'=>'3'])->value('id')){
            return returnData(['msg'=>'交通方式(去程)名称不存在','code'=>'201']);
        }
        if(!XproductClass::where(['name'=>$data['jt_fname'],'status'=>'0','type'=>'3'])->value('id')){
            return returnData(['msg'=>'交通方式(反程)名称不存在','code'=>'201']);
        }
        if(!XproductClass::where(['name'=>$data['xl_name'],'status'=>'0','type'=>'1'])->value('id')){
            return returnData(['msg'=>'线路产品分类名称不存在','code'=>'201']);
        }
        $data['create_time'] = time();

        Db::startTrans();
        try {
            Jproduct::insert($data);
            addXuserLog(getDecodeToken(),'添加线路：'.$data['name']);
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
        $where['type'] = '2';
        $num = input('post.num/d','10','strip_tags');
        $name = input('post.name/s','','strip_tags');
        if ($name){
            $where['name'] = $name;
        }
//        $yw_name = input('post.yw_name/s','','strip_tags');
//        if ($yw_name){
//            $where['yw_name'] = $yw_name;
//        }
        $cx_name = input('post.cx_name/s','','strip_tags');
        if ($cx_name){
            $where['cx_name'] = $cx_name;
        }
        $jt_qname = input('post.jt_qname/s','','strip_tags');
        if ($jt_qname){
            $where['jt_qname'] = $jt_qname;
        }
        $jt_fname = input('post.jt_fname/s','','strip_tags');
        if ($jt_fname){
            $where['jt_fname'] = $jt_fname;
        }
        $xl_name = input('post.xl_name/s','','strip_tags');
        if ($xl_name){
            $where['xl_name'] = $xl_name;
        }
        $status = input('post.status');
        if ($status == '0' || $status == '9'){
            $where['status'] = $status;
        }
        $data = Jproduct::where($where)->field('id,type,name,yw_name,cx_name,jt_qname,jt_fname,xl_name,title,money,set_city,get_city,day,end_time,end_day,product_code,address,desc,status,img_id,video_id')->paginate($num);
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
        if(!Jproduct::where(['uid'=>$uid,'status'=>'9','type'=>'2','id'=>$id])->value('id')){
            return returnData(['msg'=>'该产品暂未下架，无法操作！','code'=>'201']);
        }
        $data['money'] = input('post.money/f','','strip_tags');
        if (empty($data['money'])){
            return returnData(['msg'=>'线路价格不能为空','code'=>'201']);
        }
        $data['day'] = input('post.day/s','','strip_tags');
        if (empty($data['day'])){
            return returnData(['msg'=>'行程天数不能为空','code'=>'201']);
        }
//        $data['end_time'] = input('post.end_time/d','','strip_tags');
        $data['end_day'] = input('post.end_day/s','0');
//        $time = time();
//        if($data['end_time'] < bcadd("$time",bcmul($data['end_day'],'86400'))){
//            return returnData(['msg'=>'有效时间不合法','code'=>'201']);
//        }
        $data['update_time'] = time();

        Db::startTrans();
        try {
            Jproduct::where(['uid'=>$uid,'status'=>'9','type'=>'2','id'=>$id])->update($data);
            addXuserLog(getDecodeToken(),'修改线路：'.$id);
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
        if(!Jproduct::where(['uid'=>$uid,'status'=>'9','type'=>'2','id'=>$id])->value('id')){
            return returnData(['msg'=>'该产品暂未下架，无法操作！','code'=>'201']);
        }

        Db::startTrans();
        try {
            Jproduct::where(['uid'=>$uid,'status'=>'9','type'=>'2','id'=>$id])->update(['delete_time'=>time()]);
            addXuserLog(getDecodeToken(),'删除线路：'.$id);
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
        if(!Jproduct::where(['uid'=>$uid,'type'=>'2','id'=>$id])->value('id')){
            return returnData(['msg'=>'该产品不存在或没有权限','code'=>'201']);
        }
        if ($status == '0' || $status == '9'){
            Db::startTrans();
            try {
                Jproduct::where(['uid'=>$uid,'type'=>'2','id'=>$id])->update(['status'=>$status,'update_time'=>time()]);
                addXuserLog(getDecodeToken(),'上下架线路：'.$id);
                Db::commit();
                return returnData(['msg'=>'操作成功','code'=>'200']);
            }catch (\Exception $e){
                Db::rollback();
                return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
            }
        }
        return returnData(['msg'=>'产品状态不符合规则','code'=>'201']);

    }
}