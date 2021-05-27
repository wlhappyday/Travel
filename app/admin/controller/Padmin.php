<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\model\Padmin as P_admin;
use app\common\model\Puser;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\facade\Validate;
use think\Request;

class Padmin
{
    /**
     * @author liujiong
     * @Note  添加用户
     */
    public function add(){
        $data['user_name'] = input('post.user_name/s','','strip_tags');
        $data['passwd'] = input('post.passwd/s','','strip_tags');
        $data['phone'] = input('post.phone/s','','strip_tags');
        $data['status'] = input('post.status/d','','strip_tags');
        $data['rate'] = input('post.rate/f','','strip_tags');

        $rule = [
            'user_name' => 'require|unique:P_admin',
            'passwd' => 'require',
            'phone' => 'require|unique:P_admin|number|max:11|min:11',
//            'phone' => 'require|unique:P_admin|number|max:11|min:11|mobile',
            'status' => 'require|in:0,9',
            'rate' => 'require',
        ];
        $msg = [
            'user_name.require' => '用户名称不存在',
            'passwd.require' => '密码不存在',
            'phone.require' => '手机号不存在',
            'status.require' => '用户状态不存在',
            'rate.require' => '用户费率不存在',

            'user_name.unique' => '用户名称已存在',
            'phone.unique' => '用户手机号已存在',
            'phone.number'=> '手机号必须是全数字',
            'mobile.max' => '手机号不能超过11位',
            'phone.min' => '手机号不能小于11位',
            'phone.mobile' => '不是可用手机号',
            'phone.status' => '用户状态必须在 0,9 范围内',
        ];

        $validate = Validate::rule($rule)->message($msg);
        if (!$validate->check($data)) {
            return json(['code'=>'201','msg'=>$validate->getError()]);
        }
        $passwd = encryptionPasswd($data['passwd']);
        $data['create_time'] = time();
        $data['passwd'] = $passwd['passwd'];
        $data['passwd_salt'] = $passwd['passwd_salt'];

        Db::startTrans();
        try {
            P_admin::insert($data);
            addAdminLog(getDecodeToken(),'添加平台商：'.$data['user_name'].' 手机号: '.$data['phone']);
            Db::commit();
            return returnData(['msg'=>'操作成功','code'=>'200']);
        }catch (\Exception $e){
            Db::rollback();
//            p($e->getMessage());
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }
    }

    /**
     * @author liujiong
     * @Note  修改用户信息
     */
    public function update(){
        $data['id'] = input('post.id/d','','strip_tags');
        $data['passwd'] = input('post.passwd/s','','strip_tags');
        $data['phone'] = input('post.phone/s','','strip_tags');
        $data['status'] = input('post.status/d','','strip_tags');
        $data['rate'] = input('post.rate/f','','strip_tags');

        $rule = [
            'id' => 'require',
            'passwd' => 'require',
            'phone' => 'require|unique:P_admin|number|max:11|min:11',
//            'phone' => 'require|unique:P_admin|number|max:11|min:11|mobile',
            'status' => 'require|in:0,9',
            'rate' => 'require',
        ];
        $msg = [
            'id.require' => '用户id不存在',
            'passwd.require' => '密码不存在',
            'phone.require' => '手机号不存在',
            'status.require' => '用户状态不存在',
            'rate.require' => '用户费率不存在',
            'phone.unique' => '用户手机号已存在',
            'phone.number'=> '手机号必须是全数字',
            'mobile.max' => '手机号不能超过11位',
            'phone.min' => '手机号不能小于11位',
            'phone.mobile' => '不是可用手机号',
            'phone.status' => '用户状态必须在 0,9 范围内',
        ];
        $phone = P_admin::where(['id'=>$data['id']])->value('phone');
        if($data['phone'] == $phone){
            unset($data['phone']);
            unset($rule['phone']);
        }
        if(empty($data['passwd'])){
            unset($data['passwd']);
            unset($rule['passwd']);
        }else{
            $passwd = encryptionPasswd($data['passwd']);
            $data['passwd'] = $passwd['passwd'];
            $data['passwd_salt'] = $passwd['passwd_salt'];
        }
        $validate = Validate::rule($rule)->message($msg);
        if (!$validate->check($data)) {
            return json(['code'=>'201','msg'=>$validate->getError()]);
        }

        $data['update_time'] = time();

        Db::startTrans();
        try {
            $uid = $data['id'];
            unset($data['id']);
            P_admin::where(['id'=>$uid])->update($data);
            addAdminLog(getDecodeToken(),'修改平台商用户信息：'.$phone);
            Db::commit();
            return returnData(['msg'=>'操作成功','code'=>'200']);
        }catch (\Exception $e){
            Db::rollback();
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }

    }

