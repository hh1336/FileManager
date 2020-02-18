<?php

namespace Home\Controller;

use Home\Common\FIdConst;
use Home\Service\ExamineService;
use Home\Service\UserService;

class  ExamineController extends PSIBaseController
{
  public function index()
  {
    $this->initVar();
    $us = new UserService();
    if (!$us->hasPermission(FIdConst::GZL_WDSP)) {
      $this->gotoLoginPage("/Home/User/index");
    }
    $this->assign("title", "我的审批");

    $this->display();
  }

  public function loadFlow()
  {
    if (IS_POST) {
      $params = array(
        'query_name' => I("post.queryName"),
        'query_type' => I("post.queryType")
      );
      $ed = new ExamineService();
      $this->ajaxReturn($ed->loadFlow($params));
    }
  }

}