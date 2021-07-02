<?php


namespace app\api\controller;


use app\api\model\Admin;
use app\api\model\Juser;
use app\api\model\Padmin;
use app\api\model\Puser;
use app\api\model\Xuser;
use app\common\model\File;
use app\common\model\Log;
use app\common\model\Puseruser;
use Exception;
use thans\jwt\facade\JWTAuth;
use think\facade\Db;
use think\Request;
use think\response\Json;

class Login
{
    public function login(): Json
    {
        $phone = input('post.phone', '123456', 'strip_tags');
        $username = input('post.username', '123456', 'strip_tags');
        $passwd = input('post.password', '123456', 'strip_tags');
        $type = input('post.type/d', '5');
        if ($username != null) {
            $where['user_name'] = $username;
        } elseif ($phone != null) {
            $where['phone'] = $phone;
        } else {
            return returnData(['msg' => '请输入完整账号密码', "code" => 201]);
        }
        if ($type != '1') {
            $where['status'] = '0';
        }
        switch ($type)
        {
            case 1:
                $userDate = $this->adminLogin($where);
                break;
            case 2:
                $userDate = $this->pAdmin($where);
                if (!empty($userDate)) {
                    $userDate['avatar'] = http() . File::where('id', $userDate['avatar'])->value('file_path');
                }
                break;
            case 3:
                $userDate = $this->jLogin($where);
                break;
            case 4:
                $userDate = $this->xLogin($where);
                break;
            case 5:
                $userDate = $this->pLogin($where);
                break;
            default:
                return returnData(["code" => 201, 'msg' => '非法参数']);
        }
        if (empty($userDate)) {
            return returnData(['msg' => '用户不存在', "code" => 201]);
        }
        if (!checkPasswd($passwd, $userDate)) {
            return returnData(['msg' => '账号密码错误', "code" => 201]);
        }
        $userDate = $userDate->toArray();
        $userInfo = [
            'userName' => $userDate['user_name'],
            'phone' => $userDate['phone'],
            'id' => $userDate['id'],
            'type' => $type,
            'avatar' => $userDate['avatar'] ?? '',
            'code' => 200,
        ];
        $logData = [
            'uid' => $userDate['id'],
            'user_name' => $userDate['user_name'],
            'type' => $type,
            'info' => '登录',
            'create_time' => time(),
            'ip' => getIp(1111)['ip'],
        ];
        Log::create($logData);
        return returnData($userInfo, 200, ['Authorization' => "Bearer " . JWTAuth::builder($userInfo), 'Access-Control-Expose-Headers' => "Authorization"]);
    }

