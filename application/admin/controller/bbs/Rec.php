<?php

namespace app\admin\controller\bbs;

use app\common\controller\Backend;

/**
 * 社区广告
 *
 * @icon fa fa-circle-o
 */
class Rec extends Backend
{
    
    /**
     * BbsRec模型对象
     * @var \app\admin\model\BbsRec
     */
    protected $model = null;
    protected $relationSearch = true;
    protected $searchFields = 'id';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('BbsRec');
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
                ->with('bbsPost')
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with('bbsPost')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach($list as $kl=>$vl){
                $list[$kl]['bbsPost'] = $vl['bbs_post'];
                $list[$kl]['status'] = ($vl['status']==1) ? '展示':'隐藏';
                /*$list[$kl]['coType'] = $vl['co_type'];
                $list[$kl]['enable'] = ($vl['enable']==1) ? '展示':'隐藏';*/
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
                    $params['create_time'] = date("Y-m-d H:i:s",time());
                    $params['update_time'] = date("Y-m-d H:i:s",time());
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


}
