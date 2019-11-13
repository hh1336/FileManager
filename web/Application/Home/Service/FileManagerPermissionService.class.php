<?php

namespace Home\Service;

use Home\Common\DemoConst;
use Home\Common\FIdConst;
use Home\DAO\FileManagerPermissionDAO;
use Home\DAO\UserDAO;

class FileManagerPermissionService extends PSIBaseExService
{
  /**
   * 加载角色
   */
  public function loadRole()
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $dao = new FileManagerPermissionDAO($this->db());
    return $dao->loadRole();
  }

  public function loadRolePermission($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $dao = new FileManagerPermissionDAO($this->db());
    return $dao->loadRolePermission($params);
  }

  public function setRolePermission($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $userId = $this->getLoginUserId();

    if (!$userId == DemoConst::ADMIN_USER_ID) {
      // admin 用户是超级管理员
      if (!$this->hasPermission(FIdConst::WJGL_DWJQX)) {
        return $this->notPermission();
      }
    }

    $dao = new FileManagerPermissionDAO($this->db());
    $params["login_id"] = $this->getLoginUserId();
    $rs = $dao->setRolePermission($params);
    return $rs;

  }

  /**
   * 权限判断
   * @param null $params
   * @param $fid
   * @return bool
   */
  public function hasPermission($params, $fid)
  {

    $result = session("userId") != null;
    if (!$result) {
      return false;
    }

    $userId = $this->getLoginUserId();

    if ($userId == DemoConst::ADMIN_USER_ID) {
      return true;
    }
    // 判断用户是否被禁用
    // 被禁用的用户，视为没有权限
    $ud = new UserDAO($this->db());
    if ($ud->isDisabled($userId)) {
      return false;
    }

    $dao = new FileManagerPermissionDAO($this->db());

    $params["user_id"] = $userId;
    $params["fid"] = $fid;

    return $dao->hasPermission($params);
  }

  public function setFileCRUDPermission($params, $type)
  {
    $dao = new FileManagerPermissionDAO($this->db());
    $params["login_id"] = $this->getLoginUserId();
    $permission_arr = [];
    if ($type == "dir") {
      $permission_arr = [
        FIdConst::WJGL_ADD_DIR,
        FIdConst::WJGL_EDIT_DIR,
        FIdConst::WJGL_DEL_DIR,
        FIdConst::WJGL_INTO_DIR,
        FIdConst::WJGL_MOVE_DIR,
        FIdConst::WJGL_UP_FILE,
        FIdConst::WJGL_DOWN_FILE
      ];
    } else if ($type == "file") {
      $permission_arr = [
        FIdConst::WJGL_YL_FILE,
        FIdConst::WJGL_EDIT_FILE,
        FIdConst::WJGL_DEL_FILE,
        FIdConst::WJGL_MOVE_FILE,
        FIdConst::WJGL_DOWN_FILE
      ];
    }

    foreach ($permission_arr as $fid) {
      $params["file_type"] = $fid;
      $dao->setRolePermission($params);
    }
  }
}
