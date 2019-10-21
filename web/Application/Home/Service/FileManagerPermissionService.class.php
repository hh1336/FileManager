<?php

namespace Home\Service;

use Home\DAO\FileManagerPermissionDAO;

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
}
