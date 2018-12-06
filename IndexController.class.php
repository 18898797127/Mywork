<?php

namespace Home\Controller;

use Common\Controller\BaseController;
use Think\Page;
use Think\Rili;

class IndexController extends BaseController
{
	private $ub = array();
    public function __construct()
    {
        parent::__construct();
        $data = array(
            'request_host'       => $_SERVER['REQUEST_URI'],
            'request_person'  => $_SESSION['admin']['username'],
            'request_ip'      => $_SERVER['REMOTE_ADDR'],
            'request_content' => CONTROLLER_NAME.'/'.ACTION_NAME,
            'request_time'    => date('Y-m-d H:i:s',time()),
        );
        if(empty($_SESSION['admin'])){
        	redirect(U('Login/login'));
        }
        insert_log($data);
    }
/*    //析构函数
    public function __construct() {
        parent::__construct();
        var_dump($_GET);
    }*/
    public function index()
    {
        $this->display();
    }

    function inSign()
    {
        $date=date('Y-m',time());

        //该时间戳
        $times=strtotime($date);
        //下月时间戳
        $next=date('Y-m',strtotime("$date +1month"));
        $nexttime=strtotime($next);

        $info=M('sign_day')->where("user_id={$_SESSION['admin']['id']} and sign_time>=$times and sign_time<$nexttime")->select();
        $a=array();
        foreach($info as $k=>$v)
        {
            $a[date('Y-m-d',$v['sign_time'])]=$v['is_clear'];
        }
        /*dump($a);*/
        $link='';
        $link.='<div style="text-align:center;margin:auto"><br><br><br><table  width="1200" align="center" style="border-collapse:separate; border-spacing:0;border:1px solid #c6c6c6">
          <thead>
            <tr >
            <th colspan="7" height="40px" align="center"><button style="float: left" id="pre">上月</button><span id="ym">'.$date.'</span><button id="nex" style="float: right;display: none" >下月</button></th>
               
            </tr>
            <tr>
              <td>日</td>
              <td>一</td>
              <td>二</td>
              <td>三</td>
              <td>四</td>
              <td>五</td>
              <td>六</td>
            </tr>
          </thead>
          <tbody>';
        $date_array = explode('-', $date);
        $start_week = 0;//从星期天开始为0
        $month = cal_days_in_month(CAL_GREGORIAN, $date_array[1], $date_array[0]);//当月的天数
        $wstar = date('w', strtotime($date . '-01'));//当月从星期几天始
        $rows = ceil(($wstar + $month) / 7);//总行数
        $mday = 1;//第几天
        for ($i = 0; $i < $rows; $i++) {
            $link.='<tr>';
            for ($d = 0; $d < 7; $d++) {
                $nowday = 7 * $i + $d + $start_week;
                if ($nowday >= $wstar && $mday <= $month) {
                    $temp = date('d', strtotime($date . '-' . $mday));


                    if(array_key_exists($date.'-'.$temp,$a))
                    {
                        if($a[$date.'-'.$temp]==1){$link.='<td>'.$temp. '<span class=\'notic\'><span style="color: red">已结算</span></span></td>';}else{$link.='<td>'.$temp. '<span class=\'notic\'><span style="color: green">未结算</span></span></td>';}
                    }else{
                        if(strtotime($date.'-'.$temp)>=(time()-604800)&&strtotime($date.'-'.$temp)<=time())
                        {
                            $link.=  '<td>'.$temp. '<span style=\'\' class=\'notic\'><button type=\'button\' class=\'btn btn-success\' name="bc"></button></span></td>';
                        }
                        else
                        {
                            $link.=  '<td>'.$temp. '</td>';
                        }
                    }
                    $mday++;
                } else {
                    $link.= '<td> </td>';
                }
            }
            $link.= '</tr>';
        }
        $link.= '</tbody></table></div>';

        $this->assign('rili',$link);
        $this->display();
    }
    //数据查看
    public function dataview()
    {
        $m = M('user');
        $d = M("coinlog");
        $g = M("admincoinlog");
        $where = "type=6";
        $where1 = "type=3";
        $where2 = "type=4";
        $duiub = $d->where($where)->sum('ucoin');
        $gqub = $g->where($where1)->sum('ucoin');
        $gsub = $g->where($where2)->sum('ucoin');
        $gzhiub = $gqub + $gsub;
        $this->assign('duiub',abs($duiub));//兑换成g值的U币
        $this->assign('gzhiub',$gzhiub);//签到加速的U币
        $this->display();
    }
    function signDetail()
    {
        $user = session('admin');
        $id = intval(trim($user['id']));
        if ($id > 0) {
            $where['user_id'] = $id;
            $list = M('sign_day')->where($where)->order('id')->select();
            $dataList = array();
            foreach ($list as $listItem) {
                $dataList[] = array('signDay' => date('Y.m.d', $listItem['sign_time']), 'money' => $listItem['money'],'is_clear'=>$listItem['is_clear']);
            }
            $data['dayList'] = $dataList;
            $data['start_date'] = C('START_DATE');
            $this->ajaxReturn($data);
        }
    }
//INSERT INTO `ub_user` VALUES ('103972', '103600', '刘先生', 'qwe123', 'dc483e80a7a0bd9ef71d8cf973673924', '8092cef113b4aab5063015505d399069', 'e10adc3949ba59abbe56e057f20f883e', '86', '13424306661', '0.00', '0.00', '0.00', null, 'public/upload/qwe123.png', null, '0', '1536672689', '1536672707', '116.24.96.182', '0', 'public/upload/qwe1232.png', null);
    function allSignDetail()
    {
        $user = session('admin');
        $id = intval(trim($user['id']));
        if ($id > 0) {
            $where['user_id'] = $id;
            $count=M('sign_day')->where($where)->count();
            $pager=new Page($count,C('PAGE_SIZE'));
            $list = M('sign_day')->where($where)->order('is_clear asc, sign_time desc')->limit($pager->firstRow.','.$pager->listRows)->select();
            $allmoney=M('sign_day')->where("user_id=$id and is_clear=0")->sum('money');
            $this->assign('allmoney',$allmoney);
            $this->assign('page',$pager->show());
            $this->assign('list', $list);
            $this->display();
        }
    }
    //银行卡
    function bankcard()
    {

        $username = $_GET['realname'];
        $m = M('bankcard');
        $where = array();
        if(!empty($username)){
            $where = array('user.username'=>$username);
            $this->assign('realname',$username);//赋值条件
        }
        $count = M()->table('ub_bankcard as bank')->join('ub_user as user on bank.uid = user.id')->where($where)->select();
        $p = getpage(count($count),50);
        $list = M()->table('ub_bankcard as bank')->join('ub_user as user on bank.uid = user.id')->where($where)->order('bank.id DESC')->limit($p->firstRow, $p->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $p->show()); // 赋值分页输出
        $this->display();
    }
//转出记录
    function sale()
    {
        $username = $_GET['username'];
        $m = M('coinlog');
        $where = "type=1";
        if(!empty($username)){
            $where = array('username'=>$username,'type'=>1);
            $this->assign('username',$username);//赋值条件
        }
        $count = $m->where($where)->count();
        $p = getpage($count,50);
        $list = $m->field(" *,FROM_UNIXTIME(updated_at, '%Y-%m-%d %H:%i:%S') AS updated_at")->where($where)->order('id DESC')->limit($p->firstRow, $p->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $p->show()); // 赋值分页输出

        $sumub = $m->field(" *,FROM_UNIXTIME(updated_at, '%Y-%m-%d %H:%i:%S') AS updated_at")->where($where)->order('id DESC')->limit($p->firstRow, $p->listRows)->sum('ucoin');

        $this->assign('sumub',abs($sumub));//转出的ub
        $this->display();
    }
    //top转出记录
    public function topExport(){
        $d = M("coinlog");
        $u = M("user");
        $u1 = 'richard';
        $u2 ='9999';
        $u3 = 'qwe123';
        $u4 = '77177';
        $u1ub = $u->where(array('username'=>$u1))->sum('ucoin');
        $u2ub = $u->where(array('username'=>$u2))->sum('ucoin');
        $u3ub = $u->where(array('username'=>$u3))->sum('ucoin');
        $u4ub = $u->where(array('username'=>$u4))->sum('ucoin');
        $where = "username in('Richard',9999,'qwe123',77177) and type=1";
        $coinub = $d->where($where)->sum('ucoin');
        $this->assign('coinub',abs($coinub));
        $this->assign('u1ub',$u1ub);
        $this->assign('u2ub',$u2ub);
        $this->assign('u3ub',$u3ub);
        $this->assign('u4ub',$u4ub);
       $this->display();
    }

