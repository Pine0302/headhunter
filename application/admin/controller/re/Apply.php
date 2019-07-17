<?php

namespace app\admin\controller\re;

use app\common\controller\Backend;
use think\Db;
use think\Session;
use app\api\library\NoticeHandle;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Apply extends Backend
{
    
    /**
     * ReApply模型对象
     * @var \app\admin\model\ReApply
     */
    protected $model = null;
    protected $dataLimit = 'auth_light'; //默认基类中为false，表示不启用，可额外使用auth和personal两个值
    protected $dataLimitField = 'admin_id'; //数据关联字段,当前控制器对应的模型表中必须存在该字段
    protected $searchFields = 'reResume.name';
    protected $relationSearch = true;

    protected $noNeedRight = ['detail','pass','deny','test','sendApplyNotice'];


    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('ReApply');

    }

    /**
     * 查看
     */
/*    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model->with('reResume')->with('reCompany')->with('reJob')->with('recUser')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach($list as $kl=>$vl){
                $list[$kl]['name'] = $vl['re_resume']['name'];
                $list[$kl]['company_name'] = $vl['re_company']['name'];
                $list[$kl]['rec_user_name'] = $vl['rec_user']['username'];
                $list[$kl]['job_name'] = $vl['re_job']['name'];
                $list[$kl]['age'] = $vl['re_resume']['age'];
                $list[$kl]['mobile'] = $vl['re_resume']['mobile'];
                $list[$kl]['sex'] = ($vl['re_resume']['sex']==0) ? '男':'女';
                $list[$kl]['offer_ch'] = ($vl['offer']==1) ? '已录用':'待录用';

            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }*/

    public function test($ids){
        $row = $this->model->get($ids);
        Db::table('re_apply')->where('id',$row->id)->update(['offer'=>3]);
        $result = array("code" => 1, "msg" => "查看成功");
        return json($result);
}

    /**
     * 查看
     */
    public function index()
    {
        $data = $_REQUEST;
        $re_company_id = 0;
        if(isset($data['re_company_id'])&&(!empty($data['re_company_id']))){  //查询某个公司的简历
            $re_company_id = $data['re_company_id'];
        }
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
           list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            //   list($where, $sort, $order, $offset, $limit) = $this->buildparamsWithAuth();
            $this->model->removeOption();
            $total = $this->model
                ->with("reResume,reCompany,reJob,recUser")
                ->where($where)
                ->order($sort, $order)
                ->count();
            $this->model->removeOption();
            $list = $this->model
                ->with("reResume,reCompany,reJob,recUser")
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $this->model->removeOption();
            foreach($list as $kl=>$vl){
                $list[$kl]['reResume'] = $list[$kl]['re_resume'];
                $list[$kl]['reCompany'] = $list[$kl]['re_company'];
                $list[$kl]['recUser'] = $list[$kl]['rec_user'];
                $list[$kl]['reJob'] = $list[$kl]['re_job'];
                $list[$kl]['offer_status'] = $vl['offer'];
                  $list[$kl]['reResume']['gender'] = ($vl['re_resume']['sex']==1) ? '男':'女';
                switch( $list[$kl]['offer_status'])
                {
                    case 1:
                        $list[$kl]['offer'] = '待查看';break;
                    case 2:
                        $list[$kl]['offer'] = '待沟通';break;
                    case 3:
                        $list[$kl]['offer'] = '面试';break;
                    case 4:
                        $list[$kl]['offer'] = '不合适';break;
                    case 5:
                        $list[$kl]['offer'] = '已通过';break;
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get($ids);
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
            if($params['offer']==1){
                $frozen_money = $this-> giveMoney($row->id,$row->re_job_id,$row->user_id,$row->rec_user_id);

                (($frozen_money==1)||($frozen_money==3)) ? '':( $this->error('您账户余额不足,不够支付入职者的奖励和推荐佣金'));
            }
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
                    $result = $row->allowField(true)->save($params);
                    if ($result !== false)
                    {
                        if($params['offer']==1){
                            if($frozen_money!=3){
                                $this->createRecommendDetailRecord($row->id);    //生成奖励金的记录
                            }
                            $this->updateUserCompRelation($row->id,1);//生成用户-公司-职位-管理员关系表 re_usercomp
                            $this->sendApplyNotice($row->id); //发送notice
                        }
                        if($params['offer']==5){
                            $this->updateUserCompRelation($row->id,2);//更新用户-公司-职位-管理员关系表 re_usercomp
                            $this->sendResignNotice($row->id);//更新用户-公司-职位-管理员关系表 re_usercomp
                        }

                        if($params['offer']==2){
                            $this->sendUnRecruitNotice($row->id);//
                        }
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


    /**
     * 详情
     */
    public function detail($ids = NULL)
    {
        $row = $this->model->get($ids);
        //如果简历状态为未查看,设置为已查看
        if($row->offer==0){
            Db::table('re_apply')->where('id',$row->id)->update(['offer'=>3]);
        }
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
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }


    /**
     * 允许查看
     */
    public function pass($ids = NULL)
    {
        $row = $this->model->get($ids);
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
            $params = [
                'offer'=>2,
                'show_status'=>1,
            ];

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
                    $result = $row->allowField(true)->save($params);
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
    }



    /**
     * 确认通过
     */
    public function pass1($ids = NULL)
    {
        $row = $this->model->get($ids);
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
            $params = [ 'offer'=>1 ];
            $frozen_money = $this-> giveMoney($row->id,$row->re_job_id,$row->user_id,$row->rec_user_id);
            (($frozen_money==1)||($frozen_money==3)) ? '':( $this->error('您账户余额不足,不够支付入职者的奖励和推荐佣金'));
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
                    $result = $row->allowField(true)->save($params);
                    if ($result !== false)
                    {
                        if($frozen_money!=3){
                            $this->createRecommendDetailRecord($row->id);
                        }
                        $this->updateUserCompRelation($row->id,1); //生成用户-公司-职位-管理员关系表 re_usercomp
                        $this->sendApplyNotice($row->id); //发送notice
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
    }


    //简历通过筛选 冻结金额
    public function giveMoney($apply_id=53,$re_job_id=15,$user_id=10,$rec_user_id=0){
        $flag = 0 ; //是否有推荐上级 0 无 1有
        //先确定该职位是否是推荐入职的
        //查看用户是否有上级
        $team_info = Db::table('user_team')->where('low_user_id','=',$user_id)->find();
        if (!empty($team_info)){
            $flag = 1;
        }else{
            if(!empty($rec_user_id!=0)){
                $flag = 1;
            }
        }

        $job_info = Db::table('re_job')->where('id','=',$re_job_id)->find();

        $admin_id = $job_info['admin_id'];

        $reward_ratio = Db::table('re_ratio')->where('uid','=',$admin_id)->find();
        if(empty($reward_ratio)){
            $reward_ratio = Db::table('re_ratio')->where('uid','=',1)->find();
        }
        $reward_type = $reward_ratio['reward_type'];
        $reward = $job_info['reward'];

        if($flag==1){       //本人入职奖励+ 上级佣金+ 平台佣金
            $up_reward = $job_info['reward_up'];
            if($reward_type==2){  //按比例分配
              //  $up_reward = $reward * $reward_ratio['rec_user_per']/100;
                $p_reward = $up_reward * $reward_ratio['p_per']/100;
            }else{
            //    $up_reward = $reward_ratio['rec_user_cash'];
             //   $p_reward = $reward * $reward_ratio['p_cash'];
                $p_reward = $reward_ratio['p_cash'];
            }
            $total_reward = $reward + $up_reward + $p_reward;
        }else{        //本人入职奖励 + 平台佣金
            $up_reward = 0;
            if($reward_type==2){  //按比例分配
                $p_reward = $up_reward * $reward_ratio['p_per']/100;
            }else{
               // $p_reward = $reward * $reward_ratio['p_cash'];
                $p_reward = $reward_ratio['p_cash'];
            }
            $total_reward = $reward + $up_reward + $p_reward;
        }
        //给该职位发布者冻结金额
        $publish_company = Db::table('re_company')->where('admin_id','=',$admin_id)->find();
        //var_dump($publish_company);exit;
        if($publish_company['account'] > $total_reward){
            //冻结金额
            if($total_reward==0){
                return 3;
            }
            $arr['account'] = $publish_company['account'] - $total_reward;
            $arr['frozen'] = $publish_company['frozen'] + $total_reward;
            $result = Db::table('re_company')->where('id','=',$publish_company['id'])->update($arr);
            return $result;
        }else{
            return 2;
        }

    }

    /**
     * 确认通过
     */
    public function deny($ids = NULL)
    {
        $row = $this->model->get($ids);
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
            $params = [ 'offer'=>2 ];
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
                    $result = $row->allowField(true)->save($params);
                    if ($result !== false)
                    {
                        $this->sendUnRecruitNotice($ids);
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
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    //添加记录
    public function  createRecommendDetailRecord($apply_id){

        $apply_info = Db::table('re_apply')->where('id',$apply_id)->find();
        $admin_session = Session::get('admin'); //todo 没有的话,重新登录

        $up_user_info = Db::table('user_team')->where('low_user_id',$apply_info['user_id'])->find();
        if(empty($up_user_info)&&(empty($apply_info['rec_user_id']))){   //没有上级没有推荐
            $flag_up_user = 0;
        }else{
            $flag_up_user = 1;
        }


        $job_info = Db::table('re_job')->where('id',$apply_info['re_job_id'])->field('reward_up,reward_type,reward,id,admin_id,reward_days')->find();

        $create_at_time = time();
        $create_at = date('Y-m-d H:i:s',$create_at_time);
        $reward_days = empty($job_info['reward_days']) ? 0 : $job_info['reward_days'] ;
        $timeline = date("Y-m-d H:i:s",$create_at_time + 60*60*24*$reward_days);
        $deadline = date("Y-m-d H:i:s",$create_at_time + 60*60*24*($reward_days+7));




        $reward_setting = Db::table('re_ratio')->where('uid','=',$job_info['admin_id'])->find();
        if(empty($reward_setting)){
            $reward_setting = Db::table('re_ratio')->where('uid','=',1)->find();
        }
      // var_dump($job_info);exit;
        //查看该公司是否有代理商,如果有agent_cash 不为0 否则为0
        $company_info = Db::table('re_company')
            ->where('id',$apply_info['re_company_id'])
            ->find();

        if ($reward_setting['reward_type']==1){
            $way = 1;
            if($flag_up_user==1){
                //$up_cash = $reward_setting['rec_user_cash'];
                $up_cash = $job_info['reward_up'];
            }else{
                $up_cash = 0;
            }
            $p_cash = $reward_setting['p_cash'];
            if(!empty($company_info['re_company_id'])){
            //    $agent_cash = $reward_setting['agent_cash'];
                $agent_cash = 0;
            }else{
                $agent_cash = 0;
            }
        }else{
            $way = 2;
            if($flag_up_user==1){
              //  $up_cash = $job_info['reward'] * $reward_setting['rec_user_per']/100;
                $up_cash = $job_info['reward_up'];
            }else{
                $up_cash = 0;
            }

            $p_cash = $job_info['reward_up'] * $reward_setting['p_per']/100;
            if(!empty($company_info['re_company_id'])){
              //  $agent_cash = $p_cash * $reward_setting['agent_per']/100;
                $agent_cash = 0;
            }else{
                $agent_cash = 0;
            }
        }
        $total_cash = floatval($up_cash) + floatval($p_cash) + floatval($agent_cash) + floatval($job_info['reward']);

        if(!empty($company_info['re_company_id'])){
            $up_company_id = $company_info['re_company_id'];
        }else{
            $up_company_id = 0;
        }


        $up_user_id = empty($up_user_info) ? $apply_info['rec_user_id'] : $up_user_info['up_user_id'];

        $check_rec_detail = Db::table('re_recommenddetail')->where('reply_id',$apply_id)->find();
        if(empty($check_rec_detail)){
            $param = array();
            $param = array(
                're_company_id'=>$apply_info['re_company_id'],
                'reply_id'=>$apply_id,
                'low_user_id'=>$apply_info['user_id'],
                'up_user_id'=>$up_user_id,
                'rec_user_id'=>$apply_info['rec_user_id'],
                'lower_cash'=>$job_info['reward'],
                'status'=>2,
                //'admin_id'=>$admin_session['id'],
                'admin_id'=>$job_info['admin_id'],
                're_job_id'=>$job_info['id'],
                'way'=>$way,
                'up_cash'=>$up_cash,
                'p_cash'=>$p_cash,
                'agent_cash'=>$agent_cash,
                'total_cash'=>$total_cash,
                'up_company_id'=>$up_company_id,
                'create_at'=>$create_at,
                'timeline'=>$timeline,
                'deadline'=>$deadline,
            );
            Db::name('re_recommenddetail')
                ->data($param)
                ->insert();
        }

    }


    //更新用户职员与公司关系记录
    public function updateUserCompRelation($re_apply_id,$status){
        //$admin_id = "对应公司管理员id";$user_id,$re_company_id,$re_job_id,

        $check_usercomp = Db::table('re_usercomp')->where('re_apply_id','=',$re_apply_id)->find();
        if(!empty($check_usercomp)){
            Db::table('re_usercomp')->where('re_apply_id','=',$re_apply_id)->update(['status'=>$status,'update_at'=>date('Y-m-d H:i:s')]);
        }else{
            if($status==1){
                $apply_info = Db::table('re_apply')->where('id','=',$re_apply_id)->find();
                $company_info = Db::table('re_company')->where('id','=',$apply_info['re_company_id'])->find();
                $arr_insert = [
                    'user_id'=>$apply_info['user_id'],
                    're_company_id'=>$apply_info['re_company_id'],
                    're_job_id'=>$apply_info['re_job_id'],
                    'admin_id'=>$company_info['admin_id'],
                    're_apply_id'=>$re_apply_id,
                    'status'=>$status,
                    'create_at'=>date("Y-m-d H:i:s"),
                    'update_at'=>date("Y-m-d H:i:s"),
                ];
                Db::table('re_usercomp')->insert($arr_insert);
            }

        }
    }

    //发送入职消息
    public function sendApplyNotice($apply_id=141){

        $apply_info = Db::table('re_apply')->where('id','=',$apply_id)->find();
        //给入职者和推荐者发送消息
        $job_info = Db::table('re_job')->where('id','=',$apply_info['re_job_id'])->find();
        $company_info = Db::table('re_company')->where('id','=',$job_info['re_company_id'])->find();
        $user_info = Db::table('user')->where('id','=',$apply_info['user_id'])->find();

        $up_user_info = Db::table('user_team')->where('low_user_id','=',$user_info['id'])->find();
        $up_user = Db::table('user')->where('id','=',$up_user_info['up_user_id'])->find();

        $noticeHandleObj = new NoticeHandle();
        $type = 4;
        $content ="恭喜您，您已入职".$company_info['name']."(公司)".$job_info['name']."岗位";
        $is_read = 2;
        $noticeHandleObj->createNotice($type,$apply_info['user_id'],$content,$is_read);

        //发送入职模版
        $data = [
            'keyword1'=>['value'=>$user_info['nickname']],
            'keyword2'=>['value'=>"已通过"],
            'keyword3'=>['value'=>$job_info['name']],
            'keyword4'=>['value'=>$company_info['name']],
        ];

        $noticeHandleObj->sendModelMsg($user_info,$data,'','EntryNotice','pages/work/workDetails');

        //找该用户的上级
        if(!empty($up_user_info['up_user_id'])){
            $type = 3;
            $content ="您的团队成员".$user_info['nickname']."入职".$company_info['name']."(公司)".$job_info['name']."岗位";
            $is_read = 2;
            $noticeHandleObj->createNotice($type,$up_user_info['up_user_id'],$content,$is_read);
        }
    }

    //发送离职消息
    public function sendResignNotice($apply_id){
        $noticeHandleObj = new NoticeHandle();

        $apply_info = Db::table('re_apply')->where('id','=',$apply_id)->find();
        //给入职者和推荐者发送消息
        $job_info = Db::table('re_job')->where('id','=',$apply_info['re_job_id'])->find();
        $company_info = Db::table('re_company')->where('id','=',$job_info['re_company_id'])->find();
        $user_info = Db::table('user')->where('id','=',$apply_info['user_id'])->find();
        $up_user_info = Db::table('user_team')->where('low_user_id','=',$user_info['id'])->find();

        //发送离职模版
        $data = [
            'keyword1'=>['value'=>$user_info['nickname']],
            'keyword2'=>['value'=>"已离职"],
            'keyword3'=>['value'=>$job_info['name']],
            'keyword4'=>['value'=>$company_info['name']],
        ];
        $noticeHandleObj->sendModelMsg($user_info,$data,'','EntryNotice','pages/work/workDetails');


        //找该用户的上级
        if(!empty($up_user_info['up_user_id'])){

            $type = 10;
            //$content ="您的团队成员".$user_info['nickname']."入职".$company_info['name']."(公司)".$job_info['name']."岗位";
            $content ="您的团队成员".$user_info['nickname']."已从".$company_info['name']."(公司)".$job_info['name']."岗位离职";
            $is_read = 2;
            $noticeHandleObj->createNotice($type,$up_user_info['up_user_id'],$content,$is_read);
        }

    }

    //发送未录用
    public function sendUnRecruitNotice($apply_id){
        $noticeHandleObj = new NoticeHandle();
        $apply_info = Db::table('re_apply')->where('id','=',$apply_id)->find();
        //给入职者和推荐者发送消息
        $job_info = Db::table('re_job')->where('id','=',$apply_info['re_job_id'])->find();
        $company_info = Db::table('re_company')->where('id','=',$job_info['re_company_id'])->find();
        $user_info = Db::table('user')->where('id','=',$apply_info['user_id'])->find();
     //   $up_user_info = Db::table('user_team')->where('low_user_id','=',$user_info['id'])->find();

        //发送离职模版
        $data = [
            'keyword1'=>['value'=>$user_info['nickname']],
            'keyword2'=>['value'=>"未录用"],
            'keyword3'=>['value'=>$job_info['name']],
            'keyword4'=>['value'=>$company_info['name']],
        ];
        $noticeHandleObj->sendModelMsg($user_info,$data,'','EntryNotice','pages/work/workDetails');

        //找该用户的上级
        if(!empty($up_user_info['up_user_id'])){

     /*       $type = 10;
            //$content ="您的团队成员".$user_info['nickname']."入职".$company_info['name']."(公司)".$job_info['name']."岗位";
            $content ="您的团队成员".$user_info['nickname']."已从".$company_info['name']."(公司)".$job_info['name']."岗位离职";
            $is_read = 2;
            $noticeHandleObj->createNotice($type,$up_user_info['up_user_id'],$content,$is_read);*/
        }
    }


}
