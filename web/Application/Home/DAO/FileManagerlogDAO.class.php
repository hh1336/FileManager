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
      $params["log_id"], Date("Y-m-d H:i:s"), $params["login_user_id"], $params["log_info"], $params["action_info"]);
  }

  /**出错时删除操作记录
   * @param $id
   * @param $db
   */
  public function delLog($id)
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
      $is_dir = $db->query("select * from t_dir where id = '%s'", $params["id"]);
      $data = [];
      if (!count($is_dir)) {//查看文件历史
        $sql = "SELECT	fi.id,concat(fi.file_name,'.',fi.file_suffix) as name,	fi.action_time,	fi.action_user_id,	fi.action_info,	u.name as action_user_name,	'file' as type, fi.is_del
        FROM	t_file_info AS fi 
        LEFT JOIN t_user as u on fi.action_user_id = u.id
        WHERE	fi.file_fid = '%s' 
        ORDER BY	fi.is_del,fi.action_time DESC
        LIMIT %d,%d";
        $data = $db->query($sql, $params["id"], $params["start"], $params["limit"]);
        $rs["totalCount"] = $db->query("select count(*) from t_file_info where file_fid = '%s'", $params["id"])[0]["count(*)"];
      } else {
        $sql = "SELECT	di.id,di.dir_name as name,	di.action_time,	di.action_user_id,	di.action_info,	u.name as action_user_name,'dir' as type, di.is_del
        FROM	t_dir_info AS di 
        LEFT JOIN t_user as u on di.action_user_id = u.id
        WHERE	di.dir_fid = '%s' 
        ORDER BY	di.is_del,di.action_time DESC
        LIMIT %d,%d";
        $data = $db->query($sql, $params["id"], $params["start"], $params["limit"]);
        $rs["totalCount"] = count($is_dir);
      }
      foreach ($data as $i => $v) {
        $data[$i]["action_time"] = date("Y-m-d H:i:s", strtotime($data[$i]["action_time"]));
      }
      $rs["dataList"] = $data;
      return $rs;
    }


    $data = $db->query("SELECT	l.id,	l.action_user_id,	u.name as action_user_name,	l.action_time,	l.action_info,	l.remarks
            FROM	t_log AS l
	          LEFT JOIN t_user AS u ON l.action_user_id = u.id 
            WHERE	is_del = 0	ORDER BY l.action_time DESC
            LIMIT %d,%d", $params["start"], $params["limit"]);
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
    $db->execute("update t_log set is_del = 1000 where action_time >= '%s' and is_del = 0", $toVersion[0]["action_time"]);
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
    if ($params["action_type"] == "insert") {//撤回插入操作
      $action_sql .= "1000";
    } else {
      $action_sql .= "0,action_time = '" . Date("Y-m-d H:i:s") . "' ";
    }
    $action_sql .= " where id = '%s' ";

    if ($params["file_type"] == "file") {//对文件进行撤回
      $db->execute($action_sql, "t_file_info", $params["file_id"]);
    } else {
      $db->execute($action_sql, "t_dir_info", $params["file_id"]);
    }
  }

  public function editLogRemarksById($id, $remarks)
  {
    $db = $this->db;
    $db->execute("update t_log set remarks = '%s' where id = '%s'", $remarks, $id);
  }
}