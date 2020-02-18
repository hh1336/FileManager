<?php

namespace Home\Controller;

use Home\Common\FIdConst;
use Home\Service\ReceiveFlowService;
use Home\Service\UserService;

class ReceiveFlowController extends PSIBaseController
{
  public function index()
  {
    $this->initVar();
    $us = new UserService();
    if (!$us->hasPermission(FIdConst::GZL_JSLC)) {
      $this->gotoLoginPage("/Home/User/index");
    }
    $this->assign("title", "接收流程");

    $this->display();
  }

  public function loadData()
  {
    if (IS_POST) {
      $params = array(
        'query_name' => I("post.queryName"),
        'query_type' => I("post.queryType")
      );
      $rfs = new ReceiveFlowService();
      $this->ajaxReturn($rfs->loadData($params));
    }
  }

  public function receive()
  {
    if (IS_POST) {
      $params = array(
        'id' => I("post.id"),
        'is_sing' => I("post.isSing")
      );
      $rfs = new ReceiveFlowService();
      $this->ajaxReturn($rfs->receive($params));
    }
  }
}
