<?php

namespace app\admin\controller\re;

use app\common\controller\Backend;

/**
 * 话题

 *
 * @icon fa fa-circle-o
 */
class Topicvote extends Backend
{
    
    /**
     * ReTopicVote模型对象
     * @var \app\admin\model\ReTopicVote
     */
    protected $model = null;
    protected $relationSearch = true;
    protected $searchFields = 'reTopic.title';
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('ReTopicVote');

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
            $this->model->removeOption();
            $total = $this->model
                ->with('reTopic,user')
                ->where($where)
                ->order($sort, $order)
                ->count();
            $this->model->removeOption();
            $list = $this->model
                ->with('reTopic,user')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $this->model->removeOption();
            $list = collection($list)->toArray();
            foreach($list as $kl=>$vl){
            //    $list[$kl]['result_text'] = ($vl['result']==1) ? "蓝方获胜" : "红方获胜";
                $list[$kl]['reTopic'] = $vl['re_topic'];
                $list[$kl]['vote_text'] =($vl['vote']==1) ? "蓝方":"红方";
                $list[$kl]['result_text'] =($vl['result']==1) ? "胜利":(($vl['result']==2) ? "失败":"平局");
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }



}
