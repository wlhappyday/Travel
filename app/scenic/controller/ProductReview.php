<?php
declare (strict_types = 1);

namespace app\scenic\controller;

use app\common\model\JproductReview;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\Model;
use think\Request;

class ProductReview
{
    public function list(){
        $num = input('post.num/d','10','strip_tags');
        $uid = getDecodeToken()['id'];
        $where = [];
        $where['a.type'] = '1';
        $where['a.uid'] = $uid;
        $where['b.type'] = '1';
        $where['b.uid'] = $uid;
        $where['b.mp_id'] = 6;
        $state = input('post.state');
        if ($state){
            $where['a.state'] = $state;
        }
        $name = input('post.name/s','','strip_tags');
        if ($name){
            $where['c.name'] = $name;
        }

        $product_result = new JproductReview();
        $data = $product_result->alias('a')->where($where)
            ->join('j_product b','b.id=a.product_id','LEFT')
            ->join('p_admin c','c.id=a.pid','LEFT')
            ->field('a.id,b.name product_name,b.class_name,c.user_name,c.phone,a.state,a.create_time')->paginate($num)->toArray();

        return returnData(['data'=>$data,'code'=>'200']);
    }
    public function state(){
        $uid = getDecodeToken()['id'];
        $id = input('post.id/d','','strip_tags');
        $state = input('post.state/d','','strip_tags');
        if (empty($id) || empty($state)){
            return returnData(['msg'=>'参数错误','code'=>'201']);
        }

        if($state == '2'){
            Db::startTrans();
            try {

                $data = JproductReview::where(['id'=>$id])->find()->toArray();
                $data = product_relation($data['pid'],$data['product_id'],$id);
                if($data['code'] == '200'){
                    addJuserLog(getDecodeToken(),'产品审核：通过 审核id '.$id);
                    Db::commit();
                    return returnData(['msg'=>$data['msg'],'code'=>'200']);
                }else{
                    Db::rollback();
                    return returnData(['msg'=>$data['msg'],'code'=>'201']);
                }

            }catch (\Exception $e){
                Db::rollback();
                return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
            }
        }elseif ($state == '3'){
            Db::startTrans();
            try {
                JproductReview::where(['id'=>$id,'uid'=>$uid])->update(['state'=>$state,'update_time'=>time()]);

                addJuserLog(getDecodeToken(),'产品审核：拒绝 审核id '.$id);
                Db::commit();
                return returnData(['msg'=>'操作成功','code'=>'200']);
            }catch (\Exception $e){
                Db::rollback();
                return returnData(['msg'=>'数据操作错误，请检查','code'=>'201']);
            }
        }else{
            return returnData(['msg'=>'参数错误','code'=>'201']);
        }

    }

}