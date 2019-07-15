<?php

namespace app\admin\controller\re;

use app\common\controller\Backend;
use think\Db;


/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Advance extends Backend
{
    
    /**
     * ReAdvance模型对象
     * @var \app\admin\model\ReAdvance
     */
    protected $model = null;
    protected $dataLimit = 'personal'; //默认基类中为false，表示不启用，可额外使用auth和personal两个值
    protected $dataLimitField = 'admin_id'; //数据关联字段,当前控制器对应的模型表中必须存在该字段

    protected $relationSearch = true;

    protected $noNeedRight = ['detail','pass','deny'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('ReAdvance');

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
            //  list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            list($where, $sort, $order, $offset, $limit) = $this->buildparamsWithAuth();
            $total = $this->model
                ->with("reCompany,user,reResume")
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with("reCompany,user,reResume")
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            foreach($list as $kl=>$vl){
                $list[$kl]['reCompany'] = $list[$kl]['re_company'];
                $list[$kl]['reResume'] = $list[$kl]['re_resume'];
                $list[$kl]['showtype'] = $list[$kl]['status'];
                $list[$kl]['status'] = $vl['status']==0 ? '已申请' : ($vl['status']==1 ? '已通过':'已拒绝');
               /* switch( $list[$kl]['offer_status'])
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



    //
    public function detail($ids = NULL){
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
                    $result_avance = $this->passAdvance($row->user_id,$row->re_company_id,$row->amount,$row->id);
                    if($result_avance==1){
                        $this->success();
                    }elseif($result_avance==2){
                        $this->error('公司余额不足,请先去充值!');
                    }else{
                        $this->error('系统繁忙,请稍候再试');
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

    public function passAdvance($user_id,$re_company_id,$amount,$re_advance_id){
        //查看公司资金是否足够
        $company_info = Db::table('re_company')->where('id','=',$re_company_id)->find();
        $user_info = Db::table('user')->where('id','=',$user_id)->find();
        $now_date = date("Y-m-d H:i:s",time());
        if(!($company_info['account']<$amount)){
            //公司扣款
            //个人加款
            //cash_log
            //状态改变
            // 启动事务
            $params_company_account_update = [
                'account'=>$company_info['account']-$amount,
            ];
            $params_company_cash_log_update = [
                're_company_id'=>$company_info['id'],
                'apply_company_id'=>$company_info['id'],
                'user_id'=>$user_id,
                'apply_user_id'=>$user_id,
                'way'=>2,
                'tip'=>"工资预支(公司)",
                'cash'=>$amount,
                're_advance_id'=>$re_advance_id,
                'type'=>19,
                'status'=>1,
                'admin_id'=>$company_info['admin_id'],
                'update_at'=>$now_date,
            ];

            $arr_update_user = [
                'available_balance'=>number_format($user_info['available_balance'],2) + $amount,
                'total_balance'=>number_format($user_info['total_balance'],2) + $amount,
            ];
            $arr_update_user_cash_log = [
                're_company_id'=>$company_info['id'],
                'apply_company_id'=>$company_info['id'],
                'user_id'=>$user_id,
                'apply_user_id'=>$user_id,
                'way'=>1,
                'tip'=>"工资预支(个人)",
                'cash'=>$amount,
                're_advance_id'=>$re_advance_id,
                'type'=>20,
                'status'=>1,
                'admin_id'=>$company_info['admin_id'],
                'update_at'=>$now_date,
            ];


            $arr_update_advance = [
                'status'=>1,
                'update_at'=>$now_date,
            ];

            Db::startTrans();
            try {

                //公司付款;
                Db::table('re_company')->where('id', $re_company_id)->update($params_company_account_update);
                Db::table('cash_log')->insert($params_company_cash_log_update);  //"支付公司";

                //用户得款;
                Db::table('user')->where('id',$user_id)->update($arr_update_user);
                Db::table('cash_log')->insert($arr_update_user_cash_log);

                //修改状态
                Db::table('re_advance')->where('id',$re_advance_id)->update($arr_update_advance);
                // 提交事务
                Db::commit();
                return 1;

            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return 3;
            }
        }else{
            return 2;
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




















}
