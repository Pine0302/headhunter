<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\Areas;
use think\Cookie;
use think\Session;
use think\Db;
use fast\Http;
use fast\Wx;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class Company extends Backend
{

    protected $noNeedLogin = ['showCompany'];
    protected $noNeedRight = ['indexSpec','getAdmin','indexPersonal','detail','apply_detail','people_detail','company_detail','index'];
    /**
     * ReCompany模型对象
     * @var \app\admin\model\ReCompany
     */
    protected $model = null;
    protected $applymodel = null;
    protected $userCompModel = null;

    protected $dataLimit = 'auth_light'; //默认基类中为false，表示不启用，可额外使用auth和personal两个值
    protected $dataLimitField = 'admin_id'; //数据关联字段,当前控制器对应的模型表中必须存在该字段
    protected $relationSearch = true;
    protected $searchFields = 'name';
    public function _initialize()
    {
        parent::_initialize();

      //  $dataLimit = false; //表示不启用，显示所有数据
     //   $dataLimit = 'auth'; //表示显示当前自己和所有子级管理员的所有数据
      //  $dataLimit = 'personal'; //表示仅显示当前自己的数据

        $this->model = model('ReCompany');
        $this->applymodel = model('ReApply');
        $this->userCompModel = model('ReUsercomp');

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
            $total = $this->model
                ->with('user,reLine')
                ->where($where)
                ->order($sort, $order)
                ->count();
            $this->model->removeOption();
            $list = $this->model
                ->with('user,reLine')
            //    ->with('admin')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $this->model->removeOption();
            $list = collection($list)->toArray();

            $webset = config('webset');
            foreach($list as $kl=>$vl){
                $list[$kl]['financing'] = $webset['financing'][$vl['financing']] ?? '无';
                $list[$kl]['scale'] = $webset['scale'][$vl['scale']] ?? '无';
                $list[$kl]['levle'] = ($vl['level']==1) ? '代理商' : '公司';
                $list[$kl]['status_text'] = ($vl['status']==1) ? '已认证' : (($vl['status']==2) ? '未认证' : '认证失败');
                $list[$kl]['status'] = $vl['status'];
                if(!empty($vl['re_company_id'])){
                    $up_company_info = Db::table('re_company')->where('id','=',$vl['re_company_id'])->find();
                    $admin_info = Db::table('admin')->where('id','=',$up_company_info['admin_id'])->find();
                    $list[$kl]['admin'] = $admin_info;
                }else{
                    $list[$kl]['admin'] = [];
                }
                //获取 入职的人数，收到的简历数，代理商下面的公司数量
                $info = $this->getCompanyDetail($vl);
                $list[$kl]['info'] = $info;
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        return $this->view->fetch();
    }


    public function getCompanyDetail($company_info){
        $apply_count = 0;
        $people_count = 0;
        $company_count = 0;
        $apply_count = Db::table('re_apply')->where('re_company_id','=',$company_info['id'])->count();
        $people_count = Db::table('re_usercomp')
            ->where('re_company_id','=',$company_info['id'])
            ->where('status','=',1)
            ->count();
        $company_count = Db::table('re_company')->where('re_company_id','=',$company_info['id'])->count();
        $response = [
            'apply_count'=>$apply_count,
            'people_count'=>$people_count,
            'company_count'=>$company_count,
            'id'=>$company_info['id']
        ];
        return $response;
    }



    public function getAdmin(){
        //获取当前admin 下属admin_id admin_name 和total
        $admin_list = $this->getChildrenAdminInfo();
        foreach($admin_list as $ka=>$va){
            if(!empty($va['nickname'])){
                $admin_list[$ka]['name'] = $va['nickname'];
            }
        }
        $total = count($admin_list);
        $respose = [
            'list'=>$admin_list,
            'total'=>$total,
        ];
        echo json_encode($respose);
    }




    /**
     * 添加
     */
    public function add()
    {
        $Areas = new Areas();
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");

            //查看该账户是否已有公司如果有了就不能创建公司了
          //  $admin_session = Session::get('admin');
            //$admin_id = $admin_session['id'];
            $admin_id = $params['admin_id'];
            if(empty($admin_id)){
                $this->error('登录超时,请重新登录');
            }else{
                $check_company = Db::table('re_company')->where('admin_id',$admin_id)->find();

                if(!empty($check_company)){
                    $this->error('该账号下已有公司,请使用新账号创建公司!');exit;
                }
            }


            if ($params)
            {
                if ($this->dataLimit && $this->dataLimitFieldAutoFill)
                {
                    $params[$this->dataLimitField] = $this->auth->id;
                    $params['admin_id'] = $admin_id;
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
                    //生成 prov_code 和 city_code
                    if(!empty($params['areas'])){
                        $area_info = $Areas->areaNameFormat($params['areas']);
                        $params['prov_code'] = $area_info['prov_info']['areano'];
                        $params['city_code'] = $area_info['city_info']['areano'];
                    }
                    //获取上级分销商id,如果是总部,就不管了
                    $admin_info = Db::table('admin')->where('id',$admin_id)->find();
                    $params['re_company_id'] = 1;
                  /*  if ($admin_info['admin_id']!=1){
                        $up_comp_info = Db::table('re_company')
                            ->where('admin_id',$admin_info['admin_id'])
                            ->order('id asc')
                            ->select();
                        if(!empty($up_comp_info)){
                            $params['re_company_id'] = $up_comp_info[0]['id'];
                        }else{
                            $this->error('上级代理商还未创建公司');
                        }
                    }*/
                    $params['create_at'] = date("Y-m-d H:i:s",time());
                    $params['update_at'] = date("Y-m-d H:i:s",time());
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
        $admin_session = Session::get('admin');
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
           // print_r($params);exit;
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
                    $params['update_at'] = date("Y-m-d H:i:s",time());
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
        if($admin_session['id']==1){
            return $this->view->fetch();
        }else{
            return $this->view->fetch("edit1");
        }

    }


    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    //展示某个代理商\公司账号拥有的公司列表
    public function showCompany(){
    /*        $keeplogin = Cookie::get('keeplogin');
            if ($keeplogin) {
                list($id, $keeptime, $expiretime, $key) = explode('|', $keeplogin);
                $params['admin_id'] = $id;
            }
            list($id, $keeptime, $expiretime, $key) = explode('|', $keeplogin);
            $comp_list = Db::table('re_company')->field('id,name')->where('admin_id',$id)->select();
            $total = count($comp_list);
            $response = ['list'=>$comp_list,'total'=>$total];
            return \GuzzleHttp\json_encode($response);*/
        return parent::selectpage();
    }



    /**
     * 查看
     */
    public function indexSpec()
    {
        //设置过滤方法
        $admin_session = Session::get('admin');
         $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
           if ($this->request->request('keyField'))
            {
                return $this->selectpage(array('admin_id'=>$admin_session['id']));
               // return $this->selectpageWithAuth(array('admin_id'=>$admin_session['id']));
            }
            //     list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            list($where, $sort, $order, $offset, $limit) = $this->buildparamsWithAuth(null,null,array('admin_id'=>$admin_session['id']));
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }


    /**
     * 查看
     */
    public function indexPersonal()
    {
        //设置过滤方法
        $admin_session = Session::get('admin');
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpagePersonal(array('admin_id'=>$admin_session['id']));
            //     return $this->selectpageWithAuth(array('admin_id'=>$admin_session['id']));
            }
            //     list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            list($where, $sort, $order, $offset, $limit) = $this->buildparamsWithAuth(null,null,array('admin_id'=>$admin_session['id']));
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }



    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids)
        {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds))
            {
                $count = $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->model->where($pk, 'in', $ids)->select();
            $count = 0;
            foreach ($list as $k => $v)
            {
                $company_id = $v->id;
            }
            foreach ($list as $k => $v)
            {
                $count += $v->delete();
            }
            if ($count)
            {
                $this->success();
            }
            else
            {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /**
     * 发放
     */
    public function detail($ids = "")
    {
        $company_info = Db::table('re_company')->where('id','=',$ids)->find();
        $fileinfo = $this->getWorkQrPic($company_info['id']);
        $filepath = $fileinfo['filepath'];
        $fileurl = $fileinfo['fileurl'];
        $filename = $fileinfo['filename'];
         ob_clean();
         ob_start();
         //$filename=$_GPC['url'];
        $filename=realpath($filepath); //文件名
        $date=date("Ymd-H:i:m");
        Header( "Content-type:  application/octet-stream ");
        Header( "Accept-Ranges:  bytes ");
        Header( "Accept-Length: " .filesize($filename));
        header( "Content-Disposition:  attachment;  filename= {$filename}");
        echo file_get_contents($filename);
        readfile($filename);  exit;
        header("Location:".$fileurl);

        // $this->success();
    }

    //生成小程序二维码
    public function getWorkQrPic($id){
        $local_file_path = $_SERVER['DOCUMENT_ROOT']."/sharepic/comp_".$id.".png";
        if(file_exists($local_file_path)){
            $arr_res['pic_url'] = "https://".$_SERVER['HTTP_HOST']."/sharepic/comp_".$id.".png";
            $return_file_path = "comp_".$id.".png";
        }else{
            //获取access_token
            $wx_info = config('Wxpay');
            $arr = ['app_id'=>$wx_info['APPID'],'app_secret'=>$wx_info['APPSECRET']];
            $wx = new Wx($arr);
            //生成小程序二维码
            $page = "pages/personal/login";
            $page = "";
            //   error_log(var_export($user_info['id'],1),3,$_SERVER['DOCUMENT_ROOT']."/test.txt");
            $return_file_path = $wx->get_comp_qrcode_unlimit($id,$page);

            $arr_res['pic_url'] = "https://".$_SERVER['HTTP_HOST']."/sharepic/".$return_file_path;
        }
        $fileinfo = [
            'fileurl'=>$arr_res['pic_url'],
            'filepath'=>$local_file_path,
            'filename'=>$return_file_path,
        ];
        return $fileinfo ;
        //var_dump($arr_res);exit;

        /*  if(!empty($arr_res['pic_url'])){
              $data = [
                  'data'=>$arr_res,
              ];

              $this->success('success', $data);
          }else{
              $this->error('网络繁忙,请稍后再试');
          }*/
    }




    /**
     * 查看简历数量
     */
    public function apply_detail()
    {
        $data = $this->request->request();
        //设置过滤方法
       $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
           // list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            list($where, $sort, $order, $offset, $limit) = $this->buildparamsWithExtra();

            $total = $this->applymodel
                ->with("reResume,reCompany,reJob,recUser")
                ->where($where)
                ->order($sort, $order)
                ->count();
            $this->applymodel->removeOption();
            $list = $this->applymodel
                ->with("reResume,reCompany,reJob,recUser")
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            //var_dump($list);exit;
            foreach($list as $kl=>$vl){
                $list[$kl]['reResume'] = $list[$kl]['re_resume'];
                $list[$kl]['reCompany'] = $list[$kl]['re_company'];
                $list[$kl]['recUser'] = $list[$kl]['rec_user'];
                $list[$kl]['reJob'] = $list[$kl]['re_job'];
                $list[$kl]['offer_status'] = $vl['offer'];
                $list[$kl]['reResume']['gender'] = ($vl['re_resume']['sex']==1) ? '男':'女';
                switch( $list[$kl]['offer_status'])
                {
                    case 0:
                        $list[$kl]['offer'] = '未查看';break;
                    case 1:
                        $list[$kl]['offer'] = '已录用';break;
                    case 2:
                        $list[$kl]['offer'] = '未录用';break;
                    case 3:
                        $list[$kl]['offer'] = '已查看';break;
                    case 4:
                        $list[$kl]['offer'] = '通知面试';break;
                    case 5:
                        $list[$kl]['offer'] = '离职';break;
                }
                //    $list[$kl]['offer'] = ($vl['offer']==0) ? '待录用':(($vl['offer']==1) ? '已录用':'未录用');
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->assign('default_re_company_id',$data['re_company_id']);
        return $this->view->fetch();
    }

    /**
     * 查看
     */
    public function people_detail()
    {
        //设置过滤方法
        //  $admin_session = Session::get('admin');
        $data = $this->request->request();
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparamsWithExtra();
            $total = $this->userCompModel
                ->with('reCompany,user,reJob')
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->userCompModel
                ->with('reCompany,user,reJob')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach($list as $kl=>$vl){
                // $list[$kl]['is_admin'] = ($admin_session['id']==1) ? 1 : 0 ;
                $list[$kl]['reCompany'] = $vl['re_company'];
                $list[$kl]['reJob'] = $vl['re_job'];
                $list[$kl]['ch_status'] = ($vl['status']==1) ? "在职":"离职" ;
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->assign('default_re_company_id',$data['re_company_id']);
        return $this->view->fetch();
    }


    /**
     * 查看
     */
    public function company_detail()
    {
        $data = $this->request->request();

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
                //  ->with('admin')
                ->where($where)
                ->order($sort, $order)
                ->count();
            $total = $total -1;
            $list = $this->model
                //    ->with('admin')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach($list as $kl=>$vl){
                $list[$kl]['status'] = ($vl['status']==1) ? '运营中' : '审核中';
                $list[$kl]['levle'] = ($vl['level']==1) ? '代理商' : '公司';
                if(!empty($vl['re_company_id'])){
                    $up_company_info = Db::table('re_company')->where('id','=',$vl['re_company_id'])->find();
                    $admin_info = Db::table('admin')->where('id','=',$up_company_info['admin_id'])->find();
                    $list[$kl]['admin'] = $admin_info;
                }else{
                    $list[$kl]['admin'] = [];
                }
                //获取 入职的人数，收到的简历数，代理商下面的公司数量
                $info = $this->getCompanyDetail($vl);
                $list[$kl]['info'] = $info;
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $company_info = Db::table('re_company')->where('id','=',$data['re_company_id'])->find();
       $this->assign('default_re_company_id',$data['re_company_id']);

        return $this->view->fetch();
    }



    public function pass(){
        $data = $this->request->request();
        Db::table('re_company')->where('id','=',$data['re_company_id'])->update(['status'=>1]);
        $company_info = Db::table('re_company')->where('id','=',$data['re_company_id'])->find();
        $notice = [
            'type'=>3,
            'from_user_type'=>0,
            'from_user_id'=>0,
            'to_user_id'=>$company_info['user_id'],
            'brief_content'=>'公司认证审核结果',
            'content'=>'您的公司认证已未通过审核',
            'is_read'=>2,
            'create_at'=>date("Y-m-d H:i:s"),
            'update_at'=>date("Y-m-d H:i:s"),
        ];
        Db::table('re_notice')->insert($notice);
        $this->success("认证成功");
    }
    public function deny(){
        $data = $this->request->request();
        Db::table('re_company')->where('id','=',$data['re_company_id'])->update(['status'=>3]);
        //给对应的hr 发送notice信息
        $company_info = Db::table('re_company')->where('id','=',$data['re_company_id'])->find();
        $notice = [
            'type'=>3,
            'from_user_type'=>0,
            'from_user_id'=>0,
            'to_user_id'=>$company_info['user_id'],
            'brief_content'=>'公司认证审核结果',
            'content'=>'您的公司认证未通过审核,请稍候重试',
            'is_read'=>2,
            'create_at'=>date("Y-m-d H:i:s"),
            'update_at'=>date("Y-m-d H:i:s"),
        ];
        Db::table('re_notice')->insert($notice);
        $this->success("拒绝认证");
    }



}
