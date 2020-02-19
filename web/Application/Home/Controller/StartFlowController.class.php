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
    if (!$us->hasPermission(FIdConst::GZL_FQLC)) {
      $this->gotoLoginPage("/Home/User/index");
    }
    $this->assign("title", "发起流程");

    $this->display();
  }

  public function loadRunFlow()
  {
    if (IS_POST) {
      $params = array(
        'query_name' => I("post.queryName"),
        'query_type' => I("post.queryType")
      );
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
        'is_urgent' => I("post.isUrgent"),
        'remark' => I("post.remark")
      );
      $sf = new StartFlowService();
      $this->ajaxReturn($sf->saveFlow($params));
    }
  }

  public function startFlow()
  {
    if (IS_POST) {
      $params = array(
        'id' => I("post.id"),
        'flow_id' => I("post.flowId")
      );
      $sf = new StartFlowService();
      $this->ajaxReturn($sf->startFlow($params));
    }
  }
}
