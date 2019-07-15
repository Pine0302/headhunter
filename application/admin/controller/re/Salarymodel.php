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
class Salarymodel extends Backend
{
    
    /**
     * ReSalarymodel模型对象
     * @var \app\admin\model\ReSalarymodel
     */
    protected $model = null;
   /* protected $item_str_need = "1,2,3,4,5";
    protected $item_arr_need = ['1','2','3','4','5'];*/
    protected $item_str_need = "";
    protected $item_arr_need = [];
    protected $dataLimit = 'personal'; //默认基类中为false，表示不启用，可额外使用auth和personal两个值
    protected $dataLimitField = 'admin_id'; //数据关联字段,当前控制器对应的模型表中必须存在该字段

    protected $relationSearch = true;
    protected $searchFields = 'reCompany.name';

    protected $noNeedRight = ['detail','pass','deny','export','soft_del'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('ReSalarymodel');
        $this->item_model = model('ReSalaryitem');
        $this->detail_model = model('ReSalarymodeldetail');

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
            $arr = ['show'=>1];
            list($where, $sort, $order, $offset, $limit) = $this->buildparamsfilter(null,null,null,$arr);
            $total = $this->model
                ->with('reCompany')
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with('reCompany')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach($list as $kl=>$vl){
                $list[$kl]['reCompany'] = $list[$kl]['re_company'];
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 回收站
     */
    public function recyclebin()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->onlyTrashed()
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->onlyTrashed()
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

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

                $arr = explode(",",$params['items']);
                $arr_new = array_merge($this->item_arr_need,$arr);
                $arr_new = array_unique($arr_new);
                $params['items'] = implode(",",$arr_new);
                $params['create_at'] = date("Y-m-d H:i:s");
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
                    $id = $this->model->getLastInsID();
                    //插入detail记录
                    if(!empty($params['items'])){
                        $id_arr = explode(',',$params['items']);
                  //      $id_arr = array_merge($id_arr,$this->item_arr_need);
                        //sort($id_arr);
                        $list = array();
                       foreach($id_arr as $ki=>$vi){
                           $list[] = [
                               're_company_id'=>$params['re_company_id'],
                               're_salarymodel_id'=>$id,
                               're_salaryitem_id'=>$vi,
                               'create_at'=>date("Y-m-d H:i:s",time()),
                           ];
                       }
                      $this->detail_model->saveall($list);
                    }


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
        $itemdata = Db::table('re_salaryitem')->field('name')->select();
        $this->view->assign("item_str", $this->item_str_need);
        $this->view->assign("itemdata", $itemdata);
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get($ids);
        $id = $row->id;
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
                $params['update_at'] = date("Y-m-d H:i:s",time());

                $arr = explode(",",$params['items']);

                //去掉重复的$arr
                $arr_new = array_merge($this->item_arr_need,$arr);
                $arr_new = array_unique($arr_new);
                $params['items'] = implode(",",$arr_new);

               // 删除旧的items 记录
                Db::table('re_salarymodeldetail')
                    ->where('re_salarymodel_id',$id)
                    ->delete();
                //添加新的items记录
                if(!empty($params['items'])){
                    $id_arr = explode(',',$params['items']);
                    sort($id_arr);
                    $list = array();
                    foreach($id_arr as $ki=>$vi){
                        $list[] = [
                            're_company_id'=>$params['re_company_id'],
                            're_salarymodel_id'=>$id,
                            're_salaryitem_id'=>$vi,
                            'create_at'=>date("Y-m-d H:i:s",time()),
                        ];
                    }
                    $this->detail_model->saveall($list);
                }

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

        //获取$id对应的items
        $item_info = Db::table('re_salarymodeldetail')
            ->alias('d')
            ->join('re_salaryitem i','d.re_salaryitem_id = i.id')
            ->where('d.re_salarymodel_id',$id)
            ->field('i.id')
            ->select();
        $item_str = '';
        if(count($item_info)){
            foreach($item_info as $vi){
                $item_str .= $vi['id'].",";
            }
        }
        $item_str = substr($item_str,0,strlen($item_str)-1);

        $this->view->assign("row", $row);
        $this->view->assign("item_str", $item_str);
        return $this->view->fetch();
    }

    /**
     * 软删除
     */
    public function soft_del($ids = "")
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
                $count +=  $v->where('id',$v->id)->update(['show'=>2]);
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

    //导出模板
    public function pass($ids = ""){
        //获取$id对应的items
        /*$item_info = Db::table('re_salarymodeldetail')
            ->alias('d')
            ->join('re_salaryitem i','d.re_salaryitem_id = i.id')
            ->where('d.re_salarymodel_id',$ids)
            ->field('i.id,i.name')
            ->select();*/
        $this->outtest();


    }

    public function export($ids = ""){
        $pre_info = [
            'id'=>$ids,
            'name'=>'模板id(无需填写)'
            ];
        $item_info = Db::table('re_salarymodeldetail')
            ->alias('d')
            ->join('re_salaryitem i','d.re_salaryitem_id = i.id')
            ->where('d.re_salarymodel_id',$ids)
            ->field('i.id,i.name')
            ->select();

        foreach($item_info as $vi){
            $info[] = $vi;
        }
        $info[] =  $pre_info;
      /*  echo"<pre>";
        print_r($info);exit;*/
        $this->outtest($info);
     //   $this->outtest($item_info);
    }


    public function import(){
        //return parent::import();
        $data = $_POST;
        $file_name = $data['file'];
        $file_path = $_SERVER['DOCUMENT_ROOT'].$file_name;
        $data = $this->importExecl($file_path);
        var_dump($data);exit;

    }

    /*
    **
    *  数据导入
    * @param string $file excel文件
    * @param string $sheet
    * @return string   返回解析数据
    * @throws PHPExcel_Exception
    * @throws PHPExcel_Reader_Exception
    */
    function importExecl($file='', $sheet=0){

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
        $currSheet = $obj->getSheet($sheet);   //获取指定的sheet表
        $columnH = $currSheet->getHighestColumn();   //取得最大的列号
        $columnCnt = array_search($columnH, $cellName);
        $rowCnt = $currSheet->getHighestRow();   //获取总行数

        $data = array();
        for($_row=1; $_row<=$rowCnt; $_row++){  //读取内容
            for($_column=0; $_column<=$columnCnt; $_column++){
                $cellId = $cellName[$_column].$_row;
                $cellValue = $currSheet->getCell($cellId)->getValue();
                //$cellValue = $currSheet->getCell($cellId)->getCalculatedValue();  #获取公式计算的值
                if($cellValue instanceof PHPExcel_RichText){   //富文本转换字符串
                    $cellValue = $cellValue->__toString();
                }

                $data[$_row][$cellName[$_column]] = $cellValue;
            }
        }
        return $data;
    }



    public function outtest($info=array())
    {

        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
        $obj = $objPHPExcel->setActiveSheetIndex(0);
         $excel_config = config('Excel');
         foreach($info as $ki=>$vi){
             $col = $excel_config[$ki];
             $obj->setCellValue($col."1", $vi['name']."-".$vi['id']);

         }


        // 设置表头信息
      /*  $obj = $objPHPExcel->setActiveSheetIndex(0);
        $obj     ->setCellValue('A1', '用户名')
            ->setCellValue('B1', '用户昵称')
            ->setCellValue('C1', '手机号')
            ->setCellValue('D1', '手机号');*/

        /*--------------开始从数据库提取信息插入Excel表中------------------*/

        /*   $i=2;  //定义一个i变量，目的是在循环输出数据是控制行数
           $count = count($sql);  //计算有多少条数据
           for ($i = 2; $i <= $count+1; $i++) {
               $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $sql[$i-2]['username']);
               $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $sql[$i-2]['nickname']);
               $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $sql[$i-2]['mobile']);
           }*/


        /*--------------下面是设置其他信息------------------*/

        $objPHPExcel->getActiveSheet()->setTitle('1111111111');      //设置sheet的名称
        $objPHPExcel->setActiveSheetIndex(0);                   //设置sheet的起始位置
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');   //通过PHPExcel_IOFactory的写函数将上面数据写出来

        $PHPWriter = \PHPExcel_IOFactory::createWriter( $objPHPExcel,"Excel2007");
        $filename = "salary_".time();
        ob_clean();
        header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $PHPWriter->save("php://output"); exit;//表示在$path路径下面生成demo.xlsx文件

    }


    public function out($item_info)
    {

        $objPHPExcel = new \PHPExcel();
        $objPHPExcel_Worksheet = new \PHPExcel_Worksheet();
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
        $objPHPExcelCell = new \PHPExcel_Cell(null,null,$objPHPExcel_Worksheet);

        // 实例化完了之后就先把数据库里面的数据查出来
     //   $sql = model('ProductAccess')->select();

        //只要表头
        $sql = $item_info;

        $count = count($item_info);
        $arr = [];
        foreach($item_info as $kc=>$vc){
            $arr[0][$kc] = $vc['name'];
        }
        $maxColumn = count($arr[0]);
        $maxRow    = count($arr);

        for ($i = 0; $i < $maxColumn; $i++) {
            for ($j = 0; $j < $maxRow; $j++) {
                $pCoordinate = $objPHPExcelCell::stringFromColumnIndex($i) . '' . ($j + 1);
                $pValue      = $arr[$j][$i];
                $objPHPExcel->getActiveSheet()->setCellValue($pCoordinate, $pValue);
            }
        }



        // 设置表头信息
    /*    $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '用户名')
            ->setCellValue('B1', '用户昵称')
            ->setCellValue('C1', '手机号');*/

        /*--------------开始从数据库提取信息插入Excel表中------------------*/

      /*  $i=2;  //定义一个i变量，目的是在循环输出数据是控制行数
        $count = count($sql);  //计算有多少条数据
        for ($i = 2; $i <= $count+1; $i++) {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $arr[$i-2]['username']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $arr[$i-2]['nickname']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $arr[$i-2]['mobile']);
        }*/


        /*--------------下面是设置其他信息------------------*/

        $objPHPExcel->getActiveSheet()->setTitle('productaccess');      //设置sheet的名称
        $objPHPExcel->setActiveSheetIndex(0);                   //设置sheet的起始位置
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');   //通过PHPExcel_IOFactory的写函数将上面数据写出来

        $PHPWriter = \PHPExcel_IOFactory::createWriter( $objPHPExcel,"Excel2007");

        header('Content-Disposition: attachment;filename="userinfo.xlsx"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $PHPWriter->save("php://output"); //表示在$path路径下面生成demo.xlsx文件

    }




    //根据字段数量获取对应的表头名称
    public function getExcelHeaderName(){
        $excel_config = config('Excel');
    }









}
