<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\model\JfeeChange as J_feeChange;
use app\common\model\Juser;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\facade\Validate;
use think\Request;

class JfeeChange
{

    /**
     * @author liujiong
     * @Note  信息费变动
     */
    public function list(){
        $num = input('post.num/d','10','strip_tags');
        $uname = input('post.uname/s','','strip_tags');
        $phone = input('post.phone/s','','strip_tags');
        $type = input('post.type/d','','strip_tags');
        $where = [];
        if($type == '1'){
            if ($uname){
                $where['b.user_name'] = $uname;
            }
            if ($phone){
                $where['b.phone'] = $phone;
            }
            $where['a.type'] = $type;
        }elseif ($type == '3'){
            if ($uname){
                $where['c.user_name'] = $uname;
            }
            if ($phone){
                $where['c.phone'] = $phone;
            }
            $where['a.type'] = $type;
        }elseif ($type > 1){
            return returnData(['msg'=>'该用户类型暂未使用，请稍等！','code'=>'201']);
        }
        $state = input('post.state/d','','strip_tags');
        if($state){
            $where['a.state'] = $state;
        }


        $Jenterprise = new J_feeChange();
        $data = $Jenterprise->alias('a')
            ->join('J_user b','b.id=a.uid and a.type=1','LEFT')
            ->join('p_admin c','c.id=a.uid and a.type=3','LEFT')
            ->where($where)
            ->field('a.id,a.type,a.before_money,a.money,a.after_money,a.state,a.data_id,a.create_time,b.user_name jname,b.phone jphone,c.user_name pname,c.phone pphone')
            ->paginate($num)->toArray();

        return returnData(['data'=>$data,'code'=>'200']);

    }


}