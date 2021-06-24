<?php
declare (strict_types = 1);

namespace app\user\controller;

use app\common\model\File;
use app\common\model\Pcarousel;
use app\common\model\Puser;
use app\common\model\Puserpage;
use app\common\model\Pusermagic;
use app\common\model\Ptemplatemessage;
use app\common\model\Pusernavigation;
use app\common\model\Puserhomenavigation;
use think\facade\Db;
use think\facade\Validate;
use think\Request;
use hg\apidoc\annotation as Apidoc;
/**
 *
 * @Apidoc\Title("小程序管理接口")
 * @Apidoc\Group("applets")
 */
class Applets
{

    /**
     * @Apidoc\Title("模板消息列表")
     * @Apidoc\Desc("模板消息列表")
     * @Apidoc\Url("user/applets/templatemessagelist")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("小程序")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned ("templatemessage",type="object",desc="模板消息",ref="app\common\model\Ptemplatemessage\templatemessage"),
     */
    public function templatemessage_list(Request $request){
        $id = $request->id;
        $templatemessage = Ptemplatemessage::where(['user_id'=>$id])->find();
        return json(['code'=>'200','msg'=>'操作成功','templatemessage'=>$templatemessage]);
    }

    /**
     * @Apidoc\Title("模板消息配置")
     * @Apidoc\Desc("模板消息配置")
     * @Apidoc\Url("user/applets/templatemessage")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("小程序")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param ("templatemessage",type="object",desc="模板消息",ref="app\common\model\Ptemplatemessage\templatemessage"),
     */
    public function templatemessage(Request $request){
        $id = $request->id;
        $order_pay = $request->post('order_pay');
        $order_cancel = $request->post('order_cancel');
        $order_delivery = $request->post('order_delivery');
        $order_refund = $request->post('order_refund');
        $infosave = $request->post('infosave');
        $enroll_error = $request->post('enroll_error');
        $account_change = $request->post('account_change');
        $verify_result = $request->post('verify_result');
        $withdrawal_success = $request->post('withdrawal_success');
        $withdrawal_error = $request->post('withdrawal_error');
        $distribution_examine = $request->post('distribution_examine');
        Db::startTrans();
        try {
            $templatemessage = Ptemplatemessage::where(['user_id'=>$id])->find();
            if (empty($templatemessage)){
                $templatemessage = new Ptemplatemessage();
                $templatemessage->user_id = $id;
            }
            $templatemessage->order_pay = $order_pay;
            $templatemessage->order_cancel = $order_cancel;
            $templatemessage->order_delivery = $order_delivery;
            $templatemessage->order_refund = $order_refund;
            $templatemessage->infosave = $infosave;
            $templatemessage->enroll_error = $enroll_error;
            $templatemessage->account_change = $account_change;
            $templatemessage->verify_result = $verify_result;
            $templatemessage->withdrawal_success = $withdrawal_success;
            $templatemessage->withdrawal_error = $withdrawal_error;
            $templatemessage->withdrawal_error = $withdrawal_error;
            $templatemessage->distribution_examine = $distribution_examine;
            $templatemessage->save();
            addPuserLog(getDecodeToken(),'配置消息模板');
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'网络繁忙']);
        }
    }
    /**
     * @Apidoc\Title("小程序轮播图列表")
     * @Apidoc\Desc("小程序轮播图列表")
     * @Apidoc\Url("user/applets/carousellist")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("小程序")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned ("carousel",type="object",desc="轮播图",ref="app\common\model\Pcarousel\carousel"),
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function carousel_list(Request $request){
        $id = $request->id;
        $appid = Puser::where(['id'=>$id])->value('appid');
        $carousel = Pcarousel::where(['appid'=>$appid])->field('carousel_id,type,img,page')->select();
        foreach ($carousel as $key=>$val){
            $carousel[$key]['img'] = http(). File::where('id',$val['img'])->value('file_path');
        }
        return json(['code'=>'200','msg'=>'操作成功','carousel'=>$carousel]);
    }

    /**
     * @Apidoc\Title("小程序轮播图添加\修改")
     * @Apidoc\Desc("小程序轮播图添加\修改")
     * @Apidoc\Url("user/applets/carousel")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("小程序")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("carousel_id", type="number",require=true, desc="轮播图的唯一id   修改的时候传入" )
     * @Apidoc\Param("img_id", type="number",require=true, desc="图片id" )
     * @Apidoc\Param("type", type="number",require=false, desc="状态 1开启 0关闭" )
     * @Apidoc\Param("page", type="number",require=false, desc="小程序页面连接" )
     */
    public function carousel(Request $request){
        $id = $request->id;
        $carousel_id = $request->post('carousel_id');
        $rule = [
            'img_id'=>'require',
        ];
        $msg = [
            'img_id.require'=>'请上传图片',
        ];
        $validate = Validate::rule($rule)->message($msg);
        if (!$validate->check($request->post())) {
            return json(['code'=>'201','msg'=>$validate->getError()]);
        }
        $appid = Puser::where(['id'=>$id])->value('appid');
        $img = $request->post('img_id');
        $type = $request->post('type');
        $page = $request->post('page');
        Db::startTrans();
        try {
            if ($carousel_id){
                $carousel = Pcarousel::where('carousel_id',$carousel_id)->find();
            }else{
                $carousel = new Pcarousel();
            }
            $carousel->appid = $appid;
            $carousel->img = $img;
            $carousel->type = $type;
            $carousel->page = $page;
            $carousel->save();
            if ($carousel_id){
                addPuserLog(getDecodeToken(),'修改轮播图：'.$carousel_id);
            }else{
                addPuserLog(getDecodeToken(),'添加轮播图：'.$carousel['id']);
            }
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'网络繁忙']);
        }
    }

    /**
     * @Apidoc\Title("小程序轮播图详情")
     * @Apidoc\Desc("小程序轮播图详情")
     * @Apidoc\Url("user/applets/carouseldetail")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("小程序")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("carousel_id", type="number",require=true, desc="唯一id" )
     * @Apidoc\Returned ("carousel",type="object",desc="轮播图",ref="app\common\model\Pcarousel\carousel"),
     * @Apidoc\Returned("sign",type="string",desc="错误提示")
     */
    public function carouseldetail(Request $request){
        $carousel_id = $request->get('carousel_id');
        $carousel = Pcarousel::where(['carousel_id'=>$carousel_id])->field('carousel_id,type,img,page')->find();
        $carousel['img'] = http(). File::where('id',$carousel['img'])->value('file_path');
        return json(['code'=>'200','msg'=>'操作成功','carousel'=>$carousel]);
    }


    /**
     * @Apidoc\Title("小程序轮播图删除")
     * @Apidoc\Desc("小程序轮播图删除")
     * @Apidoc\Url("user/applets/carouseldel")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("小程序")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("carousel_id", type="number",require=true, desc="唯一id" )
     */
    public function carousel_del(Request $request){
        $carousel_id = $request->get('carousel_id');
        Db::startTrans();
        try {
            $carousel = Pcarousel::where('carousel_id',$carousel_id)->delete();
            addPuserLog(getDecodeToken(),'删除轮播图：',$carousel_id);
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'网络繁忙']);
        }
    }

    /**
     * @Apidoc\Title("导航图标列表")
     * @Apidoc\Desc("导航图标列表")
     * @Apidoc\Url("user/applets/homenavigationlist")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("小程序")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned ("navigation",type="object",desc="导航图标列表",ref="app\common\model\Puserhomenavigation\navigation"),
     */
    public function homenavigation_list(Request $request){
        $id = $request->id;
        $navigation = Puserhomenavigation::where('user_id',$id)->field('id,type,title,img,page_id')->select();
        foreach ($navigation as $key => $value) {
            $navigation[$key]['img'] =http(). File::where('id',$value['img'])->value('file_path');
            $navigation[$key]['page_id'] = Puserpage::where('id',$value['page_id'])->value('page');
        }
        return json(['code'=>'200','msg'=>'操作成功','navigation'=>$navigation]);

    }


    /**
     * @Apidoc\Title("导航图标修改状态")
     * @Apidoc\Desc("导航图标修改状态")
     * @Apidoc\Url("user/applets/homenavigationtype")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("小程序")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     *  @Apidoc\Param("navigation_id", type="number",require=true, desc="唯一id" )
     *  @Apidoc\Param("type", type="number",require=true, desc="状态值" )
     */
    public function homenavigation_type(Request $request){
        $navigation_id = $request->post('navigation_id');
        $type = $request->post('type');
        Db::startTrans();
        try {
            $navigation = Puserhomenavigation::where('id',$navigation_id)->find();
            $navigation->type = $type;
            $navigation->save();
            if ($type=='1'){
                addPuserLog(getDecodeToken(),'开启导航图标：',$navigation_id);
            }else{
                addPuserLog(getDecodeToken(),'关闭导航图标：',$navigation_id);
            }
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'网络繁忙']);
        }
    }

    /**
     * @Apidoc\Title("导航图标详情")
     * @Apidoc\Desc("导航图标详情")
     * @Apidoc\Url("user/applets/homenavigationdetail")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("小程序")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned ("navigation",type="object",desc="导航图标详情",ref="app\common\model\Puserhomenavigation\navigation"),
     */
    public function homenavigation_detail(Request $request){
        $navigation_id = $request->get('navigation_id');
        $navigation = Puserhomenavigation::where('id',$navigation_id)->find();
        $navigation['img'] =http(). File::where('id',$navigation['img'])->value('file_path');
        $navigation['page_id'] = Puserpage::where('id',$navigation['page_id'])->value('page');
        return json(['code'=>'200','msg'=>'操作成功','navigation'=>$navigation]);
    }


    /**
     * @Apidoc\Title("导航图标添加\修改")
     * @Apidoc\Desc("导航图标添加\修改")
     * @Apidoc\Url("user/applets/homenavigation")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("小程序")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("navigation_id", type="number",require=true, desc="导航图标唯一id   修改的时候传入" )
     * @Apidoc\Param("title", type="number",require=true, desc="图片id" )
     * @Apidoc\Param("img", type="number",require=false, desc="状态 1开启 0关闭" )
     * @Apidoc\Param("page_id", type="number",require=false, desc="小程序页面连接" )
     */
    public function homenavigation(Request $request){
        $id = $request->id;
        $navigation_id = $request->post('navigation_id');
        $rule = [
            'title'=>'require',
            'img'=>'require',
            'page_id'=>'require',
        ];
        $msg = [
            'title.require'=>'请输入名称',
            'img.require'=>'请上传图片',
            'page_id.require'=>'请选择页面链接',
        ];
        $data = $request->post();
        $validate = Validate::rule($rule)->message($msg);
        if (!$validate->check($data)) {
            return json(['code'=>'201','msg'=>$validate->getError()]);
        }

        Db::startTrans();
        try {
            if ($navigation_id){
                $navigation = Puserhomenavigation::where('id',$navigation_id)->find();
            }else{
                $navigation = new Puserhomenavigation();
                $navigation->user_id = $id;
            }
            $navigation->title = $data['title'];
            $navigation->img = $data['img'];
            $navigation->page_id = $data['page_id'];
            $navigation->save();
            if ($navigation_id){
                addPuserLog(getDecodeToken(),'修改导航图标：'.$navigation);
            }else{
                addPuserLog(getDecodeToken(),'添加导航图标：'.$navigation['id']);
            }
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'网络繁忙']);
        }
    }


    /**
     * @Apidoc\Title("导航图标删除")
     * @Apidoc\Desc("导航图标删除")
     * @Apidoc\Url("user/applets/homenavigationdel")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("小程序")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("navigation_id", type="number",require=true, desc="唯一id" )
     */
    public function homenavigation_del(Request $request){
        $navigation_id = $request->post('navigation_id');
        Db::startTrans();
        try {
            Puserhomenavigation::where('id',$navigation_id)->delete();
            addPuserLog(getDecodeToken(),'删除导航图标：',$navigation_id);
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'网络繁忙']);
        }
    }

    /**
     * @Apidoc\Title("图片魔方")
     * @Apidoc\Desc("图片魔方")
     * @Apidoc\Url("user/applets/magic")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("小程序")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("navigation_id", type="number",require=true, desc="唯一id" )
     */
    public function magic(Request $request){
        $id = $request->id;
        $magic = Pusermagic::where(['user_id'=>$id])->find();
        return json(['code'=>'200','msg'=>'操作成功','magic'=>$magic]);
    }


    /**
     * @Apidoc\Title("图片魔方配置")
     * @Apidoc\Desc("图片魔方配置")
     * @Apidoc\Url("user/applets/magic_do")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("小程序")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("navigation_id", type="number",require=true, desc="唯一id" )
     * @Apidoc\Param("img", type="number",require=true, desc="img[1][img]  img[1][page]" )
     * @Apidoc\Param("style", type="number",require=true, desc="1为样式1" )
     */
    public function magic_do(Request $request){
        $id = $request->id;
        $img = $request->post('img');
        $style = $request->post('style');
        Db::startTrans();
        try {
            $magic = Pusermagic::where('user_id',$id)->find();
            if(!$magic){
                $magic = new Pusermagic();
            }
            $magic->img = $img;
            $magic->style = $style;
            $magic->user_id= $id;
            $magic->save();
            addPuserLog(getDecodeToken(),'配置图片魔方');
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'网络繁忙']);
        }

    }
    /**
     * @Apidoc\Title("底部导航")
     * @Apidoc\Desc("底部导航")
     * @Apidoc\Url("user/applets/navigationlist")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("小程序")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Returned ("navigation",type="object",desc="底部导航列表",ref="app\common\model\Pusernavigation\navigation"),
     * @Apidoc\Returned ("navigations",type="object",desc="导航栏配置",
     *     @Apidoc\Returned ("dnavigationcolor",type="number",desc="顶部导航栏文字颜色"),
     *     @Apidoc\Returned ("dnavigationback",type="int",desc="顶部导航栏背景颜色"),
     *     @Apidoc\Returned ("dinavigationback",type="int",desc="底部导航栏背景颜色"),
     *     @Apidoc\Returned ("dinavigationtcolor",type="int",desc="底部导航文字未选中颜色"),
     *     @Apidoc\Returned ("dinavigationtcolors",type="int",desc="底部导航文字选中颜色"),
     *  )
     */
    public function navigationlist(Request $request){
        $id = $request->id;
        $navigation = Pusernavigation::where('user_id',$id)->field('navigation_id,img,title')->select();
        $navigations = Puser::where('id',$id)->field('dnavigationcolor,dnavigationback,dinavigationback,dinavigationtcolor,dinavigationtcolors')->find();
        foreach ($navigation as $key=>$val){
            $navigation[$key]['img'] =http().File::where('id',$val['img'])->value('file_path');
        }
        return json(['code'=>'200','msg'=>'操作成功','navigation'=>$navigation,'navigations'=>$navigations]);
    }
    /**
     * @Apidoc\Title("底部导航详情")
     * @Apidoc\Desc("底部导航详情")
     * @Apidoc\Url("user/applets/navigationdetail")
     * @Apidoc\Method("GET")
     * @Apidoc\Tag("小程序")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("navigation_id", type="number",require=true, desc="唯一id" )
     * @Apidoc\Returned ("navigation",type="object",desc="底部导航",ref="app\common\model\Pusernavigation\navigation"),
     */
    public function navigation_detail(Request $request){
        $navigation_id = $request->get('navigation_id');
        $navigation = Pusernavigation::where('navigation_id',$navigation_id)->field('navigation_id,img,imgs,page_id,title')->find();
        $navigation['img'] =http().File::where('id',$navigation['img'])->value('file_path');
        $navigation['imgs'] = http(). File::where('id',$navigation['imgs'])->value('file_path');
        $navigation['page_id'] = Puserpage::where('id',$navigation['page_id'])->value('page');
        return json(['code'=>'200','msg'=>'操作成功','navigation'=>$navigation]);
    }

    /**
     * @Apidoc\Title("底部导航修改")
     * @Apidoc\Desc("底部导航修改")
     * @Apidoc\Url("user/applets/navigation")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("小程序")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param("navigation_id", type="number",require=true, desc="唯一id" )
     * @Apidoc\Param("img", type="number",require=true, desc="未选中图标" )
     * @Apidoc\Param("imgs", type="number",require=true, desc="选中图标" )
     * @Apidoc\Param("title", type="number",require=true, desc="名称" )
     * @Apidoc\Param("page_id", type="number",require=true, desc="页面链接" )
     * @Apidoc\Returned ("navigation",type="object",desc="底部导航",ref="app\common\model\Pusernavigation\navigation"),
     */
    public function navigation(Request $request){
        $id = $request->id;
        $rule = [
            'navigation_id'=>'require',
            'img'=>'require',
            'imgs'=>'require',
            'title'=>'require',
            'page_id'=>'require',
        ];
        $msg = [
            'navigation_id.require'=>'参数错误',
            'img.require'=>'请上传未选中的图标',
            'imgs.require'=>'请上传选中的图标',
            'title.require'=>'名称不能为空',
            'page_id.require'=>'请选择页面连接',
        ];
        $data = $request->post();
        $validate = Validate::rule($rule)->message($msg);
        if (!$validate->check($data)) {
            return json(['code'=>'201','msg'=>$validate->getError()]);
        }

        Db::startTrans();
        try {
            $navigation_id = $request->post('navigation_id');
            $navigation = Pusernavigation::where('navigation_id',$navigation_id)->find();
            $navigation->img = $data['img'];
            $navigation->imgs = $data['imgs'];
            $navigation->title = $data['title'];
            $navigation->page_id = $data['page_id'];
            $navigation->save();
            addPuserLog(getDecodeToken(),'修改底部导航栏配置：'.$navigation_id);
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'网络繁忙']);
        }
    }

    /**
     * @Apidoc\Title("底部导航修改")
     * @Apidoc\Desc("底部导航修改")
     * @Apidoc\Url("user/applets/navigations")
     * @Apidoc\Method("POST")
     * @Apidoc\Tag("小程序")
     * @Apidoc\Header("Authorization", require=true, desc="Token")
     * @Apidoc\Param ("dnavigationcolor",type="number",require=true,desc="顶部导航栏文字颜色"),
     * @Apidoc\Param ("dnavigationback",type="int",require=true,desc="顶部导航栏背景颜色"),
     * @Apidoc\Param ("dinavigationback",type="int",require=true,desc="底部导航栏背景颜色"),
     * @Apidoc\Param ("dinavigationtcolor",type="int",require=true,desc="底部导航文字未选中颜色"),
     * @Apidoc\Param ("dinavigationtcolors",type="int",require=true,desc="底部导航文字选中颜色"),
     */
    public function navigations(Request $request){
        $id = $request->id;
        $rule = [
            'dnavigationcolor'=>'require',
            'dnavigationback'=>'require',
            'dinavigationback'=>'require',
            'dinavigationtcolor'=>'require',
            'dinavigationtcolors'=>'require',
        ];
        $msg = [
            'dnavigationcolor.require'=>'顶部导航栏文字颜色不能为空',
            'dnavigationback.require'=>'顶部导航栏背景颜色不能为空',
            'dinavigationback.require'=>'底部导航栏背景颜色不能为空',
            'dinavigationtcolor.require'=>'底部导航文字未选中颜色不能为空',
            'dinavigationtcolors.require'=>'底部导航文字选中颜色不能为空',
        ];
        $data = $request->post();
        $validate = Validate::rule($rule)->message($msg);
        if (!$validate->check($data)) {
            return json(['code'=>'201','msg'=>$validate->getError()]);
        }

        Db::startTrans();
        try {
            $user = Puser::find($id);
            $user->dnavigationcolor = $data['dnavigationcolor'];
            $user->dnavigationback = $data['dnavigationback'];
            $user->dinavigationback = $data['dinavigationback'];
            $user->dinavigationtcolor = $data['dinavigationtcolor'];
            $user->dinavigationtcolors = $data['dinavigationtcolors'];
            $user->save();
            addPuserLog(getDecodeToken(),'修改底部导航栏配置');
            Db::commit();
            return json(['code'=>'200','msg'=>'操作成功']);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>'201','msg'=>'网络繁忙']);
        }
    }


}
