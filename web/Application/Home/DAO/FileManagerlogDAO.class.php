<?php

namespace Home\DAO;


use Think\Exception;

class FileManagerlogDAO extends PSIBaseExDAO
{
  /**记录操作记录
   * @param $params
   */
  public function log(&$params)
  {
    $db = $this->db;
    $params["log_id"] = $this->newId();
    $db->execute("insert into t_log 
                (id, action_time, action_user_id, is_del, remarks, action_info)
                values('%s','%s','%s',0,'%s','%s')",
      $params["log_id"], Date("Y-m-d H:i:s"), $params["login_user_id"],
      $params["log_info"], $params["action_info"]);
  }

  /**出错时删除操作记录
   * @param $id
   * @param $db
   */
  public function deleteLog($id)
  {
    $db = $this->db;
    $db->execute("delete from t_log where id = '%s'", $id);
    $db->execute("delete from t_log_action where log_id = '%s'", $id);
  }

  /**添加操作记录
   * @param $data
   * @param $db
   */
  public function addLogAction($data)
  {
    $db = $this->db;
    $data["id"] = $this->newId();
    $db->execute("insert into t_log_action ( id, action_type, file_type, file_id, log_id )
        values('%s','%s','%s','%s','%s')",
      $data["id"], $data["action_type"], $data["file_type"], $data["file_id"], $data["log_id"]);
  }

