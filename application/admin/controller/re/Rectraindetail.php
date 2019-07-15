<?php

namespace app\admin\controller\re;

use app\common\controller\Backend;
use think\Session;
use think\Db;
use think\Cache;
use think\cache\driver\Redis;
use fast\Date;
use app\api\library\NoticeHandle;
use app\api\controller\Crondjob;
/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Rectraindetail extends Backend
{
    
    /**
     * ReRectraindetail模型对象
     * @var \app\admin\model\ReRectraindetail
     */
    protected $model = null;
    protected $dataLimit = 'personal'; //默认基类中为false，表示不启用，可额外使用auth和personal两个值
    // protected $dataLimit = false; //默认基类中为false，表示不启用，可额外使用auth和personal两个值
    protected $dataLimitField = 'admin_id'; //数据关联字段,当前控制器对应的模型表中必须存在该字段
    public $redis;
    protected $relationSearch = true;
    protected $noNeedRight = ['pass','index','downMoney','test','edit'];

    public function _initialize()
    {
        parent::_initialize();
        $this->redis = Cache::getHandler();
        $this->model = model('ReRectraindetail');

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
                ->with('reCompany,recUser,user,reTraining')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $date = new Date();
            foreach($list as $kl=>$vl){
                $list[$kl]['reTraining'] = $list[$kl]['re_training'];
                $list[$kl]['reCompany'] = $list[$kl]['re_company'];
                $list[$kl]['recUser'] = $list[$kl]['rec_user'];
             //   $list[$kl]['reTraining'] = $list[$kl]['re_training'];

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
                    $result = Db::table('re_rectraindetail')->where('id','=',$ids)->update($params);
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


    /**
     * 确认通过
     */
    public function pass($ids = NULL)
    {
        $platform_id = 1; //平台id
        $row = $this->model->get($ids);
     /*   $re_recommenddetail_id = $row->id;
        $re_recommenddetail_info = Db::table('re_recommenddetail')->where('id',$re_recommenddetail_id)->find();
        $re_job_id  =  $re_recommenddetail_info['re_job_id'];
        $job_info = Db::table('re_job')->where('id','=',$re_job_id)->find();
        $company_info = Db::table('re_company')->where('id','=',$job_info['re_company_id'])->find();
        $job_admin_id = $job_info['admin_id'];*/


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
            if ($row['admin_id'] == $admin_session['id']) {

                $crondJobObj = new Crondjob();
                $rec_info = Db::table('re_rectraindetail')->where('id','=',$row->id)->find();
                $result = $crondJobObj->trainCommisionDistributeAutoDetail($rec_info);
                if($result==1){
                    $this->success();
                }else{
                    $this->error();
                }

              //  $days = round((time()-strtotime($row->create_at))/(60*60*24));
                //发送消息
                //1.入职奖：您已入职xxx公司xxx岗位xxx天，您获得入职奖励xxx元。
             /*   if($row->lower_cash>0){
                    $noticeHandleObj = new NoticeHandle();
                    //   $low_user_info = Db::table('user')->where('id','=',$row->lower_user_id)->find();
                    $type = 9;
                    // $content ="您的团队成员".$low_user_info['nickname']."已从".$company_info['name']."(公司)".$job_info['name']."岗位离职";
                    $content ="您已入职".$company_info['name']."公司".$job_info['name']."岗位".$days."天，您获得入职奖励".$row->lower_cash."元。";
                    $is_read = 2;
                    $noticeHandleObj->createNotice($type,$up_user_info['id'],$content,$is_read);
                }*/
                //2.发推荐奖信息
                //您的团队成员xxx已入职xxx公司xxx岗位30天，您获得推荐奖励xxx元。
             /*   if($row->up_cash>0){
                    $noticeHandleObj = new NoticeHandle();
                    $low_user_info = Db::table('user')->where('id','=',$row->low_user_id)->find();
                    $type = 8;
                    // $content ="您的团队成员".$low_user_info['nickname']."已从".$company_info['name']."(公司)".$job_info['name']."岗位离职";
                    $content ="您的团队成".$low_user_info['nickname']."已入职".$company_info['name']."公司".$job_info['name']."岗位".$days."天，您获得推荐奖励".$row->up_cash."元。";
                    $is_read = 2;
                    $noticeHandleObj->createNotice($type,$low_user_info['id'],$content,$is_read);
                }*/





                }  else {
                $this->error(__('You have no permission'));
            }

        }
    }







}
