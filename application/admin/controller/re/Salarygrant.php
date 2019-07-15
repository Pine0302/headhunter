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
class Salarygrant extends Backend
{
    
    /**
     * ReSalarygrant模型对象
     * @var \app\admin\model\ReSalarygrant
     */
    protected $model = null;
    protected $dataLimit = 'personal'; //默认基类中为false，表示不启用，可额外使用auth和personal两个值
    protected $dataLimitField = 'admin_id'; //数据关联字段,当前控制器对应的模型表中必须存在该字段
    protected $noNeedRight = ['import','detail','helltest','pass'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('ReSalarygrant');
        $this->view->assign("statusList", $this->model->getStatusList());
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
            foreach($list as $kl=>$vl){
                $list[$kl]['status_text'] = ($vl['status']==1) ? "已发放":"未发放";
            }
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

        $admin_session = Session::get('admin');
        $admin_id = $admin_session['id'];


        $file_name = $data['file'];
        $file_path = $_SERVER['DOCUMENT_ROOT'].$file_name;
        $data = $this->importExecl($file_path);
        unset($data[1]);  //去掉栏目标题
        $excel_config = config('excel');
        //获取公司-用户列表
        $id_str = '';
        foreach($data as $kd=>$vd){
           $id_str = $id_str . $vd[$excel_config[0]].",";
        }
        $id_str = substr($id_str,0,(strlen($id_str)-1));
        $map['id'] = array('in',$id_str);
        $comp_user_arr = Db::table('re_usercomp')->where($map)->select();
        //整理公司-用户列表
        $sort_comp_user_arr = [];
        foreach($comp_user_arr as $kc=>$vc){
            $sort_comp_user_arr[$vc['id']] = $vc;
        }
        $insert = [];
        foreach($data as $kd=>$vd){
            $arr = [
                'user_id'=>$sort_comp_user_arr[$vd[$excel_config[0]]]['user_id'],
                're_company_id'=>$sort_comp_user_arr[$vd[$excel_config[0]]]['re_company_id'],
                'user_name' => $vd[$excel_config[1]],
                'mobile' => $vd[$excel_config[2]],
                'company_name' => $vd[$excel_config[3]],
                'salary' => $vd[$excel_config[4]],
                'tip'=>$vd[$excel_config[5]],
                'status'=>2,
                'admin_id'=>$admin_id,
                'create_at'=>date("Y-m-d H:i:s"),
                'update_at'=>date("Y-m-d H:i:s"),
            ];
            $insert[] = $arr;
        }
        $result = Db::table('re_salarygrant')->insertAll($insert);
        if(!empty($result)){
            $this->success();
        }else{
            $this->error();
        }
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
           // var_dump($extension);exit;
            $PHPReader = new \PHPExcel_Reader_CSV($objPHPExcel);
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

   /*     var_dump($rowCnt);
        var_dump($columnH);exit;*/
        $shared = new \PHPExcel_Shared_Date();
        $data = array();
        for($_row=1; $_row<=$rowCnt; $_row++){  //读取内容
            for($_column=0; $_column<=$columnCnt; $_column++){
                $cellId = $cellName[$_column].$_row;
                $cellValue = $currSheet->getCell($cellId)->getValue();

                if($cellValue instanceof PHPExcel_RichText){   //富文本转换字符串
                    $cellValue = $cellValue->__toString();
                }
                if(!empty($cellValue)){
                    $data[$_row][$cellName[$_column]] = $cellValue;
                }
                //$cellValue = $currSheet->getCell($cellId)->getCalculatedValue();  #获取公式计算的值
             /*   */

                /* if(strpos($cellId,'D') !== false){
                     if($cellId!="D1"){
                         $cellValue =  $shared ->ExcelToPHP($cellValue);   //时间转换为时间戳
                         $cellValue = date("Y-m-d",$cellValue);
                     }
                 }*/

            }
        }
        return $data;
    }



    /**
     * 发放
     */
    public function pass($ids = "")
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
            $cash = 0;
            //冻结金额
            foreach ($list as $k => $v)
            {
                $id = $v->id;
                $cash = $cash + $v->salary;
                if($v->status==1){
                    $this->error('请不要选择已发放的项目');exit;
                }
            }
            $check = $this->frozen($cash,$v['admin_id']);
            if($check==2){
                $this->error('余额不足,请先充值');exit;
            }
            //发放工资
            foreach ($list as $k => $v)
            {
               $id = $v->id;
               $result = $this->distribute($id);
               if($result){
                   $count += 1;
               }
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

    //冻结薪水
    public function frozen($cash,$admin_id){
        $company_info = Db::table('re_company')->where('admin_id','=',$admin_id)->find();
        if($company_info['account']<$cash){
            return 2;

        }else{
            $arr = [
                'account'=> $company_info['account']-$cash,
                'frozen'=> $company_info['frozen']+$cash,
            ];
            $result = Db::table('re_company')->where('admin_id','=',$admin_id)->update($arr);
            return empty($result) ? 2 : 1;
        }
    }


    //发放薪水
    public function distribute($id){
        $grant_info = Db::table('re_salarygrant')->where('id','=',$id)->find();
        $company_info = Db::table('re_company')->where('admin_id','=',$grant_info['admin_id'])->find();
        $user_info = Db::table('user')->where('id','=',$grant_info['user_id'])->find();
        $cash = $grant_info['salary'];
        $sql_company = "update re_company set frozen = frozen - ".$cash." where admin_id = ".$grant_info['admin_id'];
        $sql_user = "update user set available_balance = available_balance + ".$cash." where id = ".$grant_info['user_id'];
        $arr_cash_log_company = [
            'user_id'=>$grant_info['user_id'],
            're_company_id'=>$company_info['id'],
            'way'=>2,
            'tip'=>'企业发薪',
            'cash'=>$cash,
            're_salarygrant_id'=>$grant_info['id'],
            'type'=>9,
            'status'=>1,
            'admin_id'=>$grant_info['admin_id'],
            'update_at'=>date("Y-m-d H:i:s",time()),
        ];
        $arr_cash_log_user = [
            'user_id'=>$grant_info['user_id'],
            're_company_id'=>$company_info['id'],
            'apply_company_id'=>$company_info['id'],
            'way'=>1,
            'tip'=>'用户得薪',
            'cash'=>$cash,
            're_salarygrant_id'=>$grant_info['id'],
            'type'=>10,
            'status'=>1,
            'admin_id'=>$grant_info['admin_id'],
            'update_at'=>date("Y-m-d H:i:s",time()),
        ];

        $arr_grant_update = [
            'status'=>1,
            'finish_at'=>date("Y-m-d H:i:s",time()),
            'update_at'=>date("Y-m-d H:i:s",time()),
        ];
        $map_grant_update = [
            'id'=>$grant_info['id'],
        ];

        //给代理商扣除冻结金额
        //给用户增加余额
        //添加cash_log
        //修改发送状态和送达时间

        Db::startTrans();
        try{
            Db::query($sql_company);
            Db::query($sql_user);
            Db::table('cash_log')->insert($arr_cash_log_company);
            Db::table('cash_log')->insert($arr_cash_log_user);
            $result = Db::table('re_salarygrant')->where($map_grant_update)->update($arr_grant_update);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            $result = 0;
            Db::rollback();
        }

        return $result;




    }


    public function helltest(){
        $sql1 = "update re_company set frozen = frozen - 1 where id=1";
        $result = Db::query($sql1);
    }





}