  /**加载版本
   * @param $params
   * @return mixed
   */
  public function loadLog($params)
  {
    $db = $this->db;
    if ($params["id"]) {//查看指定内容的操作历史
      $is_dir = $db->query("select * from t_dir_info where dir_fid = '%s' and is_del = 0", $params["id"]);
      $data = [];
      if (!count($is_dir)) {//查看文件历史
        $sql = "select	fi.id,concat(fi.file_name,'.',fi.file_suffix) as name,
	      fi.action_time,	fi.action_user_id,	fi.action_info,	u.name as action_user_name,
	      	'file' as type, fi.is_del,cu.name as create_user_name,fi.create_time
        from	t_file_info as fi 
        left join t_user as u on fi.action_user_id = u.id
        left join t_user as cu on cu.id = fi.create_user_id
        where	fi.file_fid = '%s' 
        order by	fi.is_del,fi.action_time desc
        limit %d,%d";
        $data = $db->query($sql, $params["id"], $params["start"], $params["limit"]);
        $rs["totalCount"] = $db->query("select count(*) from t_file_info 
        where file_fid = '%s'", $params["id"])[0]["count(*)"];
      } else {
        $sql = "select	di.id,di.dir_name as name, di.action_time,	di.action_user_id,
	      di.action_info,	u.name as action_user_name,'dir' as type, di.is_del,cu.name as create_user_name,fi.create_time
        from	t_dir_info as di 
        left join t_user as u on di.action_user_id = u.id
        left join t_user as cu on cu.id = di.create_user_id
        where	di.dir_fid = '%s' 
        order by	di.is_del,di.action_time desc
        limit %d,%d";
        $data = $db->query($sql, $params["id"], $params["start"], $params["limit"]);
        $rs["totalCount"] = count($is_dir);
      }
      foreach ($data as $i => $v) {
        $data[$i]["action_time"] = date("Y-m-d H:i:s", strtotime($data[$i]["action_time"]));
      }
      $rs["dataList"] = $data;
      return $rs;
    }


    $data = $db->query("select	l.id,	l.action_user_id,	u.name as action_user_name,
	          l.action_time,	l.action_info,	l.remarks
            from	t_log as l
	          left join t_user as u on l.action_user_id = u.id 
            where	is_del = 0	order by l.action_time desc
            limit %d,%d", $params["start"], $params["limit"]);
    foreach ($data as $i => $v) {
      $data[$i]["action_time"] = date("Y-m-d H:i:s", strtotime($data[$i]["action_time"]));
    }
    $totalCount = $db->query("select count(*) from t_log where is_del = 0");
    $rs["dataList"] = $data;
    $rs["totalCount"] = $totalCount[0]["count(*)"];
    return $rs;
  }

  /**版本回退
   * @param $params
   * @return mixed
   */
  public function backVersion($params)
  {
    $db = $this->db;
    $rs["success"] = false;

    $toVersion = $db->query("select * from t_log where id = '%s' and is_del = 0", $params["id"]);
    if (!count($toVersion)) {
      $rs["msg"] = "没有对应版本，请刷新";
      return $rs;
    }

    $versions = $db->query("select id,action_time from t_log where action_time >= '%s' and is_del = 0 
                                order by action_time desc", $toVersion[0]["action_time"]);
    $db->startTrans();
    $db->execute("update t_log set is_del = 1000 
    where action_time >= '%s' and is_del = 0", $toVersion[0]["action_time"]);
    try {
      foreach ($versions as $v) {
        $actions = $db->query("select * from t_log_action where log_id = '%s'", $v["id"]);

        foreach ($actions as $action) {
          $this->backAction($action, $db);
        }
      }
    } catch (Exception $e) {
      $db->rollback();
      $rs["msg"] = "操作失败：" + $e->getMessage();
      return $rs;
    }
    $rs["success"] = true;
    $rs["msg"] = "操作成功";
    $db->commit();
    return $rs;
  }

  private function backAction($params, &$db)
  {
    $action_sql = "update %s set is_del = ";
    $move_mark = "";
    if ($params["action_type"] == "insert") {//撤回插入操作
      $action_sql .= "1000";
      $move_mark = 1;
    } else {
      $action_sql .= "0,action_time = '" . Date("Y-m-d H:i:s") . "' ";
    }
    $action_sql .= " where id = '%s' ";

    if ($params["file_type"] == "file") {//对文件进行撤回
      $db->execute($action_sql, "t_file_info", $params["file_id"]);
    } else {
      $db->execute($action_sql, "t_dir_info", $params["file_id"]);
    }

    if ($move_mark == 1) {
      $info = "";
      if ($params["file_type"] == "file") {
        $info = $db->query("select * from t_file_info where id = '%s'", $params['file_id']);
      }
      if ($info) {
        $db->execute("delete from t_file_info where id = '%s'", $params["file_id"]);
        $sql = "insert into t_useless_data
            (id, file_name, file_path, file_size, file_suffix, parent_dir_id, file_version,
            file_fid, action_user_id, action_time, is_del,create_user_id,create_time, action_info) 
            values ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s','%s','%s')";

        $rs_info = $db->execute($sql, $info[0]);
        if (!$rs_info) {
          $db->rollback();
        }
      }
    }

  }

  public function editLogRemarksById($id, $remarks)
  {
    $db = $this->db;
    $db->execute("update t_log set remarks = '%s' where id = '%s'", $remarks, $id);
  }

  public function revokeFile($params)
  {
    $db = $this->db;
    $rs['success'] = false;
    $old_file_info = $db->query("select * from t_file_info where id = '%s' and is_del = 1000", $params['id']);

    //验证目录是否存在相同文件
    $is_container = $db->query("select * from t_file_info
        where parent_dir_id = '%s' and is_del = 0 and file_name in ('%s') and file_suffix in ('%s')",
      $old_file_info[0]["parent_dir_id"], $old_file_info[0]["file_name"], $old_file_info[0]["file_suffix"]);
    if (count($is_container)) {
      $this->deleteLog($params["log_id"]);
      $rs["msg"] = "当前目录存在相同文件[" . $old_file_info[0]["file_name"] . "]，无法撤回，";
      return $rs;
    }


    if (count($old_file_info)) {
      $now_file_info = $db->query("select * from t_file_info 
      where file_fid = '%s' and is_del = 0", $old_file_info[0]['file_fid']);
      $db->startTrans();

      $db->execute("update t_file_info set is_del = 1000 where id = '%s'", $now_file_info[0]["id"]);

      $data["action_type"] = "delete";
      $data["file_type"] = "file";
      $data["file_id"] = $now_file_info[0]["id"];
      $data["log_id"] = $params["log_id"];
      $this->addLogAction($data);


      $insert_sql = "insert into t_file_info 
        (id, file_name, file_path, file_size, file_suffix, parent_dir_id, file_version,
        file_fid,action_user_id, action_time, is_del,create_user_id,create_time, action_info)
        values	( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s','%s','%s')";

      $old_file_path = $old_file_info[0]["file_path"];
      $old_file_version = $old_file_info[0]["file_version"];
      $old_file_info[0]["id"] = $this->newId();
      $old_file_info[0]["file_version"] = $this->newId();
      $old_file_info[0]["action_time"] = Date("Y-m-d H:i:s");
      $old_file_info[0]["is_del"] = 0;
      $old_file_info[0]["parent_dir_id"] = $now_file_info[0]["parent_dir_id"];
      $old_file_info[0]["file_path"] = $now_file_info[0]["file_path"];

      $info = $db->execute($insert_sql, $old_file_info[0]);
      if (!$info) {
        $this->delLog($params["log_id"]);
        $rs["msg"] = "操作失败";
        $db->rollback();
      } else {
        copy($old_file_path . $old_file_version . "." . $old_file_info[0]["file_suffix"],
          $now_file_info[0]["file_path"] . $old_file_info[0]["file_version"] .
          "." . $old_file_info[0]["file_suffix"]);
        $data["action_type"] = "insert";
        $data["file_type"] = "file";
        $data["file_id"] = $old_file_info[0]["id"];
        $data["log_id"] = $params["log_id"];
        $this->addLogAction($data);
        $db->commit();
        $rs["msg"] = "操作成功";
        $rs["success"] = true;
        return $rs;
      }

    } else {
      $this->delLog($params["log_id"]);
      $rs["msg"] = "请选择旧版本";
      return $rs;
    }

  }
}
