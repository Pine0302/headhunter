<?php

namespace app\admin\controller\bbs;

use app\common\controller\Backend;
use think\Db;

/**
 * 社区用户
 *
 * @icon fa fa-circle-o
 */
class Member extends Backend
{
    
    /**
     * BbsMember模型对象
     * @var \app\admin\model\BbsMember
     */
    protected $model = null;
    protected $relationSearch = true;
    protected $searchFields = 'nick_name';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('BbsMember');
        $this->view->assign("genderList", $this->model->getGenderList());
        $this->view->assign("levelList", $this->model->getLevelList());
        $this->view->assign("isBannedList", $this->model->getIsBannedList());
        $this->view->assign("isBackList", $this->model->getIsBackList());
        $this->view->assign("bindMobileList", $this->model->getBindMobileList());
        $this->view->assign("isManageList", $this->model->getIsManageList());
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    public function detail($ids=NULL){

        $detail = Db::table('bbs_member')->where('id',$ids)->find();
        $this->view->assign("row", $detail);
        return $this->view->fetch();

    }


}
