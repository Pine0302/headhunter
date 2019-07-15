<?php

namespace app\admin\controller\re;

use app\common\controller\Backend;
use think\Db;
use PHPExcel_IOFactory;
use PHPExcel;
use think\Session;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Salary extends Backend
{
    
    /**
     * ReSalary模型对象
     * @var \app\admin\model\ReSalary
     */
    protected $model = null;
    protected $searchFields = 'id,name,id_num';
    protected $dataLimit = 'personal'; //默认基类中为false，表示不启用，可额外使用auth和personal两个值
    protected $dataLimitField = 'admin_id'; //数据关联字段,当前控制器对应的模型表中必须存在该字段
    protected $noNeedRight = ['import','detail'];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('ReSalary');

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


    public function sctonum($num){
        if(false !== stripos($num, "e")){
            $a = explode("e",strtolower($num));
            return bcmul($a[0], bcpow(10, $a[1]));
        }else{
            return $num;
        }
    }

    public function import(){
        //return parent::import();
        $data = $_POST;

        $file_name = $data['file'];
        $file_path = $_SERVER['DOCUMENT_ROOT'].$file_name;
        $data = $this->importExecl($file_path);
        $excel_config = config('excel');
        //获取salary_model_info
        $salary_model_info = end($data[1]);
        $salary_model_arr = explode("-",$salary_model_info);
        $salary_model_id = $salary_model_arr['1'];
        $salary_model_info = Db::table('re_salarymodel')->where('id','=',$salary_model_id)->find();

        foreach($data as $kd=>&$vd){
            $length = count($vd);
            unset($data[$kd][$excel_config[$length-1]]);
        }

        $result = $this->handleExcelData($data);
        //get company
        $admin_session = Session::get('admin');
        $admin_id = $admin_session['id'];

        $company_info = Db::table('re_company')->where('id','=',$salary_model_info['re_company_id'])->find();

        $arr_insert_salary = [];

        foreach($result as $kr=>$vr){
            $arr_insert_salary[$kr]['name'] = '';
            $arr_insert_salary[$kr]['id_num'] = '';
            $arr_insert_salary[$kr]['salary'] = '';
            $arr_insert_salary[$kr]['month'] = '';
            $arr_insert_salary[$kr]['detail']='';
            foreach($vr as $kvr=>$vvr){
                ($vvr['id']==1) ? ($arr_insert_salary[$kr]['name'] = $vvr['val']) : '';
                ($vvr['id']==2) ? ($arr_insert_salary[$kr]['id_num'] =strval($this->sctonum( $vvr['val']))) : "";
                ($vvr['id']==3) ? ($arr_insert_salary[$kr]['salary'] = $this->sctonum($vvr['val'])) : '';
                if($vvr['id']==4){
                    $salary_month_time = strtotime($vvr['val']);
                    $arr_insert_salary[$kr]['month'] = date('Y-m-d',$salary_month_time);
                }
            }
            $arr_insert_salary[$kr]['re_company_id'] = $company_info['id'];
            $arr_insert_salary[$kr]['admin_id'] = $admin_id;
            $arr_insert_salary[$kr]['company_name'] = $company_info['name'];
            $arr_insert_salary[$kr]['create_at'] = date('Y-m-d H:i:s',time());
            $arr_insert_salary[$kr]['update_at'] = date('Y-m-d H:i:s',time());
            $arr_insert_salary[$kr]['detail'] = serialize($vr);

        }

       $result = Db::table('re_salary')->insertAll($arr_insert_salary);

        if(!empty($result)){
            $this->success();
        }else{
            $this->error();
        }
    }

    public function handleExcelData($data = array())
    {
        $detail = array();
        foreach ($data[1] as $kkd => $vkd) {
            $item_info = explode('-',$vkd);
            $item_id = $item_info[1];
            $item_name = $item_info[0];
            $arr = ['id'=>$item_id,'name'=>$item_name,'key'=>$kkd];
            $detail[$kkd] = $arr;
        }
        $detail_item = [];
        $i=0;
        foreach ($data as $kd => $vd) {
            if ($kd != 1) {
                foreach ($vd as $kkd => $vkd) {
                    $detail_item[$i][$detail[$kkd]['id']] = ['id'=>$detail[$kkd]['id'],'name'=>$detail[$kkd]['name'],'val'=>$vkd];
                }
                $i++;
            }
        }
        return $detail_item;
    }




    /**
     *  数据导入
     * @param string $file excel文件
     * @param string $sheet
     * @return string   返回解析数据
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     */
    function importExecl($file, $sheet=0){

        $objPHPExcel = new \PHPExcel();
        if (!file_exists($file)) {
            die('no file!');
        }
        $extension = strtolower( pathinfo($file, PATHINFO_EXTENSION) );

        if ($extension =='xlsx') {
            $objRead = new \PHPExcel_Reader_Excel2007($objPHPExcel);   //建立reader对象
            $objExcel = $objRead ->load($file);
        } else if ($extension =='xls') {
            $objRead = new \PHPExcel_Reader_Excel5($objPHPExcel);
            $objExcel = $objRead ->load($file);
        } else if ($extension=='csv') {
            $PHPReader = new \PHPExcel_Reader_CSV($objPHPExcel);
            //默认输入字符集
            $PHPReader->setInputEncoding('GBK');
            //默认的分隔符
            $PHPReader->setDelimiter(',');
            //载入文件
            $objExcel = $PHPReader->load($file);
        }
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $obj = $objRead->load($file);  //建立excel对象
        $title =$objPHPExcel->getActiveSheet()->getTitle();
        $currSheet = $obj->getSheet($sheet);   //获取指定的sheet表
        $columnH = $currSheet->getHighestColumn();   //取得最大的列号
        $columnCnt = array_search($columnH, $cellName);
        $rowCnt = $currSheet->getHighestRow();   //获取总行数
        $shared = new \PHPExcel_Shared_Date();
        $data = array();
        for($_row=1; $_row<=$rowCnt; $_row++){  //读取内容
            for($_column=0; $_column<=$columnCnt; $_column++){
                $cellId = $cellName[$_column].$_row;
                $cellValue = $currSheet->getCell($cellId)->getValue();
                //$cellValue = $currSheet->getCell($cellId)->getCalculatedValue();  #获取公式计算的值
                if($cellValue instanceof PHPExcel_RichText){   //富文本转换字符串
                    $cellValue = $cellValue->__toString();
                }

               /* if(strpos($cellId,'D') !== false){
                    if($cellId!="D1"){
                        $cellValue =  $shared ->ExcelToPHP($cellValue);   //时间转换为时间戳
                        $cellValue = date("Y-m-d",$cellValue);
                    }
                }*/
                $data[$_row][$cellName[$_column]] = $cellValue;
            }
        }
        return $data;
    }




    public function detail($ids=""){
        $detail = Db::table('re_salary')->where('id',$ids)->find();
        $detail['detail'] = unserialize($detail['detail']);


        foreach($detail['detail'] as $kr=>$vr){
                ($kr==2) ? ($detail['detail'][$kr]['val'] =strval($this->sctonum( $vr['val']))) : "";
        }
        $this->assign('list',$detail['detail']);
        return $this->view->fetch();

    }







}
