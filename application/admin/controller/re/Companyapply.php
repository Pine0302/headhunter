<?php

namespace app\admin\controller\re;

use app\common\controller\Backend;
use think\Db;
use think\Session;
use fast\Random;

/**
 * 用户关注的企业
 *
 * @icon fa fa-circle-o
 */
class Companyapply extends Backend
{
    
    /**
     * ReCompanyApply模型对象
     * @var \app\admin\model\ReCompanyApply
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('ReCompanyApply');
        $this->view->assign("statusList", $this->model->getStatusList());
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
            $this->model->removeOption();
            $list = $this->model
                ->with('hr,company')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach($list as $kl=>$vl){
                $list[$kl]['status_text'] = ($vl['status']==1) ? '已通过': (($vl['status']==2) ? '未通过':'申请中');
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
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
                'status' => 1,
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
                        'status' => 1,
                        //    'hot' => $params['hot']
                    ];
                    $admin_session = Session::get('admin');

                    Db::table('user')->where('id','=',$row->user_id)->update(['re_company_id'=>$row->re_company_id]);
                    $company_info =   Db::table('re_company')->where('id','=',$row->re_company_id)->find();
                    $user_info = Db::table('user')->where('id','=',$row->user_id)->find();
                    $va['password'] = "123456";
                    $params['admin_id'] = $company_info['admin_id'];
                    $params['username'] = $user_info['mobile'];
                    $params['email'] = $user_info['email'];
                    $params['salt'] = Random::alnum();
                    $params['password'] = md5(md5($va['password']) . $params['salt']);
                    $params['avatar'] = '/assets/img/avatar.png';
                    $insert_id = Db::table('admin')->insertGetId($params);
                    $para = [
                        'uid'=>$insert_id,
                        'group_id'=>config('webset.hr_agent_group'),
                    ];
                    Db::table('auth_group_access')->insert($para);
                    Db::table('user')->where('id','=',$user_info['id'])->update(['ad_id'=>$insert_id]);



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





}
