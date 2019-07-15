<?php

namespace app\admin\controller\re;

use app\common\controller\Backend;
use think\Session;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Training extends Backend
{
    
    /**
     * ReTraining模型对象
     * @var \app\admin\model\ReTraining
     */
    protected $model = null;
    protected $dataLimit = 'personal'; //默认基类中为false，表示不启用，可额外使用auth和personal两个值
    protected $dataLimitField = 'admin_id'; //数据关联字段,当前控制器对应的模型表中必须存在该字段
    protected $relationSearch = true;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('ReTraining');

    }



    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法

        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            //   list($where, $sort, $order, $offset, $limit) = $this->buildparamsWithAuth();

            $total = $this->model
                ->with("reTraintype")
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with("reTraintype")
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            foreach($list as $kl=>$vl){
                $list[$kl]['reTraintype'] = $list[$kl]['re_traintype'];
                switch( $list[$kl]['status'])
                {
                    case 1:
                        $list[$kl]['ch_status'] = '活动报名结束';break;
                    case 2:
                        $list[$kl]['ch_status'] = '活动报名中';break;
                    case 3:
                        $list[$kl]['ch_status'] = '活动已取消';break;
                    case 4:
                        $list[$kl]['ch_status'] = '活动已结束';break;
                }
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
        $admin_session = Session::get('admin');
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if($params['reward_up'] > $params['fee']){
                $this->error('推广费应小于报名费!');exit;
            }
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
                    $params['create_at'] = date("Y-m-d H:i:s");
                    $params['update_at'] = date("Y-m-d H:i:s");
                    $params['admin_id'] = $admin_session['id'];
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
        $admin_session = Session::get('admin');
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
                    $params['update_at'] = date("Y-m-d H:i:s");
                    $params['admin_id'] = $admin_session['id'];
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
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

}
