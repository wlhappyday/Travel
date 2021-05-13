<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\model\Penterprise as P_enterprise;
use app\common\model\Padmin;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\facade\Validate;
use think\Request;

class Penterprise
{

    /**
     * @author liujiong
     * @Note  获取用户企业信息
     */
    public function list(){
        $num = input('post.num/d','10','strip_tags');
        $uname = input('post.uname/s','','strip_tags');
        $phone = input('post.phone/s','','strip_tags');
        $where = [];
        if ($uname){
            $where['d.user_name'] = $uname;
        }
        if ($phone){
            $where['d.phone'] = $phone;
        }

        $Jenterprise = new P_enterprise();
        $data = $Jenterprise->alias('a')
            ->join('file b','b.id = a.qualifications','LEFT')
            ->join('file c','c.id = a.special_qualifications','LEFT')
            ->join('P_admin d','d.id=a.uid','LEFT')
            ->where($where)
            ->field('a.title,a.content,a.code,a.representative,a.phone as representative_phone,a.email,b.file_path qualifications,c.file_path special_qualifications,a.address,d.user_name uname,d.phone')
            ->paginate($num);

        return returnData(['data'=>$data,'code'=>'200']);

    }


}