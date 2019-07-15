<?php

namespace app\admin\controller\bbs;

use app\common\controller\Backend;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Complain extends Backend
{
    
    /**
     * BbsComplain模型对象
     * @var \app\admin\model\BbsComplain
     */
    protected $model = null;
    protected $relationSearch = true;
    protected $searchFields = 'bbsMember.nick_name,bbsPost.content';
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('BbsComplain');
        $this->bbsmodel = model('BbsPost');;

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
            //   list($where, $sort, $order, $offset, $limit) = $this->buildparamsWithAuth();

            $total = $this->model
              //  ->with("reResume,reCompany,reJob,recUser")
                ->with("bbsMember,bbsPost")
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with("bbsMember,bbsPost")
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
           foreach($list as $kl=>$vl){
               $list[$kl]['bbsMember'] = $list[$kl]['bbs_member'];
                $list[$kl]['bbsPost'] = $list[$kl]['bbs_post'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }



    /**
     * 详情
     */
    public function detail($ids = NULL)
    {
        //获取帖子id
        $complain_info = Db::table('bbs_complain')->where('id','=',$ids)->find();

        $row =  $this->bbsmodel->get($complain_info['bbs_post_id']);


        if(!empty($row->imgs)){
            $imgs = unserialize($this->mb_unserialize($row->imgs));
            $imgs_arr = [];
            $imgs_str = '';
            foreach($imgs as $kvv=>$vvv){
                // $imgs_arr[] ="/postimg/".$vvv;
                $imgs_arr[] ="https://".$_SERVER['HTTP_HOST']."/postimg/".$vvv;
                $imgs_str = $imgs_str."/postimg/".$vvv.",";
            }
            $imgs_str = rtrim($imgs_str, ",");
            $row->imgs = $imgs_arr;
            //    $row->imgs = $imgs_str;
        }else{
            $imgs_arr = [];
        }


        //如果简历状态为未查看,设置为已查看
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

        $row->ch_status = ($row->status==1) ? '显示':'隐藏';
        $this->view->assign("imgs_arr", $imgs_arr);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    public function mb_unserialize($str)
    {
        return preg_replace_callback('#s:(\d+):"(.*?)";#s', function ($match) {
            return 's:' . strlen($match[2]) . ':"' . $match[2] . '";';
        }, $str);
    }




}
