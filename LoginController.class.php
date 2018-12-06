<?php

namespace home\Controller;

use Think\Controller;

class LoginController extends Controller
{
    private $smsapi = "http://api.smsbao.com/";
    private $user   = 'Sasanana'; //账号
    private $pass   = 'a123456';  //密码
    private $checktime  = '3600';//3600秒内允许的发送短信次数
    private $checknum   = 10;     //允许发送四次
    private $expiretime = 14400;   //session有效时间单位秒


    public function login(){
        $this->display();
    }
 public function checkLogin(){
        if (!empty($_POST['username']) && !empty($_POST['phone'])) {
            $username = htmlspecialchars(trim($_POST['username']));
            $auser_were = array(
                'username' => $username,
            );
            $user_info = M('user')->where($auser_were)->find();
            if (empty($user_info) || $user_info['phone'] != $_POST['phone']) {
                echo json_encode(array('code'=>-1,'msg'=>'用户名或密码不正确！'));exit;
            }
            //更新sessionID
            session_regenerate_id();
            //登陆成功存储session
            $_SESSION['admin'] = $user_info;
            $_SESSION['expiretime'] = time() + $this->expiretime;//SESSION有效时间
            if (!empty($_SESSION)) {
                //$user_time = M('auser')->where($auser_were)->save(array('last_time'=>time()));
                //$this->redirect('Index/index');
                echo json_encode(array('code'=>0,'msg'=>'登陆成功！'));exit;
            } else {
                echo json_encode(array('code'=>-1,'msg'=>'系统错误！'));exit;
            }
        }else{
            if (empty($_POST['username'])){
                echo json_encode(array('code'=>-1,'msg'=>'账号不能为空！'));exit;
            }
            if (empty($_POST['phone'])){
                echo json_encode(array('code'=>-1,'msg'=>'密码不能为空！'));exit;
            }
        }
    }
    /**
     * 发送验证码
     */
    public function sendSms(){
        header('Access-Control-Allow-Origin:*');
        if (empty($_POST['username'])){
            echo json_encode(array('code'=>-1,'msg'=>'账号系统不存在！'));exit;
        }
        $user_info = M('auser')->where(array('username'=>htmlspecialchars(trim($_POST['username']))))->find();
        if (empty($user_info)){
            echo json_encode(array('code'=>-1,'msg'=>'账号系统不存在！'));exit;
        }
        $sms_phone = $user_info['userphone'];
        if (empty($sms_phone)){
            echo json_encode(array('code'=>-1,'msg'=>'此账号系统暂未绑定手机！'));exit;
        }
        if(!preg_match("/^1[34578]\d{9}$/", $sms_phone)){
            echo json_encode(array('code'=>-1,'msg'=>'绑定手机号码格式错误！'));exit;
        }

        //校验发送频率
        $time = time()-$this->checktime;
        $check_where = "sms_time >= '".$time."' and sms_state = 'Y'";
        $sms_check = M('smslog')->where($check_where)->select();
        if (count($sms_check) > $this->checknum) {
            echo json_encode(array('code'=>-1,'msg'=>'发送短信验证码次数超过上限！'));exit;
        }

        $send_code = rand(100,999).rand(999,100);
        $sms_msg = date("Y-m-d H:i:s",time()).",您的短信验证码：".$send_code.",在三十分钟内有效！";

        //发送短信验证码
        $result = $this->duanXinbao($sms_phone,$sms_msg);
        $statusStr = array(
            "0" => "短信发送成功",
            "-1" => "参数不全",
            "-2" => "服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间！",
            "30" => "密码错误",
            "40" => "账号不存在",
            "41" => "余额不足",
            "42" => "帐户已过期",
            "43" => "IP地址限制",
            "50" => "内容含有敏感词"
        );
        $sms_data = array(
            'sms_user' => htmlspecialchars(trim($_POST['username'])),
            'sms_phone'=> $sms_phone,
            'sms_time' => time(),
            'sms_url'  => $this->smsapi,
            'sms_requestip' => $_SERVER['REMOTE_ADDR'],
            'sms_code' => $send_code,
            'sms_content' => $statusStr[$result]
        );
        if ($result == '0'){
            $sms_data['sms_state'] = 'Y';
            M('smslog')->add($sms_data);
            echo json_encode(array('code'=>0,'msg'=>'发送成功，请注意查收！'));exit;
        }else{
            $sms_data['sms_state'] = 'N';
            M('smslog')->add($sms_data);
            echo json_encode(array('code'=>-1,'msg'=>'发送失败，请重新发送！'));exit;
        }


    }
    /**
     * 拼接短信接口
     */
    private function duanXinbao($sms_phone,$sms_msg){
        $sendurl = $this->smsapi."sms?u=".$this->user."&p=".md5($this->pass)."&m=".$sms_phone."&c=".urlencode($sms_msg);
        $result =file_get_contents($sendurl) ;
        return $result;
    }
    /**
     * 错误页面
     */
    public function loginExpiretime(){
        $this->display('404');
    }
    /**
     * 用户退出登录
     */
    function logout()
    {
        //session('admin', null);
        //清除session并释放资源
        session_unset();
        session_destroy();
        $this->redirect('login');
    }
}