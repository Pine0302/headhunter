<?php

namespace app\admin\controller\re;

use app\common\controller\Backend;
use app\common\library\Order;
use think\Session;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Compwith extends Backend
{
    
    /**
     * ReCompaccountdetail模型对象
     * @var \app\admin\model\ReCompaccountdetail
     */
    protected $model = null;
    protected $dataLimit = true;
    protected $noNeedRight = array('wepay','alipay','detail','pass','index');

    protected $relationSearch = true;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('ReCompaccountdetail');
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
            $list1 = array();
            foreach($list as $kl=>$vl){
                $list[$kl]['reCompany'] = $vl['re_company'];
              /*  $list[$kl]['showtype'] =   ($list[$kl]['status']==1) ? 1 : 3;*/
                $list[$kl]['showtype'] =   $list[$kl]['status'];
                $list[$kl]['method'] = ($vl['method']==1) ? '后台存款':'提现';
                $list[$kl]['status'] = ($vl['status']==1) ? '已申请':(($vl['status']==2) ? '支付中':($vl['status']==3 ? "已完成":"已拒绝") );
                if($vl['way']==2){
                    $list[$kl]['materia'] = $vl['banktype'];
                    $list1[] =  $list[$kl];
                    $list[$kl]['way']="取出";
                }else{
                    $list[$kl]['materia'] = ($vl['materia']==1) ? '支付宝':'微信';

                }
                $list[$kl]['way'] = ($vl['way']==1) ? '存入':'取出';


            }
            $total = count($list1);
            $list = $list1;
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */



    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            //设置过滤方法
            $admin_session = Session::get('admin');
            $params['admin_id'] = $admin_session['id'];
            if ($params['way'] ==1 ){
                $Order = new Order();
                $params['order_id'] = $Order->createOrder('b2p');
                $params['method'] = 1;
                $params['create_at'] = date("Y-m-d H:i:s");
                $params['status'] = 1;
            }else{
                $Order = new Order();
                $params['order_id'] = $Order->createOrder('p2b');
                $params['method'] = 2;
                $params['create_at'] = date("Y-m-d H:i:s");
                $params['status'] = 1;
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
            $params = [ 'status'=>3 ];
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

                    $order_id = $row->order_id;
                    $re_company_id = $row->re_company_id;
                    $cash = $row->cash;

                    $params_re_compaccountdetail = [
                        'pay_time'=>date("Y-m-d H:i:s",time()),
                        'status'=>3,
                    ];

                    $old_company_info = Db::table('re_company')->where('id',$re_company_id)->find();
                    $new_account = floatval($old_company_info['account']) - floatval($cash);
                    $param_re_company = ['account'=>$new_account];
                    $param_cash_log = [
                        're_company_id'=>$re_company_id,
                        'way' => 2,
                        'tip' => '企业提现',
                        'type' =>8,
                        'cash' =>$cash,
                        're_compaccountdetail_id' =>$ids,
                        'order_no' =>$order_id,
                        'status' =>1,
                        'update_at' =>date("Y-m-d H:i:s",time()),
                        'admin_id'=>$old_company_info['admin_id'],
                    ];
                    Db::startTrans();
                    try {
                        //更新商家账单详情表
                        Db::name('re_compaccountdetail')
                            ->where('order_id', $order_id)
                            ->data($params_re_compaccountdetail)
                            ->update();
                        //更新商信息
                        Db::name('re_company')
                            ->where('id', $re_company_id)
                            ->data($param_re_company)
                            ->update();
                    //    Db::name('pay_log')->insert($arr_pay_log);
                        //添加cash_log表
                        $result =  Db::name('cash_log')->insert($param_cash_log);
                        // 提交事务
                        Db::commit();
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                    }

                   // $result = $row->allowField(true)->save($params);
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
            $params = [ 'status'=>4 ];
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



    //支付宝支付
    public function alipay($ids = NULL){
        $row = $this->model->get($ids);
       // $this->redirect('/')
        //支付宝支付
    }




    //微信支付

    public function wepay($ids = NULL){
        $row = $this->model->get($ids);
        $id = $row->id;
        $cash = $row->cash;
        $re_company_id = $row->re_company_id;
        $order_id = $row->order_id;
        $arr = [
            'id'=>$id,
            'cash'=>$cash,
            're_company_id'=>$re_company_id,
            'order_id'=>$order_id,
        ];
        $req = base64_encode(\GuzzleHttp\json_encode($arr));
        $this->redirect('/pay/Weixinpay/qr_pay?req='.$req);

        //支付宝支付
    }

    public function pay($ids = NULL){
        $row = $this->model->get($ids);
     //
        //支付宝支付
    }


}
