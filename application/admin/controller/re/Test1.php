<?php

namespace app\admin\controller\re;

use app\common\controller\Backend;
use think\Session;
use think\Db;
use think\Cache;
use think\cache\driver\Redis;
use fast\Date;
use app\api\library\NoticeHandle;
/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Test1 extends Backend
{
    
    /**
     * ReCompany模型对象
     * @var \app\admin\model\ReCompany
     */
    protected $model = null;
    protected $dataLimit = 'personal'; //默认基类中为false，表示不启用，可额外使用auth和personal两个值
   // protected $dataLimit = false; //默认基类中为false，表示不启用，可额外使用auth和personal两个值
    protected $dataLimitField = 'admin_id'; //数据关联字段,当前控制器对应的模型表中必须存在该字段
    public $redis;
    protected $noNeedRight = ['pass','index','downMoney','test','edit'];

    public function _initialize()
    {

        parent::_initialize();
        $this->model = model('ReTest1');
        $this->redis = Cache::getHandler();
        $this->recommendDetailModel = model('ReRecommenddetail');

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        $admin_session = Session::get('admin');

        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }

            //list($where, $sort, $order, $offset, $limit) = $this->buildparamsWithAuth(null,null,array('admin_id'=>$admin_session['id']));
         //   list($where, $sort, $order, $offset, $limit) = $this->buildparamsWithAuth();
            if($admin_session['id']!=1){
            	list($where, $sort, $order, $offset, $limit) = $this->buildparamsfilter(null,null,null,array('admin_id'=>$admin_session['id']));	
            }else{
            	list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            }
            
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $date = new Date();
            foreach($list as $kl=>$vl){
                $list[$kl]['status_ori'] = $list[$kl]['status'];
                $list[$kl]['status'] = ($vl['status']==1) ? '已发奖':(($vl['status']==2) ? '未发奖':"已取消");
                $list[$kl]['reward_type'] = ($vl['reward_type']==1) ? '固定金额':"固定比例";
                if(!empty($vl['timeline'])){
                    $create_time = strtotime($vl['create_at']);
                    $timeline_time = strtotime($vl['timeline']);
                    $now = time();
                    $list[$kl]['rate'] = $date->getDateRate($create_time,$timeline_time,$now);
                }else{
                    $list[$kl]['rate'] = "-";
                }

             //   $list[$kl]['offer'] = ($vl['offer']==0) ? '待录用':(($vl['offer']==1) ? '已录用':'未录用');
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }



    /**
     * 查看
     */
    public function indexSpec()
    {
        //设置过滤方法
        $admin_session = Session::get('admin');
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
       //     list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            //list($where, $sort, $order, $offset, $limit) = $this->buildparamsWithAuth(null,null,array('admin_id'=>$admin_session['id']));
            list($where, $sort, $order, $offset, $limit) = $this->buildparamsWithAuth();
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {
                if ($this->dataLimit && $this->dataLimitFieldAutoFill)
                {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                try
                {
                    //是否采用模型验证
                    if ($this->modelValidate)
                    {
                        $name = basename(str_replace('\\', '/', get_class($this->model)));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : true) : $this->modelValidate;
                        $this->model->validate($validate);
                    }
                    $result = $this->model->allowField(true)->save($params);
                    if ($result !== false)
                    {
                        $this->success();
                    }
                    else
                    {
                        $this->error($this->model->getError());
                    }
                }
                catch (\think\exception\PDOException $e)
                {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }



    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->recommendDetailModel->get($ids);

        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();

        if (is_array($adminIds))
        {

            if (!in_array($row[$this->dataLimitField], $adminIds))
            {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {

                try
                {
                    //是否采用模型验证
                    if ($this->modelValidate)
                    {
                        $name = basename(str_replace('\\', '/', get_class($this->model)));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : true) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    $params['status'] = 3;
                    /* var_dump($params);
                     var_dump($ids);
                     exit;*/
                    $result = Db::table('re_recommenddetail')->where('id','=',$ids)->update($params);
                    //$result = $row->allowField(true)->save($params);
                    if ($result !== false)
                    {
                        $this->success();
                    }
                    else
                    {
                        $this->error($row->getError());
                    }
                }
                catch (\think\exception\PDOException $e)
                {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }


    public function prompt($ids = NULL){
        $params['status'] = 3;
       // $result = Db::table('re_recommenddetail')->where('id','=',$ids)->update($params);
        $this->success();
    }

    /**
     * 确认通过
     */
    public function pass($ids = NULL)
    {
        $platform_id = 1; //平台id
        $row = $this->recommendDetailModel->get($ids);
        $re_recommenddetail_id = $row->id;
        $re_recommenddetail_info = Db::table('re_recommenddetail')->where('id',$re_recommenddetail_id)->find();
        $re_job_id  =  $re_recommenddetail_info['re_job_id'];
        $job_info = Db::table('re_job')->where('id','=',$re_job_id)->find();
        $company_info = Db::table('re_company')->where('id','=',$job_info['re_company_id'])->find();
        $job_admin_id = $job_info['admin_id'];


        if (!$row)
            $this->error(__('No Results were found'));
            $adminIds = $this->getDataLimitAdminIdsAuth();

        if (is_array($adminIds))
        {
            if (!in_array($row[$this->dataLimitField], $adminIds))
            {
                $this->error(__('You have no permission'));
            }
        }

        if ($this->request->isPost()) {

            $re_company_id = $row->re_company_id;
            $total = $row->total_cash;

            $admin_session = Session::get('admin');
         //   $comp_info = Db::table('re_company')->field('id,admin_id,name,account')->where('id', $re_company_id)->find();
        //    if ($comp_info['admin_id'] == $admin_session['id']) {
            if ($job_admin_id == $admin_session['id']) {
                $comp_info = Db::table('re_company')->where('admin_id','=',$job_admin_id)->find();
                //发奖励之前先从发布该岗位的公司账户里扣除对应金额
                $result_down_money =  $this->downMoney($job_admin_id,$total);
            //    var_dump($result_down_money);exit;


                if ($result_down_money!=2) {


                    $params_commend_detail_update = [
                        'status' => 1,
                        'update_at' => date('Y-m-d H:i:s')
                    ];
                    $params_company_account_update = $result_down_money;
                   /* $params_company_account_update = [
                        'account' => ($comp_info['account'] - $total)
                    ];*/

                    //todo  给用户增加金额,给用户增加记录,给推荐人添加金额,给推荐人添加记录,给平台(公司)添加金额/记录,给代理商(公司)添加金额/记录
               //     $re_recommenddetail_info = Db::table('re_recommenddetail')->where('id',$re_recommenddetail_id)->find();
                    //公司资金变动记录
                    $params_company_cash_log_update = [
                        're_company_id' => $comp_info['id'],
                        'apply_company_id' => $job_info['re_company_id'],
                        'apply_user_id' => $re_recommenddetail_info['low_user_id'],
                        'admin_id' => $re_recommenddetail_info['admin_id'],
                        'way' => 2,
                        'tip' => "入职员工奖励金支付",
                        'rec_id' => $re_recommenddetail_info['id'],
                        'type' => 6,
                        'status' => 1,
                        'cash' => $total,
                        'update_at' => date("Y-m-d H:i:s",time())
                    ];

                    //1.给入职用户发奖
                    $low_user_info = Db::table('user')->field('id,total_balance,available_balance,rec_cash')->where('id',$re_recommenddetail_info['low_user_id'])->find();
                    $arr_update_low_user = [
                        'total_balance' => $low_user_info['total_balance'] + $re_recommenddetail_info['lower_cash'],
                        'available_balance' => $low_user_info['available_balance'] + $re_recommenddetail_info['lower_cash'],
                        'rec_cash' => $low_user_info['rec_cash'] + $re_recommenddetail_info['lower_cash'],
                    ];
                    //a.给入职用户添加奖金记录
                    $arr_update_low_user_cash_log = [
                        'user_id' => $re_recommenddetail_info['low_user_id'],
                        'apply_company_id' => $job_info['re_company_id'],
                        'apply_user_id' => $re_recommenddetail_info['low_user_id'],
                        'admin_id' => $re_recommenddetail_info['admin_id'],
                        'way' => 1,
                        'tip' => "入职奖励",
                        'rec_id' => $re_recommenddetail_info['id'],
                        'type' => 1,
                        'status' => 1,
                        'cash' => $re_recommenddetail_info['lower_cash'],
                        'update_at' => date("Y-m-d H:i:s",time())
                    ];

                    //2.给上级人发奖()
                    if(!empty($re_recommenddetail_info['up_user_id'])){
                        $up_user_info = Db::table('user')->field('id,total_balance,available_balance,rec_cash')->where('id',$re_recommenddetail_info['up_user_id'])->find();
                        $arr_update_up_user = [
                            'total_balance' => $up_user_info['total_balance'] + $re_recommenddetail_info['up_cash'],
                            'available_balance' => $up_user_info['available_balance'] + $re_recommenddetail_info['up_cash'],
                            'rec_cash' => $up_user_info['rec_cash'] + $re_recommenddetail_info['up_cash'],
                        ];
                        //a.给上级用户添加奖金记录
                        $arr_update_up_user_cash_log = [
                            'user_id' => $re_recommenddetail_info['up_user_id'],
                            'apply_company_id' => $job_info['re_company_id'],
                            'apply_user_id' => $re_recommenddetail_info['low_user_id'],
                            'admin_id' => $re_recommenddetail_info['admin_id'],
                            'way' => 1,
                            'tip' => "会员推荐奖励",
                            'rec_id' => $re_recommenddetail_info['id'],
                            'type' => 2,
                            'status' => 1,
                            'cash' => $re_recommenddetail_info['up_cash'],
                            'update_at' => date("Y-m-d H:i:s",time())
                        ];

                    }

                    //3.给平台(公司)添加金额
                    $p_company_info = Db::table('re_company')->field('id,account')->where('id',$platform_id)->find();
                    $arr_update_up_p = [
                        'account' => $p_company_info['account'] + $re_recommenddetail_info['p_cash'],
                    ];
                    $arr_update_p_company_cash_log = [
                        're_company_id' => $p_company_info['id'],
                        'apply_company_id' => $job_info['re_company_id'],
                        'apply_user_id' => $re_recommenddetail_info['low_user_id'],
                        'admin_id' => $re_recommenddetail_info['admin_id'],
                        'way' => 1,
                        'tip' => "入职平台分红",
                        'rec_id' => $re_recommenddetail_info['id'],
                        'type' => 4,
                        'status' => 1,
                        'cash' => $re_recommenddetail_info['p_cash'],
                        'update_at' => date("Y-m-d H:i:s",time())
                    ];

                    //4.给分销商(公司)添加金额
           /*         if($re_recommenddetail_info['agent_cash']!=0){
                        $a_company_info = Db::table('re_company')->field('id,account')->where('id',$re_recommenddetail_info['up_company_id'])->find();
                        $arr_update_up_a = [
                            'account' => $a_company_info['account'] + $re_recommenddetail_info['agent_cash'],
                        ];
                        $arr_update_a_company_cash_log = [
                            're_company_id' => $re_recommenddetail_info['up_company_id'],
                            'way' => 1,
                            'tip' => "入职代理商分红",
                            'rec_id' => $re_recommenddetail_info['id'],
                            'type' => 5,
                            'status' => 1,
                            'cash' => $re_recommenddetail_info['agent_cash'],
                            'update_at' => date("Y-m-d H:i:s",time())
                        ];
                    }*/
                /*    echo"<pre>";
                    echo"支付公司";
                    var_dump($params_company_cash_log_update);
                    echo"入职用户";
                    var_dump($arr_update_low_user);
                    var_dump($arr_update_low_user_cash_log);
                    echo"上级用户";
                    var_dump($arr_update_up_user);
                    var_dump($arr_update_up_user_cash_log);
                    echo"平台";
                    var_dump($arr_update_up_p);
                    var_dump($arr_update_p_company_cash_log);
                    echo"代理商";
                    var_dump($arr_update_up_a);
                    var_dump($arr_update_a_company_cash_log);
                    exit;*/
                    // 启动事务
                    Db::startTrans();
                    try {
                        Db::table('re_company')->where('id', $re_company_id)->update($params_company_account_update);
                        Db::table('re_recommenddetail')->where('id', $re_recommenddetail_id)->update($params_commend_detail_update);
                        Db::table('cash_log')->insert($params_company_cash_log_update);  //"支付公司";
                        //"入职用户";
                        if($re_recommenddetail_info['lower_cash']>0){
                            Db::table('user')->where('id', $low_user_info['id'])->update($arr_update_low_user);
                            Db::table('cash_log')->insert($arr_update_low_user_cash_log);
                        }

                        //"上级用户";
                        if($re_recommenddetail_info['up_cash']>0) {
                            if (!empty($re_recommenddetail_info['up_user_id'])) {
                                Db::table('user')->where('id', $up_user_info['id'])->update($arr_update_up_user);
                                Db::table('cash_log')->insert($arr_update_up_user_cash_log);
                            }
                        }

                        if($re_recommenddetail_info['p_cash']>0) {
                            //平台
                            Db::table('re_company')->where('id', $p_company_info['id'])->update($arr_update_up_p);
                            Db::table('cash_log')->insert($arr_update_p_company_cash_log);
                        }
                        // 提交事务
                        Db::commit();


                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        $this->error("网络繁忙,请稍后再试");exit;
                    }

                    $days = round((time()-strtotime($row->create_at))/(60*60*24));
                    //发送消息
                    //1.入职奖：您已入职xxx公司xxx岗位xxx天，您获得入职奖励xxx元。
                    if($row->lower_cash>0){
                        $noticeHandleObj = new NoticeHandle();
                        //   $low_user_info = Db::table('user')->where('id','=',$row->lower_user_id)->find();
                        $type = 9;
                        // $content ="您的团队成员".$low_user_info['nickname']."已从".$company_info['name']."(公司)".$job_info['name']."岗位离职";
                        $content ="您已入职".$company_info['name']."公司".$job_info['name']."岗位".$days."天，您获得入职奖励".$row->lower_cash."元。";
                        $is_read = 2;
                        $noticeHandleObj->createNotice($type,$low_user_info['id'],$content,$is_read);
                    }
                    //2.发推荐奖信息
                    //您的团队成员xxx已入职xxx公司xxx岗位30天，您获得推荐奖励xxx元。
                    if($row->up_cash>0){
                        $noticeHandleObj = new NoticeHandle();
                        $low_user_info = Db::table('user')->where('id','=',$row->low_user_id)->find();
                        $type = 8;
                        // $content ="您的团队成员".$low_user_info['nickname']."已从".$company_info['name']."(公司)".$job_info['name']."岗位离职";
                        $content ="您的团队成".$low_user_info['nickname']."已入职".$company_info['name']."公司".$job_info['name']."岗位".$days."天，您获得推荐奖励".$row->up_cash."元。";
                        $is_read = 2;
                        $noticeHandleObj->createNotice($type,$up_user_info['id'],$content,$is_read);
                    }
                    $this->success();
                } else {
                    $this->error("余额不足,请先充值");
                }
            } else {
                $this->error(__('You have no permission'));
            }

        }
    }



    public function downMoney($admin_id,$total){
        $company_info = Db::table('re_company')->where('admin_id','=',$admin_id)->find();
        $ori_frozen = $company_info['frozen'];
        $ori_account = $company_info['account'];
        $ori_total = $ori_frozen + $ori_account;
        if ($ori_frozen > $total){
            $arr['frozen'] = $ori_frozen - $total;
            $result =  Db::table('re_company')->where('admin_id','=',$admin_id)->update($arr);
            return $arr;
        }elseif($ori_total>$total){
            $arr['frozen'] = 0;
            $arr['account'] = $ori_total - $total;
            $result =  Db::table('re_company')->where('admin_id','=',$admin_id)->update($arr);
            return $arr;
        }else{
            return 2;
        }

    }

    public function test(){
        $result = $this->redis->set('myname','pine');
        $myname = $this->redis->get('myname');
       var_dump($myname);exit;
    }

}
