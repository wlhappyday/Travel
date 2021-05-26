<?php
declare (strict_types = 1);

namespace app\platform\model;

use app\api\model\Xuser;
use app\common\model\Juser;
use think\Model;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\WithoutField;
use hg\apidoc\annotation\AddField;
use think\model\concern\SoftDelete;
/**
 * @mixin \think\Model
 */
class J_product extends Model
{
    //
    use SoftDelete;
    protected $name = 'j_product';
    protected $autoWriteTimestamp = true;
    protected $hidden=['pivot'];
    protected $json = ['img_id'];
    /**
     * @field("name,jq_name,mp_name,product_code,title,money,number,img_url,video_url")
     */
    public function scenic_spot($id){
        $res = $this->get($id);
        return $res;
    }
    /**
     * @field("name,yw_name,cx_name,jt_qname,jt_fname,xl_name,product_code,set_city,get_city,day,title,standard,end_day,address,money,number,img_url,video_url")
     */
    public function route($id){
        $res = $this->get($id);
        return $res;
    }

    public function juser(){
        if ($this['type']=='1'){
            return $this->hasOne(Juser::Class, 'id', 'uid');
        }else{
            return $this->hasOne(Xuser::class, 'id', 'uid');

        }

    }
}
