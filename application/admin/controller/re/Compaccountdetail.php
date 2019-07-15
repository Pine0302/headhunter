<?php

namespace app\admin\controller\re;

use app\common\controller\Backend;
use app\common\library\Order;
use think\Session;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Compaccountdetail extends Backend
{
    
    /**
     * ReCompaccountdetail模型对象
     * @var \app\admin\model\ReCompaccountdetail
     */
    protected $model = null;
    protected $dataLimit = 'personal'; //默认基类中为false，表示不启用，可额外使用auth和personal两个值
    protected $dataLimitField = 'admin_id'; //数据关联字段,当前控制器对应的模型表中必须存在该字段
    protected $noNeedRight = array('wepay','alipay','detail');

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
            foreach($list as $kl=>$vl){
                $list[$kl]['showtype'] =   ($list[$kl]['status']==1) ? $list[$kl]['materia'] : 3;
                if($vl['way']==2){
                    $list[$kl]['materia'] = $vl['banktype'];
                }else{
                    $list[$kl]['materia'] = ($vl['materia']==1) ? '支付宝':'微信';
                }
                $list[$kl]['reCompany'] = $vl['re_company'];
                $list[$kl]['way'] = ($vl['way']==1) ? '存入':'取出';

                $list[$kl]['method'] = ($vl['method']==1) ? '后台存款':'提现';
                $list[$kl]['status'] = ($vl['status']==1) ? '已申请':(($vl['status']==2) ? '支付中':'已完成');

            }
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

    //支付宝支付
    public function alipay($ids = NULL){
        $row = $this->model->get($ids);
        $param['trade_no'] = $row->order_id;
        $param['subject'] = 'B2P充值';
        $param['total_amount'] = $row->cash;

        header('Location: https://'.$_SERVER['HTTP_HOST'].'/pay/Alipay/pay?WIDout_trade_no='.$param['trade_no']."&WIDsubject=".$param['subject']."&WIDtotal_amount=". $param['total_amount']."&WIDbody=招聘网订单");
       // header('Location: https://'.$_SERVER['HTTP_HOST'].'/pay/Notifyurl/index?WIDout_trade_no='.$param['trade_no']."&WIDsubject=".$param['subject']."&WIDtotal_amount=". $param['total_amount']."&WIDbody=招聘网订单");
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
