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

  public function loadRolePermission($params)
  {
    $db = $this->db;
    $file_fid = "";//得到文件的外键id
    $is_file = $db->query("select * from t_file_info where id = '%s'", $params['file_id']);
    if (count($is_file)) {
      $file_fid = $is_file[0]["file_fid"];
    } else {
      $file_fid = $db->query("select * from t_dir_info where id = '%s'", $params['file_id'])[0]['dir_fid'];
    }
    $sql = "select permission_fid from t_file_permission where role_id = '%s' and file_id = '%s'";
    $data = $db->query($sql, $params["role_id"], $file_fid);

    return $data;
  }

  public function setRolePermission($params)
  {
    $db = $this->db;

    $file_fid = "";//得到文件的外键id
    $is_file = $db->query("select file_fid from t_file_info where id = '%s'", $params['file_id']);


    if (count($is_file)) {
      $file_fid = $is_file[0]["file_fid"];
    } else {
      $file_fid = $db->query("select dir_fid from t_dir_info where id = '%s'", $params['file_id'])[0]['dir_fid'];
    }

    $sql = "select count(*) from t_file_permission 
        where role_id = '%s' and file_id = '%s' and permission_fid = '%s'";
    $is_container = $db->query($sql, $params["role_id"], $file_fid, $params["file_type"]);
    if (!($params["checked"] == "true")) {//选中，允许操作
      if ($is_container[0]["count(*)"]) {
        $del_sql = "delete from t_file_permission where role_id = '%s' and file_id = '%s' and permission_fid = '%s'";
        $db->execute($del_sql, $params["role_id"], $file_fid, $params["file_type"]);
      }
    } else {
      if (!$is_container[0]["count(*)"]) {//不存在数据则插入一条
        $insert_sql = "insert into t_file_permission (role_id,file_id,permission_fid)
                        values ('%s','%s','%s')";
        $db->execute($insert_sql, $params["role_id"], $file_fid, $params["file_type"]);
      }
    }

    $ids = $db->query("select id from t_dir_info where parent_dir_id = '%s' and is_del = 0", $params["file_id"]);
    if ($params['file_type'] == FIdConst::WJGL_DOWN_FILE) {
      $file_ids = $db->query("select id from t_file_info 
      where parent_dir_id = '%s' and is_del = 0", $params["file_id"]);
      $ids = array_merge_recursive($ids, $file_ids);
    }
    foreach ($ids as $arr) {
      $params["file_id"] = $arr['id'];
      $this->setRolePermission($params);
    }


    $rs["success"] = true;
    $rs["msg"] = "操作成功";
    return $rs;
  }

  public function hasPermission($params)
  {
    $db = $this->db;
    $file_fid = "";//得到文件的外键id
    $is_file = $db->query("select * from t_file_info where id = '%s'", $params['file_id']);
    if (count($is_file)) {
      $file_fid = $is_file[0]["file_fid"];
    } else {
      $file_fid = $db->query("select * from t_dir_info where id = '%s'", $params['file_id'])[0]['dir_fid'];
    }

    $sql = "SELECT	count(*) FROM
	          t_file_permission AS fp
	          LEFT JOIN t_role_user AS ru ON ru.role_id = fp.role_id
	          LEFT JOIN t_permission AS p ON fp.permission_fid = p.fid 
	          WHERE
	          ru.user_id = '%s' 
	          AND fp.file_id = '%s' 
	          AND p.fid = '%s' ";
    $counts = $db->query($sql, $params["user_id"], $file_fid, $params["fid"]);

    return $counts["0"]["count(*)"] > 0;
  }

}
