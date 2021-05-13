<?php
declare (strict_types = 1);

namespace app\line\controller;

use app\common\model\Xuser;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\facade\Validate;
use think\Request;

class User
{

    /**
     * @author liujiong
     * @Note  修改用户信息
     */
    public function update(){
        $uid = getDecodeToken()['id'];
        if(!Xuser::where(['id'=>$uid,'status'=>'0'])->find()){
            return returnData(['msg'=>'该供应商已被紧用','code'=>'201']);
        }
        $data['nickname'] = input('post.nickname/s','','strip_tags');
        $data['phone'] = input('post.phone/s','','strip_tags');
        $data['avatar'] = input('post.avatar/d','','strip_tags');
        $data['weach'] = input('post.weach/s','','strip_tags');
        $data['QQ'] = input('post.QQ/s','','strip_tags');
        $data['position'] = input('post.position/s','','strip_tags');
        $data['address'] = input('post.address/s','','strip_tags');
        if (empty($data['nickname']) || empty($data['phone']) || empty($data['avatar']) || empty($data['weach']) || empty($data['QQ']) || empty($data['position']) || empty($data['address'])){
            return returnData(['msg'=>'参数错误','code'=>'201']);
        }
        if(Xuser::where(['phone'=>$data['phone'],'status'=>'0'])->value('id')){
            return returnData(['msg'=>'用户手机号已存在！','code'=>'201']);
        }
        $data['update_time'] = time();

        Db::startTrans();
        try {
            Xuser::where(['id'=>$uid,'status'=>'0'])->update($data);
            addXuserLog(getDecodeToken(),'修改用户昵称：'.$data['nickname'].'，用户手机号：'.$data['phone']);
            Db::commit();
            return returnData(['msg'=>'操作成功','code'=>'200']);
        }catch (\Exception $e){
            Db::rollback();
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }

    }

    /**
     * @author liujiong
     * @Note  获取用户基本信息
     */
    public function getFind(){
        $uid = getDecodeToken()['id'];
        $data = Xuser::where(['id'=>$uid,'status'=>'0'])->field('nickname,phone,avatar,weach,QQ,address')->find();
        if($data){
            return returnData(['data'=>$data,'code'=>'200']);
        }else{
            return returnData(['msg'=>'该供应商不存在或已被紧用','code'=>'201']);
        }

    }

    /**
     * @author liujiong
     * @Note  修改用户密码
     */
    public function updPasswd(){
        $uid = getDecodeToken()['id'];

        $passwd = input('post.passwd/s','','strip_tags');
        $new_passwd = input('post.new_passwd/s','','strip_tags');
        $confirm_passwd = input('post.confirm_passwd/d','','strip_tags');

        if (empty($passwd) || empty($new_passwd) || empty($confirm_passwd)){
            return returnData(['msg'=>'参数错误','code'=>'201']);
        }

        $userDate = Xuser::where(['id'=>$uid,'status'=>'0'])->find();

        if (!checkPasswd($passwd, $userDate)) {
            return returnData(['msg' => '原密码错误'], 201);
        }

        if($new_passwd != $confirm_passwd){
            return returnData(['msg' => '新密码不一致'], 201);
        }

        Db::startTrans();
        try {
            $data = encryptionPasswd($new_passwd);
            Xuser::where(['id'=>$uid,'status'=>'0'])->save($data);
            addXuserLog(getDecodeToken(),'用户 '.$userDate['phone'].' 修改密码：');
            Db::commit();
            return returnData(['msg'=>'操作成功','code'=>'200']);
        }catch (\Exception $e){
            Db::rollback();
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }
    }


}