<?php

namespace Home\Controller;

use Home\Common\FIdConst;
use Home\Service\UserService;
use Home\Service\ProcessDesignService;

class ProcessDesignController extends PSIBaseController
{

  public function index()
  {
    $this->initVar();
    $us = new UserService();
    if (!$us->hasPermission(FIdConst::WJGL_LCSZ)) {
      $this->gotoLoginPage("/Home/User/index");
    }
    $this->assign("title", "流程设计");
    $this->display();
  }

  public function loadConfig()
  {
    if (IS_POST) {
      $pds = new ProcessDesignService();
      $this->ajaxReturn($pds->loadConfig());
    }
  }

  public function saveConfig()
  {
    if (IS_POST) {
      $params = array(
        "9004-01" => I("post.open_reviewing"),
        "9004-02" => I("post.open_file_reviewing"),
        "9004-03" => I("post.open_dir_reviewing"),
      );
      $pds = new ProcessDesignService();
      $this->ajaxReturn($pds->saveConfig($params));
    }
  }

  public function loadProcess()
  {
    if (IS_POST) {
      $params = array(
        "flow_name" => I("post.ProcessName"),
        "flow_type" => I("post.ProcessType"),
        "file_type" => I("post.ProcessAction"),
        "status" => I("post.Status")
      );
      $pds = new ProcessDesignService();
      $this->ajaxReturn($pds->loadProcess($params));
    }
  }

  public function saveFlow()
  {
    if (IS_POST) {
      $params = array(
        "id" => I("post.id"),
        "flow_name" => I("post.flowName"),
        "file_type" => I("post.fileType"),
        "flow_type" => I("post.flowType"),
        "sort_order" => I("post.sortOrder")
      );
      $pds = new ProcessDesignService();
      $this->ajaxReturn($pds->saveFlow($params));
    }
  }

  public function disableFlow()
  {
    if (IS_POST) {
      $params = array(
        "id" => I("post.Id")
      );
      $pds = new ProcessDesignService();
      $this->ajaxReturn($pds->disableFlow($params));
    }
  }

  public function openFlow()
  {
    if (IS_POST) {
      $params = array(
        "id" => I("post.Id")
      );
      $pds = new ProcessDesignService();
      $this->ajaxReturn($pds->openFlow($params));
    }
  }

  public function getNodeInfo()
  {
    if (IS_POST) {
      $params = array(
        "id" => I("post.id")
      );
      $pds = new ProcessDesignService();
      $this->ajaxReturn($pds->getNodeInfo($params));
    }
    $this->ajaxReturn();
  }

  public function saveProcess()
  {
    if (IS_POST) {
      $params = array(
        "id" => I("post.id"),
        "process_name" => I("post.processName"),
        "process_type" => I("post.processType"),
        "flow_id" => I("post.flowId"),
        "processing_mode" => I("post.processingMode"),
        "is_user_end" => I("post.isUserEnd"),
        "is_userop_pass" => I("post.isUseropPass"),
        "respon_ids" => I("post.responIds"),
        "respon_text" => I("post.responText"),
        "is_sing" => I("post.isSing"),
        "user_ids" => I("post.userIds"),
        "user_text" => I("post.userText"),
        "role_ids" => I("post.roleIds"),
        "role_text" => I("post.roleText"),
        "is_back" => I("post.isBack")
      );

      $pds = new ProcessDesignService();
      $this->ajaxReturn($pds->saveProcess($params));
    }
  }

  public function loadDesign()
  {
    if (IS_POST) {
      $params = array(
        'id' => I("post.id")
      );
      $pds = new ProcessDesignService();
      $this->ajaxReturn($pds->loadDesign($params));
    }
  }

  public function saveDesign()
  {
    if (IS_POST) {
      $params = array(
        'id' => I('post.id'),
        'process_to' => I('post.processTo'),
        'set_top' => I('post.setTop'),
        'set_left' => I('post.setLeft')
      );
      $pds = new ProcessDesignService();
      $this->ajaxReturn($pds->saveDesign($params));
    }
  }

  public function deleteNode()
  {
    if (IS_POST) {
      $params = array(
        'id' => I("post.id")
      );
      $pds = new ProcessDesignService();
      $this->ajaxReturn($pds->deleteNode($params));
    }
  }
}
