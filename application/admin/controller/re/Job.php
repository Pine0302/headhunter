<?php

namespace app\admin\controller\re;

use app\common\controller\Backend;
use app\common\model\Areas;
use think\Db;
use think\Session;
use app\common\library\Dater;


/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Job extends Backend
{
    
    /**
     * ReJob模型对象
     * @var \app\admin\model\ReJob
     */
    protected $model = null;
    protected $dataLimit = 'personal'; //默认基类中为false，表示不启用，可额外使用auth和personal两个值
    protected $dataLimitField = 'admin_id'; //数据关联字段,当前控制器对应的模型表中必须存在该字段
    protected $noNeedRight = ['indexSpec','offline','online'];
    protected $relationSearch = true;
    protected $searchFields = 'reCompany.name';
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('ReJob');


    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    /**
     * 查看
     */
    public function experience()
    {
        //设置过滤方法
        $webset = config('webset');
        $list = $this->switchConfig2List($webset['job_experience']);
        $total = count($list);
        $result = array("total" => $total, "rows" => $list);
        return json($result);
    }


    /**
     * 查看
     */
    public function index()
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
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with('reCompany')
                ->where($where)
                ->order($sort, $order)
                ->count();
            $this->model->removeOption();
            $list = $this->model
                ->with('reCompany')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
          //  var_dump($this->model->getLastSql());exit;
            $list = collection($list)->toArray();
            foreach($list as $kl=>$vl){
                $list[$kl]['is_admin'] = ($admin_session['id']==1) ? 1 : 0 ;
                $list[$kl]['reward_type_ori'] = $vl['reward_type'];
                $list[$kl]['is_hot'] = ($vl['is_hot']==1) ? '推荐':'未推荐';
                $list[$kl]['reCompany'] = $vl['re_company'];
                $list[$kl]['reward_type'] = $vl['reward_type']==1 ? '固定金额' : '固定比例' ;
            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }


    public function indexspec(){
        //设置过滤方法
        $admin_session = Session::get('admin');
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpageWithAuth(array('admin_id'=>$admin_session['id']));
            }
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
            foreach($list as $kl=>$vl){
                $list[$kl]['reward_type_ori'] = $vl['reward_type'];
                $list[$kl]['reward_type'] = $vl['reward_type']==1 ? '固定金额' : '固定比例' ;
            }

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
            $params = $this->handleAddParams($params);
            if ($params)
            {
                /*$check_online = $this->checkOnline($params);
                if($check_online==2){
                    $this->error('公司余额不足,请先充值!');exit;
                }*/
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
               //     $params['requirement_age'] = $params['mini_age']."-".$params['max_age'];

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
        $row = $this->model->get($ids);
        $daterObj = new Dater();
        $row->salary_range = $daterObj->getSalaryRange($row->mini_salary,$row->max_salary);

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
                /*$check_online = $this->checkOnline($params);
                if($check_online==2){
                    $this->error('公司余额不足,请先充值!');exit;
                }*/
                try
                {
                    //是否采用模型验证
                    if ($this->modelValidate)
                    {
                        $name = basename(str_replace('\\', '/', get_class($this->model)));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : true) : $this->modelValidate;
                        $row->validate($validate);
                    }

                    $params = $this->handleAddParams($params);
                  //  print_r($params);exit;
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

        /*$re_jobtype_data = Db::table('re_jobtype')->field('id,name')->select();
        $re_jobtype_data1 = array();
        foreach($re_jobtype_data as $kr=>$vr){
            $re_jobtype_data1[]=array($vr['id']=>$vr['name']);
        }*/
        $this->view->assign("row", $row);
        //  $this->view->assign("re_jobtype_data", $re_jobtype_data1);
        return $this->view->fetch();
    }



    public function handleAddParams($params){
        $Areas = new Areas();
        if(!empty($params['area'])){
            $area_info = $Areas->areaNameFormat($params['area']);
            $params['prov_code'] = $area_info['prov_info']['areano'];
            $params['city_code'] = $area_info['city_info']['areano'];
            $params['district_code'] = $area_info['district_info']['areano'];
            $params['create_at'] = date('Y-m-d H:i:s',time());
            $params['update_at'] = date('Y-m-d H:i:s',time());
        }
        $salary_range = $params['salary_range'];
        if($salary_range){
            $daterObj = new Dater();
            $salary_range = $daterObj->getSalaryPath($salary_range);
            if(!empty($salary_range['min_salary']))  $params['mini_salary'] = $salary_range['min_salary'];
            if(!empty($salary_range['max_salary']))  $params['max_salary'] = $salary_range['max_salary'];
        }
        $company_info = Db::table('re_company')->where('id','=',$params['re_company_id'])->find();
        $params['user_id'] = $company_info['user_id'];
        $params['admin_id'] = $company_info['user_id'];
        return $params;
    }


    public function checkOnline($params,$admin_id=null){
        $reward = $params['reward'] ?? 0 ;
        $reward_up = $params['reward_up'] ?? 0;
        $reward_setting = Db::table('re_ratio')->where('id',1)->find();
        if($reward_setting['reward_type']==1){
            $p_cash = $reward_setting['p_cash'];
        }else{
            $p_cash = $reward_setting['p_per'] * $params['reward_up'] / 100;
        }
        $total_reward = $reward + $reward_up + $p_cash;
        if(empty($admin_id)){
            $admin_session = Session::get('admin');
            $admin_id = $admin_session['id'];
        }
        $company_info = Db::table('re_company')->where('admin_id','=',$admin_id)->find();
        $company_avaliable = $company_info['account'];
        if($company_avaliable < $total_reward ){
            return 2;
        }else{
            return 1;
        }

    }


    /**
     * 设为推荐
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
            $params = $this->request->post("row/a");
            $params = [
                'update_at'=>datetime("Y-m-d H:i:s",time()),
                'is_hot' => 1,
            ];
            if ($params)
            {
                try
                {
                    //是否采用模型验证
                 /*   if ($this->modelValidate)
                    {
                        $name = basename(str_replace('\\', '/', get_class($this->model)));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : true) : $this->modelValidate;
                        $row->validate($validate);
                    }*/
                  //  $params = $this->handleAddParams($params);
                    $params = [
                        'update_at'=>date("Y-m-d H:i:s",time()),
                        'is_hot' => 1,
                    //    'hot' => $params['hot']
                    ];
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

   /*     $re_jobtype_data = Db::table('re_jobtype')->field('id,name')->select();
        $re_jobtype_data1 = array();
        foreach($re_jobtype_data as $kr=>$vr){
            $re_jobtype_data1[]=array($vr['id']=>$vr['name']);
        }*/
        $this->view->assign("row", $row);
        //$this->view->assign("re_jobtype_data", $re_jobtype_data1);
        return $this->view->fetch();
    }




    /**
     * 设置状态 设为推荐
     */
    public function detail1($ids = NULL)
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
                    $params = [
                        'is_hot'=>1,
                        'hot'=>$params['hot'],
                    ];
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
        $this->view->assign("row", $row);
        return $this->view->fetch();
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
            $params = [ 'is_hot'=>2 ];
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
     * 下线
     */
    public function offline($ids = NULL)
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
            $params = [ 'status'=>2 ];
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
     * 下线
     */
    public function online($ids = NULL)
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
            $params = [ 'status'=>1 ];
            if ($params)
            {
                try
                {
                    $param_check_online = [
                        'reward' => $row['reward'],
                        'reward_up' => $row['reward_up'],
                        'reward_type' => $row['reward_type'],
                    ];
                    $result = $this->checkOnline($param_check_online,$row->admin_id);
                    //验证该职位能否上线
                 /*   $reward = $row['reward'];
                    $reward_type = $row['reward_type'];
                    //获取reward_setting
                    $reward_ratio = Db::table('re_ratio')->where('id','=',1)->find();
                    if($reward_type==2){  //按比例分配
                        $up_reward = $reward * $reward_ratio['rec_user_per']/100;
                        $p_reward = $reward * $reward_ratio['p_per']/100;
                        $total_reward = $reward + $up_reward + $p_reward;
                    }else{
                        $up_reward = $reward_ratio['rec_user_cash'];
                        $p_reward = $reward * $reward_ratio['p_cash'];
                        $total_reward = $reward + $up_reward + $p_reward;
                    }
                   //查看发布该岗位的公司金额
                    $company_info = Db::table('re_company')->where('admin_id','=',$row->admin_id)->find();
                    $company_avaliable = $company_info['account'];*/
                    if($result == 2){
                        $this->error('公司余额不足,请先充值!');exit;
                    }




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








}