    public function gettotalNum(){
        $d = M("coinlog");
        $where = "username in('Richard',9999,'qwe123',77177) and type=1";//"and username = 'Richard' OR username = '9999' OR username = 'qwe123' OR username = '77177'";
        if (!empty($_POST['s_time'])){
            $where.=" and updated_at > '".strtotime($_POST['s_time'])."'";
        }
        if (!empty($_POST['e_time'])){
            $where.=" and updated_at <= '".strtotime($_POST['e_time'])."'";
        }
        if (!empty($_POST['username'])){
            $where.=" and username = '".$_POST['username']."'";
        }
        $tatal_Num = $d->where($where)->sum('ucoin');
        echo json_encode(array('code'=>1,'data'=>$tatal_Num));
    }
    public function gettopExport(){
        $d = M("coinlog");
        $where = "username in('Richard',9999,'qwe123',77177) and type=1";//"and username = 'Richard' OR username = '9999' OR username = 'qwe123' OR username = '77177'";
        if (!empty($_GET['s_time'])){
            $where.=" and updated_at > '".strtotime($_GET['s_time'])."'";
        }
        if (!empty($_GET['e_time'])){
            $where.=" and updated_at <= '".strtotime($_GET['e_time'])."'";
        }
        if (!empty($_GET['username'])){
            $where.=" and username = '".$_GET['username']."'";
        }
        $page_s = ($_GET['page']-1)*$_GET['limit'];
        $page_e = $_GET['limit'];
        $coinlog_list = $d->where($where)->order('updated_at DESC' )->limit($page_s,$page_e)->select();
        if (!empty($coinlog_list)){
            foreach ($coinlog_list as $key=>$list){
                $coinlog_list[$key]['updated_at'] = date("Y-m-d H:i:s",$list['updated_at']);
            }
        }
        $coinlog = $d->field("id")->where($where)->order('updated_at desc')->select();
        $num = count($coinlog);
        echo json_encode(array('code'=>0,'msg'=>'','count'=>$num,'data'=>$coinlog_list));
    }
    public function getparentLeve(){
        $this->display();
    }
    //兑换
    function duihuan()
    {
        $User = M("coinlog");
        $username = $_GET['username'];
        $m = M('coinlog');
        $where = "type=6";
        if(!empty($username)){
            $where = array('username'=>$username,'type'=>6);
            $this->assign('username',$username);//赋值条件
        }
        $count = $m->where($where)->count();
        $p = getpage($count,50);
        $list = $m->field(" *,FROM_UNIXTIME(updated_at, '%Y-%m-%d %H:%i:%S') AS updated_at")->where($where)->order('id DESC')->limit($p->firstRow, $p->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $p->show()); // 赋值分页输出
        $this->display();
    }

//添加公告
    public function sendMessage(){
        $model = M('message');
        $content =$_POST['content'] ? $_POST['content'] : '';
        if (empty($content)){
            echo json_encode(array('code'=>-1,'msg'=>'公告信息为空'));exit;
        }
        $content_info = $model->add(array('content'=>$content, 'addtime'=>time(),'type'=>1));
        if ($content_info){
            echo json_encode(array('code'=>1,'msg'=>'发布成功'));exit;
        }else{
            echo json_encode(array('code'=>-1,'msg'=>'发布失败'));exit;
        }
    }
    //留言管理
    public function advice()
    {
        $buy = M("advice");
        $username = $_GET['username'];
        $count      = $buy->count();// 查询满足要求的总记录数
        $Page       = new \Think\Page($count,50);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();// 分页显示输出
        if(!empty($username)){
            $list = $buy->where(array('username'=>$username))->field(" *,FROM_UNIXTIME(addtime, '%Y-%m-%d %H:%i:%S') AS addtime")->order('id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        }else{
            $list = $buy->field(" *,FROM_UNIXTIME(addtime, '%Y-%m-%d %H:%i:%S') AS addtime")->order('id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        }

        if (!empty($list)){
            foreach ($list as $k=>$val){
                $arr = explode(',',$val['picurl']);

                $count = count(explode(',',$val['picurl']));
                if ($count){
                    $list[$k]['picurl']=$arr;
                }
            }
        }
        $path_url ='http://www.u-trade.top/';
        $this->assign('path_url', $path_url);
        $this->assign('list', $list);
        $this->assign('page',$show);
        $this->display();
    }
//会员记录
    function details()
    {
        $User = M("admincoinlog");
        $username = $_GET['username'];
        $m = M('admincoinlog');
        $where = "1=1";
        if(!empty($username)){
            $where = array('username'=>$username);
            $this->assign('username',$username);//赋值条件
        }
        $count = $m->where($where)->count();
        $p = getpage($count,50);
        $list = $m->field(" *,FROM_UNIXTIME(updated_at, '%Y-%m-%d %H:%i:%S') AS updated_at")->where($where)->order('id DESC')->limit($p->firstRow, $p->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $p->show()); // 赋值分页输出
        $this->display();
    }
//系统参数设置

//添加U币
    function addu(){
        if (empty($_POST)){
            $this->display();
        }else{
            $model = M('user');
            $username =$_POST['username'];
            $user_info = $model->where(array('username'=>$_POST['username']))->find();
            $ub = $_POST['ucoin'] ? $_POST['ucoin'] : '';
            $gb = $_POST['gcoin'] ? $_POST['gcoin'] : '';
            $gball = $_POST['gcoin'] ? $_POST['gcoin'] : '';
            //当U币与G值同时存在
            if (!empty($ub) && !empty($gb)) {
                $arr = array(
                    'ucoin' => ($user_info['ucoin'] ? $user_info['ucoin'] : 0)+$ub,
                    'gcoin' => ($user_info['gcoin'] ? $user_info['gcoin'] : 0)+$gb,
                    'gcoinall' => ($user_info['gcoinall'] ? $user_info['gcoinall'] : 0)+$gball
                );
            }
            //只存在G值时
            if (!empty($gb) && empty($ub)) {
                $arr = array(
                    'gcoin' =>  ($user_info['gcoin'] ? $user_info['gcoin'] : 0)+$gb,
                    'gcoinall' => ($user_info['gcoinall'] ? $user_info['gcoinall'] : 0)+$gball
                );
            }
            //只存在U币时
            if (!empty($ub) && empty($gb)) {
                $arr = array('ucoin' =>  ($user_info['ucoin'] ? $user_info['ucoin'] : 0)+$ub);
            }

            $save_info = $model->where(array('username'=>$username))->save($arr);
            if ($save_info){
                $log_data = array(
                    'uid' => $user_info['id'],
                    'username' => $username,
                    'add_time'  => date(time()),
                    'log_type'  => '充值',
                    'log_state' => 2,
                    'user_ucoin'=> $ub ? $ub : 0,
                    'user_gcoin'=> $gb ? $gb : 0,
                    'operation_msg' => $_SESSION['admin']['username'] ? $_SESSION['admin']['username'] : '',
                    'log_content'   => '',
                );

                $insert_userlog =  M('adduserlog')->add($log_data);
                if ($insert_userlog){
                    $this->success("充值成功!");
                }else{
                    $this->error("充值失败");
                }
            }else{
                $this->error("充值失败");
            }
        }
    }
    //充值记录
    public function jilu()
    {
        $User = M("adduserlog");
        $username = $_POST['username'];
        $count      = $User->count();// 查询满足要求的总记录数
        $Page       = new \Think\Page($count,50);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();// 分页显示输出
// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        if(!empty($username)){
            $list = $User->where(array('username'=>$username))->field(" *,FROM_UNIXTIME(add_time, '%Y-%m-%d %H:%i:%S') AS add_time")->order('id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        }else{
            $list = $User->field(" *,FROM_UNIXTIME(add_time, '%Y-%m-%d %H:%i:%S') AS add_time")->order('id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        }
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

    //扣除
    function withhold(){
        if (empty($_POST)){
            $this->display();
        }else{
            $model = M('user');
            $username =$_POST['username'];
            $user_info = $model->where(array('username'=>$_POST['username']))->find();
            $ub = $_POST['ucoin'] ? $_POST['ucoin'] : '';
            $gb = $_POST['gcoin'] ? $_POST['gcoin'] : '';
            //当U币与G值同时存在
            if (!empty($ub) && !empty($gb)) {
                $arr = array(
                    'ucoin' => ($user_info['ucoin'] ? $user_info['ucoin'] : 0)-$ub,
                    'gcoin' => ($user_info['gcoin'] ? $user_info['gcoin'] : 0)-$gb
                );
            }
            //只存在G值时
            if (!empty($gb) && empty($ub)) {
                $arr = array('gcoin' =>  ($user_info['gcoin'] ? $user_info['gcoin'] : 0)-$gb);
            }
            //只存在U币时
            if (!empty($ub) && empty($gb)) {
                $arr = array('ucoin' =>  ($user_info['ucoin'] ? $user_info['ucoin'] : 0)-$ub);
            }

            $username = $user_info['username'];
            $save_info = $model->where(array('username'=>$username))->save($arr);
            if ($save_info){
                $this->success("扣款成功!");
            }else{
                $this->error("扣款失败");
            }
        }
    }
    //系统角色管理
    function system()
    {
        $car = M('auser');
        /*$list = $car->field(" *,CASE isroot WHEN '0' THEN '普通管理员' WHEN '1' THEN 'TOP级会员'END
as root")->select();*/

        $list =$car->field("*,CASE is_root WHEN '0' THEN '普通管理员' WHEN '1' THEN 'TOP级管理员' WHEN '2' THEN '系统管理员'END AS is_root,FROM_UNIXTIME(last_time, '%Y-%m-%d %H:%i:%S') AS last_time")->select();
        $this->assign('list', $list);
        $this->display();
    }
//网站配置
    function config()
    {
        $model = M('config');
        $content =$_POST['about'] ? $_POST['about'] : '';
        if (empty($content)){
            echo json_encode(array('code'=>-1,'msg'=>'公告信息为空'));exit;
        }
        $info = $model->where('id =1')->save(array('about'=>$content));
        if ($info){
            echo json_encode(array('code'=>1,'msg'=>'发布成功'));exit;
        }else{
            echo json_encode(array('code'=>-1,'msg'=>'发布失败'));exit;
        }
    }
    public function sysInfo()
    {  	
        $this->display();
    }
    //递归查询所有ID
    public function digui($list,$id,$lev=0){
		foreach ($list as $v) {
			if ($v['topid'] == $id) {
				$this->ub[$lev] = $v['id']; 
				$this->digui($list,$v['id'],$lev+1);
			}
		}		
		return $this->ub;
	}
	//列表信息
    public function getuserInfo()
    {
    	$user = M("user");
    	$where = "1=1";   	
    	if (!empty($_GET['s_time'])){
    		$where.=" and updated_at > '".strtotime($_GET['s_time'])."'";
    	}
    	if (!empty($_GET['e_time'])){
    		$where.=" and updated_at <= '".strtotime($_GET['e_time'])."'";
    	}
    	if (!empty($_GET['username'])){
    		$where.=" and username = '".$_GET['username']."'";
    	}else{
    		$_GET['username'] = $_SESSION['admin']['username'];
    	}
    	$page_s = ($_GET['page']-1)*$_GET['limit'];
    	$page_e = $_GET['limit'];
    	$coinlog_find = $user->where(array('username'=>$_GET['username']))->order('id DESC')->find();
    	if (!empty($coinlog_find)){
	    	$coinlog_infos= $user->select();
	    	$result = $this->digui($coinlog_infos,$coinlog_find['id']);
	    	foreach ($result as $v){
	    		$arr[] = "'".$v."'";
	    	}
	    	$topid_info = $user->field('*')->where("id in(".implode(',', $arr).")")->limit($page_s,$page_e)->select(); 
	    	$topid_conut = $user->field("count(id)")->where("id in(".implode(',', $arr).")")->find();
    	} 		 	
    	$coinlog_list = $user->where($where)->order('id DESC')->limit($page_s,$page_e)->select();
    	if (!empty($coinlog_list)){
    		foreach ($coinlog_list as $key=>$list){
    			$coinlog_list[$key]['updated_at'] = date("Y-m-d H:i:s",$list['updated_at']);
    		}
    	}
    	$coinlog = $user->field("id")->where($where)->order('id desc')->select();
    	if (!empty($topid_info)) {
    		echo json_encode(array('code'=>0,'msg'=>'','count'=>$topid_conut['count(id)'],'data'=>$topid_info));
    	}else{
    		$num = count($coinlog);
    		echo json_encode(array('code'=>0,'msg'=>'','count'=>$num,'data'=>$coinlog_list));
    	}
    }
    function beifen(){
        vendor('DBExport');
        header('Content-type: text/plain; charset=UTF-8');
        $dbport = new \DBExport();
        $dbName = C('DB_NAME');
        header("Content-Disposition: attachment; filename=\"{$dbName}.sql\"");
        echo $dbport->ExportAllData();

    }
    //数据查看
    public function userStatistics(){
        $m = M('user');
        $d = M("coinlog");
        $g = M("admincoinlog");
        $z = M("adduserlog");
        $where = "type=6";
        $where1 = "type=3 OR type=4";
        $where2 = "1=1";
        $where4 = "2=2";
        $where3 = "type=3";

        if (!empty($_GET['time'])){
            $data = explode(",",$_GET['time']);

            if (is_array($data) && $data[0]){
                $where.=" and updated_at > '".strtotime($data[0])."'";
                $where1.=" and updated_at > '".strtotime($data[0])."'";
                $where3.=" and updated_at > '".strtotime($data[0])."'";
                $where2.=" and regtime > '".strtotime($data[0])."'";
                $where4.=" and add_time > '".strtotime($data[0])."'";
            }
            if (is_array($data) && $data[1]){
                $where.=" and updated_at <= '".strtotime($data[1])."'";
                $where1.=" and updated_at <= '".strtotime($data[1])."'";
                $where3.=" and updated_at <= '".strtotime($data[1])."'";
                $where2.=" and regtime > '".strtotime($data[0])."'";
                $where4.=" and add_time > '".strtotime($data[0])."'";
            }
        }
        $zcub = $z->where($where4)->sum('user_ucoin');
        $zcub = 100000000-$zcub;//liu剩余
        $jiag = $g->where($where3)->sum('ucoin');
        $allub = $m->where($where2)->sum('ucoin');
        $allg = $m->where($where2)->sum('gcoin');
        $duiub = $d->where($where)->sum('ucoin');
        $gzhiub = $g->where($where1)->sum('ucoin');
        $szlub =$zcub + $gzhiub;
        $this->assign('szlub',$szlub);//剩总量u点
        $this->assign('zcub',$zcub);//剩余的u点
        $this->assign('jiag',$jiag);//g值加速总量
        $this->assign('allub',$allub);//盘中总的ub
        $this->assign('allg',$allg);//总的g值
        $this->assign('duiub',abs($duiub));//兑换成g值的U币
        $this->assign('gzhiub',$gzhiub);//签到加速的U币
        $this->display();
    }
    //获取用户的ip地址
    function get_client_ip($type = 0) {
        $type       =  $type ? 1 : 0;
        static $ip  =   NULL;
        if ($ip !== NULL) return $ip[$type];
        if($_SERVER['HTTP_X_REAL_IP']){//nginx 代理模式下，获取客户端真实IP
            $ip=$_SERVER['HTTP_X_REAL_IP'];
        }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {//客户端的ip
            $ip     =   $_SERVER['HTTP_CLIENT_IP'];
        }elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {//浏览当前页面的用户计算机的网关
            $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos    =   array_search('unknown',$arr);
            if(false !== $pos) unset($arr[$pos]);
            $ip     =   trim($arr[0]);
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];//浏览当前页面的用户计算机的ip地址
        }else{
            $ip=$_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u",ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }
}