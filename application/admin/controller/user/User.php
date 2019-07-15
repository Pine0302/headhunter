<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use think\Db;

/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class User extends Backend
{

    protected $relationSearch = true;

    /**
     * User模型对象
     */
    protected $model = null;
    protected $noNeedRight = ['indexSpec','detail'];
    protected $searchFields = 'id,username,nickname,mobile,district_name';
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('User');
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
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $this->model->removeOption();
            $total = $this->model
                    ->with('group')
                    ->where($where)
                    ->order($sort, $order)
                    ->count();
            $this->model->removeOption();
            $list = $this->model
                    ->with('group')
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            foreach ($list as $k => $v)
            {
                $v->hidden(['password', 'salt']);
                $list[$k]['gender'] = ($v['gender']==1) ? '男' : (($v['gender']==2) ? '女' : '未知');
                $user_type='';
                $coin = '';
                if($v['is_engineer']==1){
                    $user_type .= "工程师"." ";
                    $coin .= "金币:".$v['coin']." ";
                }
                if($v['is_hr']==1){
                    $user_type .= "hr"." ";
                    $coin .= "hr猎币:".$v['hr_coin']." ";
                }
                if($v['is_agent']==1){
                    $user_type .= "经纪人"." ";
                    $coin .= "经纪人猎币:".$v['agent_coin']." ";
                }
                if(empty($user_type)){
                    $user_type = "无";
                }
                $list[$k]['user_type'] = rtrim("-",$user_type);
                $list[$k]['user_type'] = $user_type;
                $list[$k]['coin_info'] = $coin;

            }
        //    print_r($list);exit;
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
                ->with('group')
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with('group')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            foreach ($list as $k => $v)
            {
                $v->hidden(['password', 'salt']);
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
        $this->view->assign('groupList', build_select('row[group_id]', \app\admin\model\UserGroup::column('id,name'), $row['group_id'], ['class' => 'form-control selectpicker']));
        return parent::edit($ids);
    }

    public function detail($ids=NULL){

        $detail = Db::table('user')->where('id',$ids)->find();
        $this->view->assign("row", $detail);
        return $this->view->fetch();

    }



}
