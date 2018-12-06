<?php

namespace Home\Controller;

use Common\Controller\BaseController;
use Think\Controller;
use Think\Page;
class UserController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        if (empty($_SESSION['admin']['username'])){
            redirect(U('Login/dfe9f69aa0596fd3'));
        }
        $data = array(
            'request_host'       => $_SERVER['REQUEST_URI'],
            'request_person'  => $_SESSION['admin']['username'],
            'request_ip'      => $_SERVER['REMOTE_ADDR'],
            'request_content' => CONTROLLER_NAME.'/'.ACTION_NAME,
            'request_time'    => date('Y-m-d H:i:s',time()),
        );
        insert_log($data);
    }
    public function index()
    {
        $s_time = strtotime($_POST['s_time']);
        $e_time = strtotime($_POST['e_time']);
        $username = htmlspecialchars(trim($_POST['username']));
        $phone = $_POST['phone'];
        $where = "1=1";
        if (!empty($s_time)) {
            $where .= " and regtime >= '" . $s_time . "'";
            $this->assign('s_time', $_POST['s_time']);
        }
        if (!empty($e_time)) {
            $where .= " and regtime < '" . $e_time . "'";
            $this->assign('e_time', $_POST['e_time']);
        }
        if (!empty($username)) {
            $where .= " and username like '%" . $username . "%'";
            $this->assign('username', $username);
        }
        if (!empty($phone)) {
            $where .= " and phone = '" . $phone . "'";
            $this->assign('phone', $phone);
        }
        if (empty($_GET['order_by'])){
            $soke = 'id DESC';
        }else{
            if($_GET['order_by'] == 'g_asc'){
                $soke = "gcoin ASC";
            }
            if($_GET['order_by'] == 'g_desc'){
                $soke = "gcoin DESC";
            }
            if($_GET['order_by'] == 'u_asc'){
                $soke = "ucoin ASC";
            }
            if($_GET['order_by'] == 'u_desc'){
                $soke = "ucoin DESC";
            }
            if($_GET['order_by'] == 'is_vip'){
                $soke = "isvip ASC";
            }
            if($_GET['order_by'] == 'status1'){
                $soke = "status =0";
            }
            if($_GET['order_by'] == 'status2'){
                $soke = "status =1";
            }

        }
        $car = M("user");
        $count = $car->count();// 查询满足要求的总记录数
        $Page = new \Think\Page($count, 50);// 实例化分页类 传入总记录数和每页显示的记录数(50)
        $show = $Page->show();// 分页显示输出
        $list = $car->field(" *,CASE isvip WHEN '0' THEN '普通会员' WHEN '1' THEN 'VIP会员' END
as isvip,CASE status WHEN '0' THEN '正常' WHEN '1' THEN '冻结' END
as status,FROM_UNIXTIME(regtime, '%Y-%m-%d %H:%i:%S') AS regtime")->where($where)->order($soke)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $num = $car->where($where)->count();

        if(empty($_GET['p'])){
            $_GET['p']=1;
        }
        $order=$count-50*($_GET['p']-1);
        foreach ($list as $k => $v) {
            $id = $list[$k]['topid'];
            $list1 = $car->field("username")->where("id = $id")->find();
            $topusername = $list1['username'];
            $list[$k]['topusername'] = $topusername;
            $list[$k]['order'] = $order;
            $order--;
        }
        $this->assign('page', $show);// 赋值分页输出
        $this->assign('list', $list);

        //获取当天凌晨时间戳
        $today = strtotime(date("Y-m-d"),time());
        $counts = $car->field("count(*) as count")->find();
        $counts_vip = $car->field("count(*) as count")->where(array('isvip'=>1))->find();
        $counts_day = $car->field("count(*) as count")->where("regtime >= $today")->find();
        $this->assign('counts', $counts);//会员总数
        $this->assign('counts_vip', $counts_vip);//vip会员数
        $this->assign('counts_day', $counts_day);//当天注册人数
        $this->display();
    }
    //修改用户名密码
    public function chanxiupasword()
    {
        if (empty($_POST)){
            $this->error("参数错误");

        }else{
            if (empty($_POST['username']))
            {
                $this->error("参数错误");
            }else
            {
                $arr = array();
                if ($_POST['password'])
                {
                    $arr['password'] = md5($_POST['password']);
         }
                if ($_POST['transaction'])
                {
                    $arr['transaction'] = md5($_POST['transaction']);
         }
                $car = M("user");
                $save_user = $car->where(array('username' => $_POST['username']))->save($arr);
                if ($save_user) {
                    $this->success("修改完成");
                } else {
                    $this->error("修改失败");
                }
            }
        }
    }

    public function updateUser()
    {
        if (empty($_GET)){
            $this->ajaxReturn(array('code' => -1, 'mgs' => '参数错误'));

        }else{
            $this->assign('id', $_GET['id']);
            $this->display();
        }
    }
    //修改
    public function updateUsers()
    {
        if (empty($_POST['id'])){
            $this->ajaxReturn(array('code' => -1, 'mgs' => '参数错误'));

        }else{
            $arr = array();
            if (!empty($_POST['phone'])) {
                $arr['phone'] = trim($_POST['phone']);
            }
            if ($_POST['level'] !== '') {
                $arr['isvip'] = trim($_POST['level']);
            }
            if (empty($arr)){
                echo json_encode(array('code'=>-1,'msg'=>'参数错误'));exit;
            }
            $car = M("user");
            $id = $_POST['id'];
            $save_user = $car->where(array('id'=>$id))->save($arr);
            if ($save_user) {
                echo json_encode(array('code'=>1,'msg'=>'编辑成功'));exit;
            } else {
                echo json_encode(array('code'=>-1,'msg'=>'编辑失败'));exit;
            }

        }
    }
    //转入记录
    public function buy()
    {
        $User = M("coinlog");
        $username = $_GET['username'];
        $m = M('coinlog');
        $where = "type=2";
        if(!empty($username)){
            $where = array('username'=>$username,'type'=>2);
            $this->assign('username',$username);//赋值条件
        }
        $count = $m->where($where)->count();
        $p = getpage($count,50);
        $list = $m->field(" *,FROM_UNIXTIME(updated_at, '%Y-%m-%d %H:%i:%S') AS updated_at")->where($where)->order('id DESC')->limit($p->firstRow, $p->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $p->show()); // 赋值分页输出
        $this->display();
    }
    public function searchList(){
        $where = "1=1";
        $car = M("user");
        $page  = ($_POST['currPage']-1)*$_POST['limit'];
        $limit = $_POST['limit'];
        $field = "*,CASE isvip WHEN '0' THEN '普通会员' WHEN '1' THEN 'VIP会员' END as isvip,FROM_UNIXTIME(regtime, '%Y-%m-%d %H:%i:%S') AS regtime";
        $list = $car->field($field)->where($where)->limit($page,$limit)->select();
        $num = count($list);
        echo json_encode(array('data'=>$list));exit;

    }
    function find()
    {
        $id = $_POST['uid'];
        $car = M("user");
        $list = $car->field(" *,CASE isvip WHEN '0' THEN '普通会员' WHEN '1' THEN 'VIP会员' END
as isvip,FROM_UNIXTIME(regtime, '%Y-%m-%d %H:%i:%S') AS regtime FROM ub_user;")->where(array('id'=>$id))->find();
        $this->assign('list', $list);
        $this->display();
    }


    //添加用户
    function addUser()
    {
        if (!IS_POST) {
            $this->display();
        } else {
            $model = M('user');
            $_POST['password']=md5( $_POST['password']);
            $_POST['transaction']=md5( $_POST['transaction']);
            $_POST['regtime']=time();
            $topid=$_POST['topid'];
            $username =$_POST['username'];
            $area = $_POST['area'];
            $phone = $_POST['phone'];
            if ($_POST['username'] && $_POST['phone']){
                $where = "username = '".$username."' OR phone = '".$phone."'";
                $user_info = $model->where($where)->find();
                if (!empty($user_info)){
                    $this->error("用户名或手机号已存在");
                }
            }
            if (empty($_POST['topid'])) {
                $_POST['topid'] = 0;
            } else {
                $user_info = $model->where("username = '$topid'")->find();
                $_POST['topid'] = $user_info['id'];
                if (!$user_info) {
                    $this->error("邀请码不存在");
                }
            }
            //转账二维码
            $json = array(
                'username'=>$_POST['username'],
            );
            $json = json_encode($json);
            $filename = "upload/".$_POST['username'].".png";
            $this->qrcode($json,$filename);

            $_POST['qrcode']="trade/".$filename;

            //分享二维码
            $url = "http://www.u-trade.top/reg.php?username=".$_POST['username'];
            $filename1 = "upload/".$_POST['username']."2.png";
            $this->qrcode($url,$filename1);

            $_POST['shareqrcode']="trade/".$filename1;

            if ($model->add($_POST)) {
                $this->success("用户添加成功!");
            } else {
                $this->error($model->getError());
            }
        }
    }

    // 冻结/解冻用户
    public function lockUser()
    {
        if (empty($_GET['id']) || $_GET['status'] === ''){
            $this->error("参数错误!");
        }else{
            $user_state = M('user')->where(array('id'=>$_GET['id']))->save(array('status'=>$_GET['status'],'token'=>0));

            if ($user_state) {

                $this->success("操作成功");
            } else {
                $this->error(M()->getError());
            }
        }
    }
//修改用户
    public function editUser()
    {
        if (IS_GET) {
            $id = $_GET['id'];
            $list = M('user')->find($id);
            $this->assign("list", $list);
            $this->display();
        } else {
            $model = M('user');
            if ($model->create()) {
                if ($model->save()) {
                    $this->success("用户修改成功!");
                } else {
                    $this->error($model->getError());
                }
            } else {
                $this->error($model->getError());
            }
        }
    }
//推荐关系图
    public function schema()
    {
        $user = M('user');
        $where = "1=1";
        if (!empty($_GET['id'])){
            $id_info = $user->field("id,topid")->where("id = '".$_GET['id']."'")->find();
            $where =" id = '".$_GET['id']."' or topid = '".$id_info['topid']."' or topid = '0' or id = '".$id_info['topid']."' ";
            $this->assign('ids',$_GET['id']);
        }
        $topuser_select =$user->field('id,username as name,topid,nickname')->select();
        $topuser =$user->field('id,username as name,topid as pId,nickname')->where($where)->select();
        foreach ($topuser as $k =>$v){
            if ($v['pid'] == 0){
                $topuser[$k]['isParent'] = "true";
            }else{
                $topuser[$k]['isParent'] = "";
            }
            if (!empty($_GET['id']) && $v['id'] == $id_info['topid'] ){
                $topuser[$k]['open'] = "true";

            }
            if(!empty($_GET['id']) && $v['id'] == $id_info['id']){
                $topuser[$k]['font'] = "{color:blue}";
            }
            $topuser[$k]['pId'] = $v['pid'];
            $topuser[$k]['name'] = $v['name'].' '.$v['nickname'];
            unset($topuser[$k]['pid']);
        }
        $topusers = json_encode($topuser);
        $this->assign('topusers',$topusers);
        $this->assign('topuser',$topuser_select);
        $this->display();
    }


//冻结用户
    function dongjie()
    {

        if (empty($_GET['id']) || $_GET['status'] === ''){
            $this->error("参数错误!");
        }else{
            $user_state = M('user')->where(array('id'=>$_GET['id']))->save(array('status'=>$_GET['status'],'token'=>0));

            if ($user_state) {

                $this->success("操作成功");
            } else {
                $this->error(M()->getError());
            }
        }

    }
    function clearing()
    {
        $prefix = C('ub');//数据库表前缀
        $wh['sd.is_clear'] = 0;
        $data = array();
        $list = M('sign_day sd')->join('left join ' . $prefix . 'user u on u.id = sd.user_id')->where($wh)->field('sd.sign_time,sd.money,u.nickname')->select();
        foreach ($list as $k => $value) {
            $data[$value['nickname']][] = $value;
            $data[$value['nickname']][date('Y-m', $value['sign_time'])]['list'][] = $value;
            $data[$value['nickname']][date('Y-m', $value['sign_time'])]['count'] += $value['money'];
            $data[$value['nickname']]['allCount'] += $value['money'];
        }
        foreach ($data as $kk => $dataItem) {
            foreach ($dataItem as $kkk => $item) {
                $key = (int)$kkk;
                unset($data[$kk][$key]);
            }
        }
        //dump($data);
        $this->assign('data', $data);
        $this->display();
    }


    //买入记录
    public function buy1()
    {

        $inusername = $_GET['inusername'];
        $m = M('paylog');
        $where = "1=1";
        if(!empty($inusername)){
            $where = array('inusername'=>$inusername);
            $this->assign('inusername',$inusername);//赋值条件1
        }
        $count = $m->where($where)->count();
        $p = getpage($count,50);
        $list = $m->field("*,CASE status2 WHEN '0' THEN '挂单中' WHEN '1' THEN '等待付款' WHEN '2' THEN '已确认付款' END
as status2,FROM_UNIXTIME(over_at, '%Y-%m-%d %H:%i:%S') AS over_at,FROM_UNIXTIME(begin_at, '%Y-%m-%d %H:%i:%S') AS begin_at")->where($where)->order('id DESC')->limit($p->firstRow, $p->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $p->show()); // 赋值分页输出
        $this->display();
    }
    //记录详情
    public function selectDetial()
    {
        $coinlog = M("coinlog");
        $where = "username = '".$_GET['username']."'";
        if (!empty($_GET['type'])){
            $where.=" and type = '".$_GET['type']."'";
        }
        $page_s = ($_GET['page']-1)*$_GET['limit'];
        $page_e = $_GET['limit'];
        $coinlog_list = $coinlog->field("*,FROM_UNIXTIME(updated_at, '%Y-%m-%d %H:%i:%S') AS updated_at")->where($where)->order('id DESC')->limit($page_s,$page_e)->select();
        $coinlog_count = $coinlog->where($where)->select();
        $num = count($coinlog_count);
        echo json_encode(array('code'=>0,'msg'=>'','count'=>$num,'data'=>$coinlog_list));
    }
//新窗口
    public function windowOpen()
    {
        if (!empty($_GET['username']) && $_GET['type'] == 1){
            $this->assign('username', $_GET['username']);
        }
        $this->display();
    }
//新窗口2
    public function windowOpen2()
    {
        if (!empty($_GET['username'])){
            $user = M('user');
            $field = "*,CASE isvip WHEN '0' THEN '普通会员' WHEN '1' THEN 'VIP会员' END
      as isvip,CASE status WHEN '0' THEN '正常' WHEN '1' THEN '冻结' END
      as status,FROM_UNIXTIME(regtime, '%Y-%m-%d %H:%i:%S') AS regtime";
            $user_info = $user->field($field)->where(array('username'=>$_GET['username']))->find();
            $this->assign('username', $_GET['username']);
            $this->assign('user_info', $user_info);
        }
        $this->display();
    }
//U总数
    public function gettotalNum()
    {
        $coinlog = M("coinlog");
        if (!empty($_POST['username']) && !empty($_POST['type'])){
            $where = array('username'=>$_POST['username'],'type'=>$_POST['type']);
            $tatal_Num = $coinlog->where($where)->sum('ucoin');
            echo json_encode(array('code'=>1,'data'=>$tatal_Num));
        }
    }
    //卖出记录
    public function sale1()
    {

        $User = M("paylog");
        $inusername = $_GET['outusername'];
        $m = M('paylog');
        $where = "1=1";
        if(!empty($inusername)){
            $where = array('outusername'=>$inusername);
            $this->assign('outusername',$inusername);//赋值条件1
        }
        $count = $m->where($where)->count();
        $p = getpage($count,50);
        $list = $m->field("*,CASE status2 WHEN '0' THEN '挂单中' WHEN '1' THEN '等待付款' WHEN '2' THEN '已确认付款' END
as status2,FROM_UNIXTIME(over_at, '%Y-%m-%d %H:%i:%S') AS over_at,FROM_UNIXTIME(begin_at, '%Y-%m-%d %H:%i:%S') AS begin_at")->where($where)->order('id DESC')->limit($p->firstRow, $p->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $p->show()); // 赋值分页输出
        $this->display();
    }
    function doClearing()
    {
        $model = M('sign_day');
        $model->is_clear = 1;
        $wh['is_clear'] = 0;
        $model->where($wh)->save();
    }
    public function info()
    {
        $prefix = C('ub_');//数据库表前缀
        $wh['sd.is_clear'] = 0;
        $data = array();
        $list = M('user')->field(" *,CASE isvip WHEN '0' THEN '普通会员' WHEN '1' THEN 'VIP会员' END
as isvip,FROM_UNIXTIME(regtime, '%Y-%m-%d %H:%i:%S') AS regtime FROM ub_user;")->select();
        foreach ($list as $k => $value) {
            $data[$value['id']][] = $value;
            $data[$value['topid']]= $value;
            $data[$value['username']]= $value;
            $data[$value['nickname']]= $value;
        }
        foreach ($data as $kk => $dataItem) {
            foreach ($dataItem as $kkk => $item) {
                $key = (int)$kkk;
                unset($data[$kk][$key]);
            }
        }
        //dump($data);

        $info=array();
        $info[0]=array();
        $i=1;
        foreach($data as $k=>$v)
        {
            $info[$i]['name']=$k;
            foreach ($v as $k1=>$v1)
            {
                if($k1!='allCount')
                {
                    $info[$i]['date'].=$k1.'/';
                }
            }
            $info[$i]['money']=$v['allCount'];

            $i++;
        }
        $count=M('sign_day')->query('select sum(money) as s from z_sign_day where is_clear<>1');
        $allcount=$count[0]['s'];
        $info[count($info)]=array('','总金额',"$allcount",'');
        create_xls($info,"用户信息");

    }
    public function qrcode($url,$filename,$level=3,$size=4){
        Vendor('phpqrcode');
        $errorCorrectionLevel =intval($level) ;//容错级别
        $matrixPointSize = intval($size);//生成图片大小
//生成二维码图片
        //echo $_SERVER['REQUEST_URI'];
        $object = new \QRcode();
        $object->png($url,$filename,$errorCorrectionLevel, $matrixPointSize, 2);
    }

}

