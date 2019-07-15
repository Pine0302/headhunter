<?php

namespace app\admin\controller\cash;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Log extends Backend
{
    
    /**
     * CashLog模型对象
     * @var \app\admin\model\CashLog
     */
    protected $model = null;
    protected $dataLimit = 'auth'; //默认基类中为false，表示不启用，可额外使用auth和personal两个值
    protected $dataLimitField = 'admin_id'; //数据关联字段,当前控制器对应的模型表中必须存在该字段
    protected $relationSearch = true;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('CashLog');

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
        $log_type = config("webset.cash_log");
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $this->model->removeOption();
            $total = $this->model
                ->with("user")
                ->where($where)
                ->order($sort, $order)
                ->count();
            $this->model->removeOption();
            $list = $this->model
                ->with("user")
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $this->model->removeOption();
            $list = collection($list)->toArray();
            foreach($list as $kl=>$vl){
             /*   $list[$kl]['reCompany'] = $list[$kl]['re_company'];
                $list[$kl]['reApplyCompany'] = $list[$kl]['re_apply_company'];
                $list[$kl]['applyUser'] = $list[$kl]['apply_user'];*/


                $list[$kl]['ch_way'] = ($vl['way']==1) ? '流入':'流出';
                $list[$kl]['ch_type'] = $log_type[$vl['type']]['discription'];


            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }


}
