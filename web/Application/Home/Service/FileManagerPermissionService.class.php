<?php

namespace Home\Service;

use Home\Common\DemoConst;
use Home\Common\FIdConst;
use Home\DAO\FileManagerPermissionDAO;
use Home\DAO\UserDAO;

class FileManagerPermissionService extends PSIBaseExService
{
  /**加载角色
   *
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

  public function setRolePermission($params){
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $rs["success"]  = false;
    $us = new UserService();
    $userId = $this->getLoginUserId();

    if (!$userId == DemoConst::ADMIN_USER_ID) {
      // admin 用户是超级管理员
      if(!$us->hasPermission(FIdConst::WJGL_DWJQX)){
        $rs["msg"] = "权限设置";
        return $rs;
      }
    }

    $dao = new FileManagerPermissionDAO($this->db());
    $rs = $dao->setRolePermission($params);
    return $rs;

  }

  /**判断权限
   * @param null $fid
   */
  public function hasPermission($params,$fid){

    $result = session("loginUserId") != null;
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
}
