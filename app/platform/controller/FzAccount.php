<?php
declare (strict_types = 1);

namespace app\platform\controller;

use app\common\model\PfzAccount;
use app\common\model\Juser;
use app\common\service\Sign;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\facade\Validate;
use think\Request;

class FzAccount
{

    /**
     * @author liujiong
     * @Note  分账接收方列表
     */
    public function list(){
        $uid = getDecodeToken()['id'];
        $num = input('post.num/d','10','strip_tags');
        $where = [];

        $where['pid'] = $uid;
        $status = input('post.status');
        if ($status){
            $where['a.status'] = $status;
        }
        $name = input('post.name/s','','strip_tags');
        if ($name){
            $where['a.name'] = $name;
        }
        $uname = input('post.uname/s','','strip_tags');
        $phone = input('post.phone/s','','strip_tags');

        $state = input('post.state');
        if ($state){
            $where['a.state'] = $state;
        }
        $result = new PfzAccount();
        if($state == 1){
            if ($uname){
                $where['b.user_name'] = $uname;
            }
            if ($phone){
                $where['b.phone'] = $phone;
            }
        }elseif ($state == 2){
            if ($uname){
                $where['c.user_name'] = $uname;
            }
            if ($phone){
                $where['c.phone'] = $phone;
            }
        }elseif ($state == 3){
            if ($uname){
                $where['d.user_name'] = $uname;
            }
            if ($phone){
                $where['d.phone'] = $phone;
            }
        }

        $data = $result->alias('a')
            ->where($where)
            ->join('j_user b','b.id=a.uid and a.state = 1','LEFT')
            ->join('x_user c','c.id=a.uid and a.state = 2','LEFT')
            ->join('p_user d','d.id=a.uid and a.state = 3','LEFT')
            ->field('a.id,a.status,a.state,a.mch_id,a.sub_mch_id,a.create_time,a.name,a.account,a.type,a.relation_type,a.desc,b.user_name jname,b.phone jphone,c.user_name xname,c.phone xphone,d.user_name pname,d.phone pphone')
            ->paginate($num)
            ->toArray();

        return returnData(['data'=>$data,'code'=>'200']);

    }


}