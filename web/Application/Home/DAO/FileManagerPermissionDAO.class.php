<?php

namespace Home\DAO;

class FileManagerPermissionDAO extends PSIBaseExDAO
{

  public function loadRole()
  {
    $db = $this->db;
    $roles = $db->query("select id,name,code from t_role order by code asc");
    return $roles;
  }
}
