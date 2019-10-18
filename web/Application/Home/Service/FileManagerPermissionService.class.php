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
    $dao = new FileManagerPermissionDAO($this->db());
    return $dao->loadRole();
  }
}
