<?php

namespace app\admin\controller\re;

use app\common\controller\Backend;
use fast\Idp2s;
use fast\Ocr;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Record extends Backend
{
    
    /**
     * ReRecord模型对象
     * @var \app\admin\model\ReRecord
     */
    protected $model = null;
    protected $relationSearch = true;
    //protected $dataLimit = 'auth'; //默认基类中为false，表示不启用，可额外使用auth和personal两个值
    protected $dataLimitField = 'admin_id'; //数据关联字段,当前控制器对应的模型表中必须存在该字段
    protected $dataLimit = 'personal'; //默认基类中为false，表示不启用，可额外使用auth和personal两个值

    protected $noNeedRight = ['getIdInfo','test','detail'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('ReRecord');

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
           list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            //  list($where, $sort, $order, $offset, $limit) = $this->buildparamsWithAuth();
            $total = $this->model
             //   ->with("user,reCompany,reJob")
                ->with("reCompany")
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
           //     ->with("user,reCompany,reJob")
                ->with("reCompany")
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            foreach($list as $kl=>$vl){
                $list[$kl]['reCompany'] = $list[$kl]['re_company'];
                $list[$kl]['id_num'] = "'".$vl['id_num']."'";
          //      $list[$kl]['reJob'] = $list[$kl]['re_job'];
                $list[$kl]['in_service_status'] = $vl['in_service'];
                $list[$kl]['in_service'] = ($vl['in_service']==1) ? '在职' : '不在职';
                $list[$kl]['sex'] = ($vl['sex']==1) ? '男' : '女';
             /*
                $list[$kl]['recUser'] = $list[$kl]['rec_user'];
                $list[$kl]['offer_status'] = $vl['offer'];
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
                }*/
                //    $list[$kl]['offer'] = ($vl['offer']==0) ? '待录用':(($vl['offer']==1) ? '已录用':'未录用');
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

            if(!empty($params['pic_id'])){
                //调取接口获取用户信息
                $file_path = $_SERVER['DOCUMENT_ROOT'].$params['pic_id'];


                $id_info = $this->getIdInfo2($file_path);

                $params = array_merge($params,$id_info);
                $params['create_at'] = date('Y-m-d H:i:s',time());
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

        /*    if(!empty($params['pic_id'])){
                //调取接口获取用户信息
                $file_path = $_SERVER['DOCUMENT_ROOT'].$params['pic_id'];
                $id_info = $this->getIdInfo2($file_path);
                $params = array_merge($params,$id_info);
                $params['update_at'] = date('Y-m-d H:i:s',time());
            }*/

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





    /**
     * 详情
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




    public function base64EncodeImage ($image_file) {

        $base64_image = '';
        $image_info = getimagesize($image_file);
        $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
        $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
        $base64_image_split = chunk_split(base64_encode($image_data));
        return $base64_image_split;
    }


    public function getIdInfo2($file_path){

        $file_path1 = $this->base64EncodeImage($file_path);

        $id2s_set = config('ocr');
        $arr=array(
            'app_code' => $id2s_set['app_code'],
            'app_key' => $id2s_set['app_key'],
            'app_secret' => $id2s_set['app_secret'],
            'url' => $id2s_set['url'],
            'type' => 1
        );
        $ocr = new Ocr($arr);
        $data = $ocr->analyzeId($file_path1);
    
        $sex = ($data['sex']=="男") ? 1 : 2 ;
        if(!empty($data)){
            $param = [
                'name'=>$data['name'],
                'birth'=>$data['date_of_birth'],
                'user_address'=>$data['address'],
                'id_num'=>$data['card_no'],
                'sex'=>$sex,
                'nationality'=>$data['nation'],
            ];
        }else{
            //todo
            $param=[];
        }
        return $param;
    }





    public function getIdInfo($file_path){
        var_dump($file_path);exit;
        $id2s_set = config('id2s');
        $arr=array(
            'app_code' => $id2s_set['set']['app_code'],
            'app_key' => $id2s_set['set']['app_key'],
            'app_secret' => $id2s_set['set']['app_secret'],
            'url' => $id2s_set['set']['url'],
            'type' => 1
        );


        $Idp2s = new Idp2s($arr);
        $response = $Idp2s->analyzeId($file_path);
        if($response['status']==0){
            $data = json_decode($response['data'],true);
            $birth_str = substr_replace(substr_replace($data['birth'],"-",4,0),'-',7,0);
            $sex = ($data['sex']=="男") ? 1 : 2 ;
            $param = [
                'name'=>$data['name'],
                'birth'=>$birth_str,
                'user_address'=>$data['address'],
                'id_num'=>$data['num'],
                'sex'=>$sex,
                'nationality'=>$data['nationality'],
            ];
        }else{
         //todo
            $param=[];
        }
        return $param;
    }








}
