<?php

namespace Home\Controller;

use Home\Common\FIdConst;
use Home\Service\UserService;

class  ExamineController extends PSIBaseController
{
  public function index()
  {
    $this->initVar();
    $us = new UserService();
    if (!$us->hasPermission(FIdConst::WJGL_WDSP)) {
      $this->gotoLoginPage("/Home/User/index");
    }
    $this->assign("title", "我的审批");

    $this->display();
  }

}
