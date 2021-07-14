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

    public function productRecord(){

        $num = input('post.num/d','10','strip_tags');
        $type = input('post.type/d','','strip_tags');
        $uname = input('post.uname/s','','strip_tags');
        $phone = input('post.phone/s','','strip_tags');
        $where = [];
        $where['b.type'] = '1';
        if($type){
            $where['a.type'] = $type;
        }
        $name = input('post.name/s','','strip_tags');
        if ($name){
            $where['b.name'] = $name;
        }

        if ($uname){
            $where['c.user_name'] = $uname;
        }
        if ($phone){
            $where['c.phone'] = $phone;
        }

        $balance_result = new JproductRecords();
        $data = $balance_result->alias('a')->where($where)
            ->join('j_product b','b.id=a.product_id','LEFT')
            ->join('J_user c','c.id=b.uid','LEFT')
            ->field('a.id,a.type,a.before_number,a.number,a.after_number,a.descript,a.create_time,b.name,b.class_name,c.user_name,c.phone')
            ->paginate($num)->toArray();
        return returnData(['data'=>$data,'code'=>'200']);
    }
}