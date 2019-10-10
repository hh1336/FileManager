<?php

namespace Home\Controller;

use Home\Common\FIdConst;
use Home\Service\UserService;
use Home\Service\FormService;

/**
 * 自定义表单Controller
 *
 * @author 李静波
 *        
 */
class FormController extends PSIBaseController
{

  /**
   * 自定义表单 - 主页面
   */
  public function index()
  {
    $us = new UserService();

    if ($us->hasPermission(FIdConst::FORM_SYSTEM)) {
      $this->initVar();

      $this->assign("title", "自定义表单");

      $this->display();
    } else {
      $this->gotoLoginPage("/Home/Form/index");
    }
  }

  /**
   * 表单分类列表
   */
  public function categoryList()
  {
    if (IS_POST) {
      $service = new FormService();
      $this->ajaxReturn($service->categoryList());
    }
  }

  /**
   * 新增或编辑表单分类
   */
  public function editFormCategory()
  {
    $params = [
      "id" => I("post.id"),
      "code" => I("post.code"),
      "name" => I("post.name")
    ];

    $service = new FormService();
    $this->ajaxReturn($service->editFormCategory($params));
  }

  /**
   * 删除表单分类
   */
  public function deleteFormCategory()
  {
    $params = [
      "id" => I("post.id"),
    ];

    $service = new FormService();
    $this->ajaxReturn($service->deleteFormCategory($params));
  }
}
