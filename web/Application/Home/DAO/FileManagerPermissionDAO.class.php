<?php

namespace Home\DAO;

use Home\Common\FIdConst;

class FileManagerPermissionDAO extends PSIBaseExDAO
{

  public function loadRole()
  {
    $db = $this->db;
    $roles = $db->query("select id,name,code from t_role order by code asc");
    return $roles;
  }

  public function loadRolePermission($params){
    $db = $this->db;
//    $params["role_id"] = '54CA9BDE-D052-11E9-8217-D8CB8AD4DC8A';
//    $params['file_id'] = '1F7A01AD-EE5E-11E9-8CB9-00FF2421C2C8';
    $sql = "select permission from t_file_permission where 1=1 and role_id = '%s' and file_id = '%s'";
    $data = $db->query($sql,$params["role_id"],$params['file_id']);

    return $data;
  }
}
