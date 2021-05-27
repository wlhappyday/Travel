<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\model\Jproduct;
use app\common\model\Juser;
use app\common\model\Xuser;
use app\common\model\JproductRecords;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\facade\Validate;
use think\Request;
use app\common\model\JproductClass;

class Product
{

    public function list(){
        $where = [];
        $type = input('post.type/d','','strip_tags');
        $name = input('post.name/s','','strip_tags');
        if ($name){
            $where['a.name'] = $name;
        }
        $class_name = input('post.class_name/s','','strip_tags');
        if ($class_name){
            $where['a.class_name'] = $class_name;
        }

        $status = input('post.status/d','','strip_tags');
        if ($status){
            $where['a.status'] = $status;
        }

        if($type == 1){
            $id = Juser::where(['a.status'=>'0'])->alias('a')->join('j_product b','b.uid=a.id and b.type=1')->column('b.id');
        }else if($type == 2){
            $id = Xuser::where(['a.status'=>'0'])->alias('a')->join('j_product b','b.uid=a.id and b.type=2')->column('b.id');
        }else {
            $jid = Juser::where(['a.status'=>'0'])->alias('a')->join('j_product b','b.uid=a.id and b.type=1')->column('b.id');
            $xid = Xuser::where(['a.status'=>'0'])->alias('a')->join('j_product b','b.uid=a.id and b.type=2')->column('b.id');
            $id = array_merge($jid,$xid);
        }
        $data = Jproduct::where($where)->alias('a')
            ->whereIn('a.id',$id)
            ->join('j_user b','b.id = a.uid and a.type = 1','left')
            ->join('x_user c','c.id = a.uid and a.type = 2','left')
            ->join('file d','d.id=a.first_id')
            ->field('a.id,a.type,a.name,a.class_name,a.title,a.money,a.number,a.end_time,a.desc,b.user_name j_name,c.user_name x_name,d.file_path')
            ->select();;

        return returnData(['data'=>$data,'code'=>'200']);
    }
}