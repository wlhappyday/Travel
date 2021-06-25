<?php
declare (strict_types = 1);

namespace app\scenic\controller;

use app\common\model\JfeeChange as J_feeChange;
use app\common\model\Juser;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\facade\Validate;
use think\Request;

class FeeChange
{

    /**
     * @author liujiong
     * @Note  信息费变动
     */
    public function list(){
        $num = input('post.num/d','10','strip_tags');
        $type = input('post.type/d','','strip_tags');
        $id = getDecodeToken()['id'];
        $where = [];
        $where['type'] = '1';
        $where['uid'] = $id;
        $state = input('post.state/d','','strip_tags');
        if($state){
            $where['state'] = $state;
        }

        $Jenterprise = new J_feeChange();
        $data = $Jenterprise
            ->where($where)
            ->field('id,before_money,money,after_money,state,data_id,create_time')
            ->paginate($num)->toArray();

        return returnData(['data'=>$data,'code'=>'200']);

    }


}