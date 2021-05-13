<?php
declare (strict_types = 1);

namespace app\line\controller;

use app\common\model\Xenterprise;
use app\common\model\Xuser;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\facade\Validate;
use think\Request;

class Enterprise
{
    /**
     * @author liujiong
     * @Note  添加企业信息
     */
    public function add(){
        $data['uid'] = getDecodeToken()['id'];

        if(Xenterprise::where(['uid'=>$data['uid']])->value('id')){
            return returnData(['msg'=>'已存在，无法添加！','code'=>'201']);
        }

        $data['title'] = input('post.title/s','','strip_tags');
        $data['content'] = input('post.content/s','','strip_tags');
        $data['code'] = input('post.code/s','','strip_tags');
        $data['representative'] = input('post.representative/s','','strip_tags');
        $data['phone'] = input('post.phone/s','','strip_tags');
        $data['email'] = input('post.email/s','','strip_tags');
        $data['qualifications'] = input('post.qualifications/s','','strip_tags');
        $data['special_qualifications'] = input('post.special_qualifications/s','','strip_tags');
        $data['address'] = input('post.address/s','','strip_tags');

        Db::startTrans();
        try {
            Xenterprise::insert($data);
            addXuserLog(getDecodeToken(),'添加企业信息：'.$data['title']);
            Db::commit();
            return returnData(['msg'=>'操作成功','code'=>'200']);
        }catch (\Exception $e){
            Db::rollback();
            return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
        }
    }

    /**
     * @author liujiong
     * @Note  修改企业信息
     */
    public function update(){
        $uid = getDecodeToken()['id'];
        if(!Xuser::where(['id'=>$uid,'status'=>'0'])->find()){
            return returnData(['msg'=>'该用户已被紧用','code'=>'201']);
        }
        if(!Xenterprise::where(['uid'=>$uid])->value('id')){
            return returnData(['msg'=>'信息不存在，无法操作！','code'=>'201']);
        }
        $data['title'] = input('post.title/s','','strip_tags');
        $data['content'] = input('post.content/s','','strip_tags');
        $data['code'] = input('post.code/s','','strip_tags');
        $data['representative'] = input('post.representative/s','','strip_tags');
        $data['phone'] = input('post.phone/s','','strip_tags');
        $data['email'] = input('post.email/s','','strip_tags');
        $data['qualifications'] = input('post.qualifications/s','','strip_tags');
        $data['special_qualifications'] = input('post.special_qualifications/s','','strip_tags');
        $data['address'] = input('post.address/s','','strip_tags');

        Db::startTrans();
        try {
            Xenterprise::where(['id'=>$uid,'status'=>'0'])->update($data);
            addXuserLog(getDecodeToken(),'修改企业信息：'.$data['title'].'，负责人手机号：'.$data['phone']);
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
        $Xenterprise = new Xenterprise();
        $data = $Xenterprise->alias('a')
            ->join('file b','b.id = a.qualifications','LEFT')
            ->join('file c','c.id = a.special_qualifications','LEFT')
            ->where(['uid'=>$uid])
            ->field('a.title,a.content,a.code,a.representative,a.phone,a.email,b.file_path qualifications,c.file_path special_qualifications,a.address')
            ->find();
        if($data){
            return returnData(['data'=>$data,'code'=>'200']);
        }else{
            return returnData(['msg'=>'该用户不存在或已被紧用','code'=>'201']);
        }

    }


}