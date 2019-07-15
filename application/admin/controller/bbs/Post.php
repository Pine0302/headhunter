<?php

namespace app\admin\controller\bbs;

use app\common\controller\Backend;

/**
 * 社区贴子管理
 *
 * @icon fa fa-circle-o
 */
class Post extends Backend
{
    
    /**
     * BbsPost模型对象
     * @var \app\admin\model\BbsPost
     */
    protected $model = null;
    protected $relationSearch = true;
    protected $searchFields = 'bbsForum.name,bbsMember.nick_name';


    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('BbsPost');
        $this->view->assign("recommendList", $this->model->getRecommendList());
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
                ->with('bbsForum,bbsMember')
                ->where($where)
                ->order($sort, $order)
                ->count();
            $this->model->removeOption();
            $list = $this->model
                ->with('bbsForum,bbsMember')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $this->model->removeOption();
            $list = collection($list)->toArray();
            foreach($list as $kl=>$vl){
                if(!empty($vl['imgs'])){
                    $imgs = unserialize($this->mb_unserialize($vl["imgs"]));
                    $imgs_arr = [];
                    foreach($imgs as $kvv=>$vvv){
                       $imgs_arr[] ="https://".$_SERVER['HTTP_HOST']."/postimg/".$vvv;
                    }
                    $list[$kl]['imgs'] = $imgs_arr;
                }
                $list[$kl]['bbsForum'] = $vl['bbs_forum'];
                $list[$kl]['bbsMember'] = $vl['bbs_member'];
                $list[$kl]['status'] = ($vl['status']==1) ? '展示':'隐藏';
                $list[$kl]['recommend'] = ($vl['recommend']==1) ? '推荐':'不推荐';
                /*$list[$kl]['coType'] = $vl['co_type'];
                $list[$kl]['enable'] = ($vl['enable']==1) ? '展示':'隐藏';*/
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }


    public function mb_unserialize($str)
    {
        return preg_replace_callback('#s:(\d+):"(.*?)";#s', function ($match) {
            return 's:' . strlen($match[2]) . ':"' . $match[2] . '";';
        }, $str);
    }

    /**
     * 确认通过
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
            $params = [ 'status'=>1 ];
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
     * 确认拒绝
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
     * 详情
     */
    public function detail($ids = NULL)
    {
        $row = $this->model->get($ids);


        if(!empty($row->imgs)){
            $imgs = unserialize($this->mb_unserialize($row->imgs));
            $imgs_arr = [];
            $imgs_str = '';
            foreach($imgs as $kvv=>$vvv){
               // $imgs_arr[] ="/postimg/".$vvv;
                $imgs_arr[] ="https://".$_SERVER['HTTP_HOST']."/postimg/".$vvv;
                $imgs_str = $imgs_str."/postimg/".$vvv.",";
            }
            $imgs_str = rtrim($imgs_str, ",");
             $row->imgs = $imgs_arr;
            //    $row->imgs = $imgs_str;
        }else{
            $imgs_arr = [];
        }


        //如果简历状态为未查看,设置为已查看
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

        $row->ch_status = ($row->status==1) ? '显示':'隐藏';
        $this->view->assign("imgs_arr", $imgs_arr);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }




    /**
     * 确认推荐
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
            $params = [ 'recommend'=>1 ];
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
     * 取消推荐
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
            $params = [ 'recommend'=>2 ];
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






}
