<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use think\Db;
/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Complain extends Backend
{
    
    /**
     * UserComplain模型对象
     * @var \app\admin\model\UserComplain
     */
    protected $model = null;
    protected $dataLimit = 'auth'; //默认基类中为false，表示不启用，可额外使用auth和personal两个值
    protected $dataLimitField = 'admin_id'; //数据关联字段,当前控制器对应的模型表中必须存在该字段

    protected $relationSearch = true;
    protected $noNeedRight = ['detail','pass','deny','transfer'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('UserComplain');

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
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
                ->with('user')
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->with('user')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
       /*     foreach ($list as $kl=>$vl){
                $list[$kl]['status_num'] = $vl['status'];
                switch( $list[$kl]['status_num'])
                {
                    case 0:
                        $list[$kl]['status'] = '已申请';break;
                    case 1:
                        $list[$kl]['status'] = '已通过';break;
                    case 2:
                        $list[$kl]['status'] = '已拒绝';break;
                }
            }*/
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }


    /**
     * 查看
     */
    public function detail($ids = NULL)
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
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }



}