    public static function sendRequest($url, $params = [], $method = 'POST', $options = [])
    {
        $method = strtoupper($method);
        $protocol = substr($url, 0, 5);
        $query_string = is_array($params) ? http_build_query($params) : $params;

        $ch = curl_init();
        $defaults = [];
        if ('GET' == $method) {
            $geturl = $query_string ? $url . (stripos($url, "?") !== FALSE ? "&" : "?") . $query_string : $url;
            $defaults[CURLOPT_URL] = $geturl;
        } else {
            $defaults[CURLOPT_URL] = $url;
            if ($method == 'POST') {
                $defaults[CURLOPT_POST] = 1;
            } else {
                $defaults[CURLOPT_CUSTOMREQUEST] = $method;
            }
            $defaults[CURLOPT_POSTFIELDS] = $query_string;
        }
        $defaults[CURLOPT_HEADER] = FALSE;
        $defaults[CURLOPT_USERAGENT] = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.98 Safari/537.36";
        $defaults[CURLOPT_FOLLOWLOCATION] = TRUE;
        $defaults[CURLOPT_RETURNTRANSFER] = TRUE;
        $defaults[CURLOPT_CONNECTTIMEOUT] = 5;
        $defaults[CURLOPT_TIMEOUT] = 5;
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        if ('https' == $protocol) {
            $defaults[CURLOPT_SSL_VERIFYPEER] = FALSE;
            $defaults[CURLOPT_SSL_VERIFYHOST] = FALSE;
        }

        curl_setopt_array($ch, (array)$options + $defaults);

        $ret = curl_exec($ch);
        $err = curl_error($ch);

        if (FALSE === $ret || !empty($err)) {
            $errno = curl_errno($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            return [
                'ret' => FALSE,
                'errno' => $errno,
                'msg' => $err,
                'info' => $info,
            ];
        } else {
            curl_close($ch);
            return [
                'ret' => TRUE,
                'msg' => $ret,
            ];
        }

    }


    public function adminLogin($where)
    {
        return Admin::where($where)->find();
    }

    public function jLogin($where)
    {
        return Juser::where($where)->find();
    }

    public function xLogin($where)
    {
        return Xuser::where($where)->find();
    }

    public function pAdmin($where)
    {
        return Padmin::where($where)->find();
    }

    public function pLogin($where)
    {
        return Puser::where($where)->find();
    }
    public function SignLogin(Request $request){
        if ($request->isPost()){
            $puseruser_id = $request->post('uid');
            $appid = $request->post('appid');
            $nickName = $request->post('nickName');
            $address = $request->post('address');
            $gender = $request->post('gender');
            $avatar = $request->post('avatar');
            $code = $request->post('code');
            $type = $request->post('type');
            if ($appid && $nickName && $address && $gender && $avatar && $code) {
                $user = Puser::where(['appid'=>$appid])->field('appkey,id')->find();
                if ($user){
                    $secret =Puser::where(['appid'=>$appid])->value('appkey');
                    $session_key = json_decode(httpGet("https://api.weixin.qq.com/sns/jscode2session?appid=".$appid."&secret=".$secret."&js_code=".$code."&grant_type=authorization_code"),true);
//                    dd($session_key);
                    Db::startTrans();

                    try{
                        $uid = Puseruser::where(['openid'=>$session_key['openid'],'appid'=>$appid])->find();

                        if ($uid){

                            $save['appid']= $uid['appid'];
                            $save['nickname'] = $uid['nickname'];
                            $save['sex'] = $uid['sex'];
                            $save['address'] = $uid['address'];
                            $save['avatar'] = $uid['avatar'];
                            $save['openid']=$uid['openid'];
                            $save['puser_id'] = $uid['id'];
                            $uid->last_time = time();
                            $uid->save();
                        }else{
                            $Puseruser = new Puseruser;
                            $save['appid']= $appid;
                            $save['nickname'] = $nickName;
                            $save['sex'] = $gender;
                            $save['address'] = $address;
                            $save['avatar'] = $avatar;
                            $save['type'] = '1';
                            $save['openid'] = $session_key['openid'];
                            $save['last_time'] = time();
                            $Puseruser->save($save);
                            $save['puser_id'] = $Puseruser['id'];
                        }
                        DB::commit();
                        $save['type'] = '6';
                        header('Authorization:' . "Bearer " . JWTAuth::builder($save));
                        return json(['code' => '200', 'msg' => '操作成功', 'session_key' => $session_key]);
                    } catch (Exception $e) {
                        Db::rollback();
                        return json(['code' => '-1', 'msg' => $e->getMessage()]);
                    }
                }else{
                    return json(['code'=>'-1','msg'=>'门店不存在']);
                }
            }else{
                if ($appid){
                    $user = Puser::where(['appid'=>$appid])->find();
                    if ($user){
                        $save['appid']=$appid;
                        $save['type']=$type;
                        header('Authorization:'."Bearer " . JWTAuth::builder($save));
                        return json(['code'=>'200','msg'=>'操作成功']);
                    }
                    return json(['code'=>'-1','msg'=>'抱歉，门店不存在']);
                }
                return json(['code'=>'-1','msg'=>'请检查参数']);
            }
        }else{
            return json(['code'=>'-1','msg'=>'请使用Post提交']);
        }
    }
}