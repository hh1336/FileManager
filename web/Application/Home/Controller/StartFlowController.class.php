<?php

namespace Home\Controller;

use Home\Common\FIdConst;
use Home\Service\StartFlowService;
use Home\Service\UserService;

class StartFlowController extends PSIBaseController
{
  public function index()
  {
    $this->initVar();
    $us = new UserService();
    if (!$us->hasPermission(FIdConst::WJGL_FQLC)) {
      $this->gotoLoginPage("/Home/User/index");
    }
    $this->assign("title", "文件管理");

    $this->display();
  }

  public function loadRunFlow()
  {
    if (IS_POST) {
      $params = array();
      $sf = new StartFlowService();
      $this->ajaxReturn($sf->loadRunFlow($params));
    }
  }

  public function loadCheckFlow()
  {
    if (IS_POST) {
      $sf = new StartFlowService();
      $this->ajaxReturn($sf->loadCheckFlow());
    }
  }

  public function previewFile()
  {
    if (IS_POST) {
      $params = array(
        "flow_id" => I("post.flowId"),
        "file_path" => I("post.filePath"),
        "ext" => I("post.ext"),
        "file_name" => I("post.fileName")
      );
      $sf = new StartFlowService();
      $this->ajaxReturn($sf->previewFile($params));
    }
  }

  public function saveFlow()
  {
    if (IS_POST) {
      $params = array(
        'id' => I("post.id"),
        'run_name' => I("post.runName"),
        'flow_id' => I("post.flowId"),
        'is_urgent' => I("post.isUrgent")
      );
      $sf = new StartFlowService();
      $this->ajaxReturn($sf->saveFlow($params));
    }
  }
}
