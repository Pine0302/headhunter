<?php

namespace app\admin\controller\re;

use app\common\controller\Backend;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Ratio extends Backend
{
    
    /**
     * ReRatio模型对象
     * @var \app\admin\model\ReRatio
     */
    protected $model = null;
    protected $relationSearch = true;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('ReRatio');

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
                ->with("admin")
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with("admin")
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();


            $list = collection($list)->toArray();



            foreach($list as $kr=>$vr){
                $list[$kr]['reward_type'] = ($vr['reward_type'] == 1) ? '固定金额' : '固定比例';
                if($vr['uid']!=1){
                    $company_info = Db::table('re_company')
                        ->where('admin_id','=',$vr['uid'])
                        ->find();
                    $company_name = empty($company_info['name']) ? '' : $company_info['name'];
                }else{
                    $company_name = "总平台";
                }
                $list[$kr]['admin']['info'] = $vr['admin']['nickname']."--".$company_name;

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
                    $params['create_at'] = date("Y-m-d H:i:s");
                    $params['update_at'] = date("Y-m-d H:i:s");
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

    //查看代理商信息
    public function agent(){
        $params = $this->request->request();
        $id = isset($params['searchValue']) ? $params['searchValue']:'';

        if(!empty($id)){
            $map['uid'] = $id;
        }else{
            $map = [];
        }
        $agent_info = [];
        $uid_list = Db::table('auth_group_access')
            ->alias('g')
            ->join('admin a','a.id = g.uid')
            ->field('a.nickname as name,g.uid as uid')
            ->where('g.group_id','=',2)
            ->where($map)
            ->select();
        $total = count($uid_list);
        foreach($uid_list as $ku=>$vu){
            $company_info = Db::table('re_company')
                ->where('admin_id','=',$vu['uid'])
                ->find();
            if(!empty($company_info['name'])){
                $agent_info[] = [
                    'id'=>$vu['uid'],
                    'info'=>$vu['name']."--".$company_info['name'],
                ];
            }else{
                $agent_info[] = [
                    'id'=>$vu['uid'],
                    'info'=>$vu['name'],
                ];
            }
        }
        $arr = [
            'list'=>$agent_info,
            'total'=>$total,
        ];
        echo \GuzzleHttp\json_encode($arr);
    }



}
