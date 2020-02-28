<?php


namespace Home\Controller;


use Home\Common\FIdConst;
use Home\Service\JointlySignService;
use Home\Service\UserService;

class JointlySignController extends PSIBaseController
{
  public function index()
  {
    $this->initVar();
    $us = new UserService();
    if (!$us->hasPermission(FIdConst::GZL_HQSH)) {
      $this->gotoLoginPage("/Home/User/index");
    }
    $this->assign("title", "会签");

    $this->display();
  }

  public function loadData()
  {
    if (IS_POST) {
      $params = array(
        'query_name' => I("post.queryName"),
        'query_type' => I("post.queryType")
      );

      $jss = new JointlySignService();
      $this->ajaxReturn($jss->loadData($params));
    }
  }

  public function flowAdvance()
  {
    if (IS_POST) {
      $params = array(
        "sign_id" => I("post.signId"),
        "run_process_id" => I("post.runProcessId")
      );
      $jss = new JointlySignService();
      $this->ajaxReturn($jss->flowAdvance($params));
    }
  }

  public function jointlySignAdvance()
  {
    if (IS_POST) {
      $params = array(
        "run_process_id" => I("post.runProcessId")
      );
      $jss = new JointlySignService();
      $this->ajaxReturn($jss->jointlySignAdvance($params));
    }
  }

  public function loadJointlyCount()
  {
    if (IS_POST) {
      $params = array(
        "run_process_id" => I("post.runProcessId")
      );
      $jss = new JointlySignService();
      $this->ajaxReturn($jss->loadJointlyCount($params));
    }
  }

  public function pass()
  {
    if (IS_POST) {
      $params = array(
        "sign_id" => I("post.signId"),
        "content" => I("post.content")
      );
      $jss = new JointlySignService();
      $this->ajaxReturn($jss->pass($params));
    }
  }

  public function fail()
  {
    if (IS_POST) {
      $params = array(
        "sign_id" => I("post.signId"),
        "content" => I("post.content")
      );
      $jss = new JointlySignService();
      $this->ajaxReturn($jss->fail($params));
    }
  }
}
