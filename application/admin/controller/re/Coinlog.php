<?php

namespace app\admin\controller\re;

use app\common\controller\Backend;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Coinlog extends Backend
{
    
    /**
     * ReCoinLog模型对象
     * @var \app\admin\model\ReCoinLog
     */
    protected $model = null;
    protected $relationSearch = true;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('ReCoinLog');
      /*  $this->view->assign("wayList", $this->model->getWayList());
        $this->view->assign("methodList", $this->model->getMethodList());
        $this->view->assign("statusList", $this->model->getStatusList());*/
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
        $log_type = config("webset.coin_log");
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $this->model->removeOption();
            $total = $this->model
                ->with("user,company")
                ->where($where)
                ->order($sort, $order)
                ->count();
            $this->model->removeOption();
            $list = $this->model
                ->with("user,company")
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $this->model->removeOption();
            $webset = config('webset');
            $list = collection($list)->toArray();
            foreach($list as $kl=>$vl){
                $list[$kl]['user_type_text'] = $webset['id_type'][$vl['user_type']];
                $list[$kl]['way_text'] = ($vl['way']==1) ? '流入':'流出';
                $list[$kl]['method_text'] =  $webset['coin_log'][$vl['method']]['discription'];
                $list[$kl]['expire_at'] =  date("Y-m-d",$vl['expire_at']);
                /*   $list[$kl]['reCompany'] = $list[$kl]['re_company'];
                   $list[$kl]['reApplyCompany'] = $list[$kl]['re_apply_company'];
                   $list[$kl]['applyUser'] = $list[$kl]['apply_user'];*/
          /*
                $list[$kl]['ch_type'] = $log_type[$vl['type']]['discription'];*/
            }
            //总流入
            //$in = Db::table('re_coin_log')->where('way','=',1)->sum('num');
            $in = Db::table('pay_log')->where('type','in',[1,2])->sum('total_fee');
            $out = 0;
            $sum = $in/100-$out/100;
            $extend = [
                'in'=>$in/100,
                'out'=>$out/100,
                'sum'=>$sum,
            ];
            $result = array("total" => $total, "rows" => $list,"extend"=>$extend);
            return json($result);
        }
        return $this->view->fetch();
    }



}
