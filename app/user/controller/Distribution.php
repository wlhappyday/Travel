<?php
declare (strict_types = 1);

namespace app\user\controller;

use app\common\model\Puseruser;
use app\platform\model\P_user;
use think\facade\Db;
use think\Request;
use hg\apidoc\annotation as Apidoc;
/**
 *
 * @Apidoc\Title("分销管理")
 * @Apidoc\Group("distribution")
 */
class Distribution
{

    /**
     * @Apidoc\Title("分销商列表")
     * @Apidoc\Desc("分销商列表")
     * @Apidoc\Url("user/distribution/userlist")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Param("pagenum", type="string",require=false, desc="分页数量" )
     * @Apidoc\Param("nickname", type="string",require=false, desc="微信昵称" )
     * @Apidoc\Param("name", type="string",require=false, desc="姓名" )
     * @Apidoc\Param("is_distcenter", type="string",require=false, desc="审核状态  是否是分销商  1已同意 2申请中  3已拒绝" )
     * @Apidoc\Returned("data",type="object",desc="列表",ref="app\common\model\Puseruser\zhu")
     */
    public function userlist(Request  $request){
        $id = $request->id;
        $is_distcenter = $request->get('is_distcenter');
        if ($request->isGet()){
            $name = $request->get('name');
            $nickname =  $request->get('nickname');
            $pagenum = $request->get('pagenum');
            $admin = Puseruser::where(['is_distcenter'=>'1','puser_id'=>$id])->order('distcenter_time','Desc')
                ->field('id,avatar,nickname,name,phone,is_distcenter,offline_count,distcenter_time,distcenters_time')
                ->where('is_distcenter','like','%'.$is_distcenter.'%')
                ->where('name','like','%'.$name.'%')
                ->where('nickname','like','%'.$nickname.'%')->paginate($pagenum);
            return json(['code'=>'200','msg'=>'操作成功','data'=>$admin]);
        }
        return json(['code'=>'201','msg'=>'请用GET访问']);
    }

    /**
     * @Apidoc\Title("分销商列表")
     * @Apidoc\Desc("分销商列表")
     * @Apidoc\Url("user/distribution/isDistcenter")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("列表 基础")
     * @Apidoc\Param("puser_id", type="string",require=false, desc="分销商列表的id" )
     * @Apidoc\Param("is_distcenter", type="string",require=false, desc="审核状态  是否是分销商  1已同意 2申请中  3已拒绝" )

     */
    public function isDistcenter(Request  $request){
        $is_distcenter = $request->POST('is_distcenter');
        $id = $request->post('puser_id');
        if ($request->isPost()){
            Db::startTrans();
            try {
                $admin = Puseruser::where('id',$id)->field('is_distcenter')->find();
                $admin->is_distcenter=$is_distcenter;
                $admin->save();
                if ($is_distcenter=='1'){
                    addPuserLog(getDecodeToken(),'审核分销商通过：'.$id);
                }
                if ($is_distcenter=='2'){
                    addPuserLog(getDecodeToken(),'审核分销商拒绝：'.$id);
                }
                Db::commit();
                return json(['code'=>'200','msg'=>'操作成功']);
            }catch (\Exception $e){
                Db::rollback();
                return json(['code'=>'201','msg'=>'网络繁忙']);
            }
        }
        return json(['code'=>'201','msg'=>'请用POST提交']);
    }

    public function distcenterPrice(Request  $request){
        $id = $request->id;
        $distribution = $request->post('distribution');
        if ($request->isPost()){
            Db::startTrans();
            try {
                 P_user::where('id',$id)->update([
                    'distribution'=>$distribution
                ]);
                addPuserLog(getDecodeToken(),'修改分销商比例：'.$id);
                Db::commit();
                return json(['code'=>'200','msg'=>'操作成功']);
            }catch (\Exception $e){
                Db::rollback();
                return json(['code'=>'201','msg'=>'网络繁忙']);
            }
        }
        return json(['code'=>'201','msg'=>'请用POST提交']);
    }
}
