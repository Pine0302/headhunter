<?php

namespace app\admin\controller\re;

use app\common\controller\Backend;
use think\Session;
use think\Db;
use PHPExcel_IOFactory;
use PHPExcel;
/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Usercomp extends Backend
{
    
    /**
     * ReUsercomp模型对象
     * @var \app\admin\model\ReUsercomp
     */
    protected $model = null;
    protected $dataLimit = 'auth'; //默认基类中为false，表示不启用，可额外使用auth和personal两个值
    protected $dataLimitField = 'admin_id'; //数据关联字段,当前控制器对应的模型表中必须存在该字段

    protected $noNeedRight = ['export','searchComp','exportNew'];
    protected $relationSearch = true;
    protected $searchFields = 'reCompany.name';
    public function _initialize()
    {

        parent::_initialize();
        $this->model = model('ReUsercomp');
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
      //  $admin_session = Session::get('admin');
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
                ->with('reCompany,user,reJob')
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
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
                $this->denyApply($v->re_apply_id);
                //var_dump($v->re_apply_id);
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

    //
    public function searchComp(){
        $admin_session = Session::get('admin');
        $admin_id = $admin_session['id'];
        $company_info = Db::table('re_company')->where('admin_id','=',$admin_id)->find();
        $arr_comp = [];
        if($admin_id==1){
            $comp_list = Db::table('re_company')->where('status','=',1)->field('id,name')->select();
        }else{
            $comp_list = Db::table('re_company')
                ->where('id','=',$company_info['id'])
                ->whereOr('re_company_id','=',$company_info['id'])
                ->field('id,name')->select();
        }
        foreach($comp_list as $kc=>$vc){
            $arr_comp[$vc['id']] = $vc['name'];
        }
        echo json_encode($arr_comp);
    }

    public function deny($ids = NULL){
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
                       $this->denyApply($row->re_apply_id);
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

    public function denyApply($apply_id){
        Db::table('re_apply')->where('id','=',$apply_id)->update(['offer'=>5]);
    }



    public function export(){

        //获取数据

        $this->exportExcel(array('姓名','年龄'), array(array('a',21),array('b',23)), '档案', './', true);
   /*     $objPHPExcel = new \PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()
            ->setCellValue("A1", '门店名称')
            ->setCellValue("B1", '店长')
            ->setCellValue("A2", '我的门店')
            ->setCellValue("B2", 'www');

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="store.xlsx"');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        $objPHPExcel->disconnectWorksheets();*/
    }

    //导出
    public function exportNew(){
        $data = $_REQUEST;
        $re_company_id = $data['re_company_id'];
        $status = $data['status'];

        $map=[];
        if(!empty($re_company_id)){
            $map['c.re_company_id'] = $re_company_id;
        }else{
            $admin_session = Session::get('admin');
            $admin_id = $admin_session['id'];
            $company_info = Db::table('re_company')->where('admin_id','=',$admin_id)->find();
            $map['c.re_company_id'] = $company_info['id'];
        }
        if(!empty($status)){
            $map['c.status'] = $status;
        }

        $list = Db::table('re_usercomp')
            ->alias('c')
            ->join('user u','u.id = c.user_id')
            ->join('re_company y','y.id = c.re_company_id')
            ->field('c.id,u.username,u.mobile,y.name as company_name')
            ->where($map)
            ->select();
        $this->exportExcel(array('id','姓名','电话','公司名称','薪资','备注'),$list, '档案', './', true);
    }


    /**
     * 数据导出
     * @param array $title   标题行名称
     * @param array $data   导出数据
     * @param string $fileName 文件名
     * @param string $savePath 保存路径
     * @param $type   是否下载  false--保存   true--下载
     * @return string   返回文件全路径
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     */
    function exportExcel($title=array(), $data=array(), $fileName='', $savePath='./', $isDown=false){
    //    include('PHPExcel.php');
        $obj = new PHPExcel();

        //横向单元格标识
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');

        $obj->getActiveSheet(0)->setTitle('sheet名称');   //设置sheet名称
        $_row = 0;   //设置纵向单元格标识
        if($title){
            $_cnt = count($title);
          //  $obj->getActiveSheet(0)->mergeCells('A'.$_row.':'.$cellName[$_cnt-1].$_row);   //合并单元格
        //    $obj->setActiveSheetIndex(0)->setCellValue('A'.$_row, '数据导出：'.date('Y-m-d H:i:s'));  //设置合并后的单元格内容
            $_row++;
            $i = 0;
            foreach($title AS $v){   //设置列标题
                $obj->setActiveSheetIndex(0)->setCellValue($cellName[$i].$_row, $v);
                $i++;
            }
            $_row++;
        }

        //填写数据
        if($data){
            $i = 0;
            foreach($data AS $_v){
                $j = 0;
                foreach($_v AS $_cell){
                    $obj->getActiveSheet(0)->setCellValue($cellName[$j] . ($i+$_row), $_cell);
                    $j++;
                }
                $i++;
            }
        }

        //文件名处理
        if(!$fileName){
            $fileName = uniqid(time(),true);
        }

        $objWrite = PHPExcel_IOFactory::createWriter($obj, 'Excel2007');

        if($isDown){   //网页下载
            header('pragma:public');
            header("Content-Disposition:attachment;filename=$fileName.xlsx");
            $objWrite->save('php://output');exit;
        }

        $_fileName = iconv("utf-8", "gb2312", $fileName);   //转码
        $_savePath = $savePath.$_fileName.'.xlsx';
        $objWrite->save($_savePath);

        return $savePath.$fileName.'.xlsx';
    }

//exportExcel(array('姓名','年龄'), array(array('a',21),array('b',23)), '档案', './', true);













}