    /**
     * @author liujiong
     * @Note  获取用户信息
     */
    public function list(){
        $num = input('post.num/d','10','strip_tags');

        $user_name = input('post.user_name/s','','strip_tags');
        $where = [];
        if ($user_name){
            $where['user_name'] = $user_name;
        }
        $phone = input('post.phone/s','','strip_tags');
        if ($phone){
            $where['phone'] = $phone;
        }
        $status = input('post.status/d');

        if (isset($status)){
            $where['status'] = $status;
        }

        $user_result = new P_admin();
        $data = $user_result->where($where)
            ->field('id,user_name,phone,weach,QQ,position,address,weach,login_ip,login_time,login_address,Round(rate*100,2) rate,status')
            ->paginate($num);

        if($data){
            return returnData(['data'=>$data,'code'=>'200']);
        }else{
            return returnData(['msg'=>'该用户不存在或已被紧用','code'=>'201']);
        }

    }

    /**
     * @author liujiong
     * @Note  禁用用户
     */
    public function updStatus(){
        $uid = input('post.id/d','','strip_tags');
        $data['status'] = input('post.status/d');

        if (empty($uid) || !isset($data['status'])){
            return returnData(['msg'=>'参数错误','code'=>'201']);
        }
        if (!in_array($data['status'],[0,9])){
            return returnData(['msg'=>'用户状态不符合规则','code'=>'201']);
        }
        if($data['status'] == 9){
            $info = '禁用平台商用户 '.$uid;
        }else{
            $info = '启用平台商用户 '.$uid;
        }
        $data['update_time'] = time();

        Db::startTrans();
        try {
            P_admin::where(['id'=>$uid])->save($data);
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
     * @Note  删除用户
     */
    public function isDelete(){
        $uid = input('post.id/d','','strip_tags');

        if (empty($uid)){
            return returnData(['msg'=>'参数错误','code'=>'201']);
        }
        $data['delete_time'] = time();

        Db::startTrans();
        try {
            P_admin::where(['id'=>$uid])->save($data);
            addAdminLog(getDecodeToken(),'删除平台商用户 '.$uid);
            Db::commit();
            return returnData(['msg'=>'操作成功','code'=>'200']);
        }catch (\Exception $e){
            Db::rollback();
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }
    }

    /**
     * @author liujiong
     * @Note  查看门店
     */
    public function getUser(){

        $num = input('post.num/d','10','strip_tags');
        $uid = input('post.id/d','','strip_tags');
        $user_name = input('post.user_name/s','','strip_tags');
        $where = [];
        if ($user_name){
            $where['user_name'] = $user_name;
        }
        $phone = input('post.phone/s','','strip_tags');
        if ($phone){
            $where['phone'] = $phone;
        }
        $status = input('post.status/d');

        if (isset($status)){
            $where['status'] = $status;
        }
        if (empty($uid)){
            return returnData(['msg'=>'参数错误','code'=>'201']);
        }

        $user_result = new Puser();
        $data = $user_result->where($where)
            ->field('id,user_name,phone,weach,QQ,position,address,weach,login_ip,login_time,login_address,Round(rate*100,2) rate,status')
            ->paginate($num);

        if($data){
            return returnData(['data'=>$data,'code'=>'200']);
        }else{
            return returnData(['msg'=>'该用户不存在或已被紧用','code'=>'201']);
        }


    }


}