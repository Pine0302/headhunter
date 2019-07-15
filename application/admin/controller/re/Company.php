<?php

namespace app\admin\controller\re;

use app\common\controller\Backend;
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
    
    /**
     * ReCompany模型对象
     * @var \app\admin\model\ReCompany
     */
    protected $model = null;


    protected $noNeedRight = ['indexSpec'];

    public function _initialize()
    {

        parent::_initialize();
        $this->model = model('ReCompany');

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

                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with('user')
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

                return $this->selectpage();exit;
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
     * 发放
     */
    public function detail($ids = "")
    {
        $company_info = Db::table('re_company')->where('id','=',$ids)->find();
        $filename = $this->getWorkQrPic($company_info['id']);
        header("Location:".$filename);
       /* ob_clean();
        ob_start();
        //$filename=$_GPC['url'];

        $file = fopen($filename, 'r');
        header('Content-type:application/octet-stream');
        header('Accept-Ranges:bytes');
        header('Accept-Length:' . filesize($filename));
        header('Content-Disposition:attachment;filename="' . $filename . '"');
        echo fread($file, filesize($filename));
        fclose($file);*/
        exit;
       // $this->success();
    }


    function downfile()
    {
        $filename=realpath("resume.html"); //文件名
        $date=date("Ymd-H:i:m");
        Header( "Content-type:  application/octet-stream ");
        Header( "Accept-Ranges:  bytes ");
        Header( "Accept-Length: " .filesize($filename));
        header( "Content-Disposition:  attachment;  filename= {$date}.doc");
        echo file_get_contents($filename);
        readfile($filename);
    }







//生成小程序二维码
    public function getWorkQrPic($id){
        $local_file_path = $_SERVER['DOCUMENT_ROOT']."/sharepic/comp_".$id.".png";
        if(file_exists($local_file_path)){
            $arr_res['pic_url'] = "http://".$_SERVER['HTTP_HOST']."/sharepic/comp_".$id.".png";
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
        return $arr_res['pic_url'];
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


    public function pass(){
        $this->success("认证成功");
    }

}
