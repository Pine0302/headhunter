<?php

namespace app\admin\controller\re;

use app\common\controller\Backend;
use think\Db;
use think\Session;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Resume extends Backend
{
    
    /**
     * ReResume模型对象
     * @var \app\admin\model\ReResume
     */
    protected $model = null;
    protected $noNeedRight = ['*'];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('ReResume');
        //$this->view->assign("identityList", $this->model->getIdentityList());
     /*
        $this->view->assign("willList", $this->model->getWillList());
        $this->view->assign("natureList", $this->model->getNatureList());
        $this->view->assign("typeList", $this->model->getTypeList());*/
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
        $admin_session = session('admin');
        $mobile = config('webset.default_service_mobile');
        if($admin_session['id']!=1){
            $company_info = Db::table('re_company')->where('admin_id','=',$admin_session['id'])->find();
            if(!empty($company_info['service_mobile'])){
                $mobile = $company_info['service_mobile'];
            }
        }
        $flag = 1;
        if($admin_session['admin_id']!==0){
            $flag = 0;
        }
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
                ->where($where)
                ->order($sort, $order)
                ->count();
            $this->model->removeOption();
            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $this->model->removeOption();
            $list = collection($list)->toArray();
            $webset_config = config('webset');

            foreach($list as $kl=>$vl){
                $apply_query = Db::table('re_apply');
                $apply_list = $apply_query
                    ->alias('a')
                    ->join('re_company c','c.id = a.re_company_id')
                    ->join('re_job j','j.id = a.re_job_id')
                    ->field('j.name as job_name,c.name as company_name')
                    ->where('a.re_resume_id','=',$vl['id'])
                    ->select();
                $apply_query -> removeOption();
                $list[$kl]['apply_info'] = '';
                if(!empty($apply_list)){
                    foreach($apply_list as $ka=>$va){
                        $list[$kl]['apply_info'].= $va['company_name']."-".$va['job_name'].";";
                    }
                    $list[$kl]['apply_info'] = rtrim($list[$kl]['apply_info'],";");
                }
                //print_r($apply_list);
                $list[$kl]['identity_text'] = ($vl['identity']==1) ? "职场" : "应届生";
                $list[$kl]['type_text'] = ($vl['type']==1) ? "普通简历" : "金边简历";

                $list[$kl]['education_text'] = $webset_config['education'][$vl['education']];
                $list[$kl]['will_text'] = $webset_config['will'][$vl['will']];
                $list[$kl]['nature_text'] = $webset_config['nature'][$vl['nature']];
                $list[$kl]['intime_text'] = $webset_config['intime'][$vl['intime']];
                $list[$kl]['flag'] = $flag;
                $list[$kl]['service_mobile'] = $mobile;
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    public function test($ids){
        //$resume_info = Db::table('re_resume')->where('id','=',$ids)->find();
        $mobile = config('webset.default_service_mobile');
        $admin_session = session('admin');
        if($admin_session['admin_id']!=1){
            $company_info = Db::table('re_company')->where('admin_id','=',$admin_session['admn_id'])->find();
            if(!empty($company_info['service_mobile'])){
                $mobile = $company_info['service_mobile'];
            }
        }
        echo json_encode(['mobile'=>$mobile]);
    }

}
