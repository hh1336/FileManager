<?php

namespace Home\Controller;

use Home\Common\FIdConst;
use Home\Service\BizlogService;
use Home\Service\FIdService;
use Home\Service\MainMenuService;
use Home\Service\UserService;

/**
 * 主菜单Controller
 *
 * @author 李静波
 *
 */
class MainMenuController extends PSIBaseController
{

  /**
   * 页面跳转
   */
  public function navigateTo()
  {
    $this->assign("uri", __ROOT__ . "/");

    $fid = I("get.fid");

    // $t == 1的时候，是从常用功能链接点击而来的
    // $t == 2的时候，是从快捷访问而来
    $t = I("get.t");

    $fidService = new FIdService();
    $fidService->insertRecentFid($fid);
    $fidName = $fidService->getFIdName($fid);
    if ($fidName) {
      // 记录业务日志

      $bizLogService = new BizlogService();

      if ($t == "1") {
        $bizLogService->insertBizlog("通过常用功能进入模块：" . $fidName, "常用功能");
      } else if ($t == "2") {
        $bizLogService->insertBizlog("通过快捷访问进入模块：" . $fidName, "快捷访问");
      } else {
        $bizLogService->insertBizlog("通过主菜单进入模块：" . $fidName);
      }
    }
    if (!$fid) {
      redirect(__ROOT__ . "/Home");
    }

    if (substr($fid, 0, 2) == "ct") {
      // 码表
      redirect(__ROOT__ . "/Home/CodeTable/run?fid={$fid}");
    } else {
      // 系统模块
      switch ($fid) {
        case FIdConst::WJGL:
          //文件管理
          redirect(__ROOT__ . "/Home/FileManager/index");
          break;
        case FIdConst::WJGL_WDSP:
          //我的审批
          redirect(__ROOT__ . "/Home/Examine/index");
          break;
        case FIdConst::WJGL_LCSZ:
          //流程设计
          redirect(__ROOT__ . "/Home/ProcessDesign/index");
          break;
          case FIdConst::WJGL_FQLC:
          //发起流程
          redirect(__ROOT__ . "/Home/StartFlow/index");
          break;

        case FIdConst::ABOUT:
          // 修改我的密码
          redirect(__ROOT__ . "/Home/About/index");
          break;
        case FIdConst::RELOGIN:
          // 重新登录
          $us = new UserService();
          $us->clearLoginUserInSession();
          redirect(__ROOT__ . "/Home");
          break;
        case FIdConst::CHANGE_MY_PASSWORD:
          // 修改我的密码
          redirect(__ROOT__ . "/Home/User/changeMyPassword");
          break;
        case FIdConst::USR_MANAGEMENT:
          // 用户管理
          redirect(__ROOT__ . "/Home/User");
          break;
        case FIdConst::PERMISSION_MANAGEMENT:
          // 权限管理
          redirect(__ROOT__ . "/Home/Permission");
          break;
        case FIdConst::BIZ_LOG:
          // 业务日志
          redirect(__ROOT__ . "/Home/Bizlog");
          break;
        case FIdConst::BIZ_CONFIG:
          // 业务设置
          redirect(__ROOT__ . "/Home/BizConfig");
          break;
        case FIdConst::CODE_TABLE:
          // 码表设置
          redirect(__ROOT__ . "/Home/CodeTable/index");
          break;
        case FIdConst::MAIN_MENU:
          // 主菜单维护
          redirect(__ROOT__ . "/Home/MainMenu/maintainIndex");
          break;
        case FIdConst::SYS_DICT:
          // 系统数据字典
          redirect(__ROOT__ . "/Home/SysDict/index");
          break;
        case FIdConst::FORM_SYSTEM:
          // 自定义表单
          redirect(__ROOT__ . "/Home/Form/index");
          break;
        case FIdConst::FORM_VIEW_SYSTEM_DEV:
          // 表单视图开发助手
          redirect(__ROOT__ . "/Home/FormView/devIndex");
          break;
        default:
          redirect(__ROOT__ . "/Home");
      }
    }
  }

  /**
   * 返回生成主菜单的JSON数据
   * 目前只能处理到生成三级菜单的情况
   */
  public function mainMenuItems()
  {
    if (IS_POST) {
      $ms = new MainMenuService();

      $this->ajaxReturn($ms->mainMenuItems());
    }
  }

  /**
   * 常用功能
   */
  public function recentFid()
  {
    if (IS_POST) {
      $fidService = new FIdService();
      $data = $fidService->recentFid();

      $this->ajaxReturn($data);
    }
  }

  /**
   * 主菜单维护 - 主界面
   */
  public function maintainIndex()
  {
    $us = new UserService();

    if ($us->hasPermission(FIdConst::MAIN_MENU)) {
      $this->initVar();

      $this->assign("title", "主菜单维护");

      $this->display();
    } else {
      $this->gotoLoginPage("/Home/MainMenu/maintainIndex");
    }
  }

  /**
   * 查询所有的主菜单项 - 主菜单维护模块中使用
   */
  public function allMenuItemsForMaintain()
  {
    if (IS_POST) {
      $service = new MainMenuService();
      $this->ajaxReturn($service->allMenuItemsForMaintain());
    }
  }

  /**
   * Fid自定义字段 - 查询数据
   */
  public function queryDataForFid()
  {
    if (IS_POST) {
      $params = [
        "queryKey" => I("post.queryKey")
      ];

      $service = new MainMenuService();
      $this->ajaxReturn($service->queryDataForFid($params));
    }
  }

  /**
   * 菜单项自定义字段 - 查询数据
   */
  public function queryDataForMenuItem()
  {
    if (IS_POST) {
      $params = [
        "queryKey" => I("post.queryKey")
      ];

      $service = new MainMenuService();
      $this->ajaxReturn($service->queryDataForMenuItem($params));
    }
  }

  /**
   * 菜单项快捷访问自定义字段 - 查询数据
   */
  public function queryDataForShortcut()
  {
    if (IS_POST) {
      $params = [
        "queryKey" => I("post.queryKey")
      ];

      $service = new MainMenuService();
      $this->ajaxReturn($service->queryDataForShortcut($params));
    }
  }

  /**
   * 主菜单维护 - 新增或编辑菜单项
   */
  public function editMenuItem()
  {
    if (IS_POST) {
      $params = [
        "id" => I("post.id"),
        "fid" => I("post.fid"),
        "caption" => I("post.caption"),
        "parentMenuId" => I("post.parentMenuId"),
        "showOrder" => I("post.showOrder")
      ];

      $service = new MainMenuService();
      $this->ajaxReturn($service->editMenuItem($params));
    }
  }

  /**
   * 删除菜单项
   */
  public function deleteMenuItem()
  {
    if (IS_POST) {
      $params = [
        "id" => I("post.id")
      ];

      $service = new MainMenuService();
      $this->ajaxReturn($service->deleteMenuItem($params));
    }
  }

  /**
   * 某个菜单项的详情信息
   */
  public function menuItemInfo()
  {
    if (IS_POST) {
      $params = [
        "id" => I("post.id")
      ];

      $service = new MainMenuService();
      $this->ajaxReturn($service->menuItemInfo($params));
    }
  }
}
