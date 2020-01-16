<?php

namespace Home\DAO;

class ProcessDesignDAO extends PSIBaseExDAO
{
  /**
   * 加载流程配置
   *
   * 01流程开关
   * 02文件开关
   * 03文件夹开关
   * @return mixed
   */
  public function loadConfig()
  {
    $db = $this->db;
    $sql = "select id,value from t_config where id = '9004-01' or id = '9004-02' or id = '9004-03'";
    $config = $db->query($sql);
    return $this->successAction("", $config);
  }

  /**
   * 保存配置
   * @param $params
   * @return mixed
   */
  public function saveConfig($params)
  {
    $db = $this->db;
    $sql = "update t_config set value = '%s' where id = '%s'";
    foreach ($params as $key => $value) {
      $db->execute($sql, $value, $key);
    }
    return $this->successAction("操作成功");
  }

  /**
   * 加载流程
   * @param $params
   * @return mixed
   */
  public function loadProcess($params)
  {
    $db = $this->db;
    $sql = "select * from t_flow where 1 = 1";
    if ($params["flow_name"]) {
      $sql .= " and flow_name like('%" . $params["flow_name"] . "%')";
    }
    if ($params["flow_type"]) {
      $sql .= " and flow_type like('%" . $params["flow_type"] . "%')";
    }
    if ($params["file_type"]) {
      $sql .= " and file_type like('%" . $params["file_type"] . "%')";
    }
    if ($params["status"]) {
      $sql .= " and status = " . $params["status"];
    }

    $sql .= " order by sort_order asc ";

    $data = $db->query($sql);
    $list = [];
    foreach ($data as $i => $item) {
      $list[$i]["Id"] = $item["id"];
      $list[$i]["FlowType"] = $item["flow_type"];
      $list[$i]["FileType"] = $item["file_type"];
      $list[$i]["FlowName"] = $item["flow_name"];
      $list[$i]["SortOrder"] = $item["sort_order"];
      $list[$i]["Status"] = $item["status"];
      $list[$i]["UId"] = $item["uid"];
      $list[$i]["UName"] = $item["uname"];
      $list[$i]["AddTime"] = date("Y-m-d H:i:s", $item["add_time"]);
    }
    $rs["totalCount"] = count($data);
    $rs["dataList"] = $list;
    return $rs;
  }

  /**
   * 保存流程
   *
   * status状态
   * 0：未启用
   * 1：正在使用中
   * @param $params
   * @return mixed
   */
  public function saveFlow($params)
  {
    $db = $this->db;
    $info = "";
    $db->startTrans();
    if ($params["id"]) {//编辑
      $sel_sql = "select * from t_flow where id = '%s' and is_del = 0 and status = 0";
      $sel_data = $db->query($sel_sql, $params["id"]);
      if (!count($sel_data)) {
        $db->rollback();
        return $this->failAction("流程正在使用中或已删除，请刷新或关闭流程后重试");
      }
      $sql = "update t_flow set
      flow_type = '%s', file_type = '%s', flow_name = '%s',
      sort_order = '%s', last_edit_time = '%s', last_edit_uid = '%s',
      last_edit_uname = '%s'
      where	id = '%s'";

      $data["flow_type"] = $params["flow_type"];
      $data["file_type"] = $params["file_type"];
      $data["flow_name"] = $params["flow_name"];
      $data["sort_order"] = $params["sort_order"];
      $data["last_edit_time"] = time();
      $data["last_edit_uid"] = $params["uid"];
      $data["last_edit_uname"] = $params["uname"];
      $data["id"] = $params["id"];

      $info = $db->execute($sql, $data);


    } else {
      $sql = "insert into t_flow (id,	flow_type,	file_type,	flow_name,	sort_order,	status,	is_del,	uid,
        uname,	add_time) values ('%s','%s','%s','%s','%d','%d','%d','%s','%s','%s')";
      $data["id"] = $this->newId();
      $data["flow_type"] = $params["flow_type"];
      $data["file_type"] = $params["file_type"];
      $data["flow_name"] = $params["flow_name"];
      $data["sort_order"] = $params["sort_order"];
      $data["status"] = 0;
      $data["is_del"] = 0;
      $data["uid"] = $params["uid"];
      $data["uname"] = $params["uname"];
      $data["add_time"] = time();

      $info = $db->execute($sql, $data);
    }

    if (!$info) {
      $db->rollback();
      return $this->failAction("操作失败");
    }
    $db->commit();
    return $this->successAction("操作成功");
  }

  /**
   * 禁用流程
   * @param $params
   * @return mixed
   */
  public function disableFlow($params)
  {
    $db = $this->db;
    $data = $db->query("select * from t_flow where id = '%s' and is_del = 0 and status = 0", $params["id"]);
    if (!count($data)) {
      return $this->failAction("流程已禁用或已删除");
    }
    $sql = "update t_flow set status = 1 where id = '%s'";
    $info = $db->execute($sql, $params["id"]);
    if (!$info) {
      return $this->failAction("操作失败");
    }
    return $this->successAction("操作成功");
  }

  /**
   * 启用流程
   * @param $params
   * @return mixed
   */
  public function openFlow($params)
  {
    $db = $this->db;
    $data = $db->query("select * from t_flow where id = '%s' and is_del = 0 and status = 1", $params["id"]);
    if (!count($data)) {
      return $this->failAction("流程已禁用或已删除");
    }
    $sql = "update t_flow set status = 0 where id = '%s'";
    $info = $db->execute($sql, $params["id"]);
    if (!$info) {
      return $this->failAction("操作失败");
    }
    return $this->successAction("操作成功");
  }

  /**
   * 保存步骤
   * @param $params
   * @return mixed
   */
  public function saveProcess($params)
  {
    $db = $this->db;
    $db->startTrans();
    $info = "";
    $data = [];
    if ($params["id"]) {
      $sel_data = $db->query("select * from t_flow_process where id = '%s' and is_del = 0", $params['id']);
      if (!count($sel_data)) {
        return $this->failAction('未找到数据');
      }

      $sql = "update t_flow_process set 
        process_name = '%s', process_type = '%s', sponsor_ids = '%s', sponsor_text = '%s', sponsor_role = '%s',
        sponsor_role_text = '%s', role_ids = '%s', role_text = '%s', respon_ids = '%s', respon_text = '%s',
        user_ids = '%s', user_text = '%s', is_user_end = '%s', is_sing = '%s', is_back = '%s',
        is_userop_pass = '%s', processing_mode = '%s', updatetime = '%s' where id = '%s'";

      $data['id'] = $params['id'];
      $info = $db->execute($sql, $params['process_name'], $params['process_type'], $params['sponsor_ids'],
        $params['sponsor_text'], $params['sponsor_role'], $params['sponsor_role_text'], $params['role_ids'],
        $params['role_text'], $params['respon_ids'], $params['respon_text'], $params['user_ids'],
        $params['user_text'], $params['is_user_end'], $params['is_sing'], $params['is_back'],
        $params['is_userop_pass'], $params['processing_mode'], time(), $params['id']);
    } else {//创建一个步骤
      if ($params["process_type"] != "Step") {
        $sel_sql = "select * from t_flow_process where flow_id = '%s' and process_type = 'StartStep' and is_del = 0";
        $sel_data = $db->query($sel_sql, $params["flow_id"]);
        if (count($sel_data)) {
          return $this->failAction('已存在一个起始节点');
        }
      }

      $data['id'] = $this->newId();
      $data['flow_id'] = $params["flow_id"];
      $data['process_name'] = $params["process_type"] == "Step" ? "步骤" : "起始节点";
      $data['process_type'] = $params["process_type"];
      $data['set_left'] = 20;
      $data['set_top'] = 20;
      $data['style'] = "{}";
      $data['is_del'] = 0;
      $data['is_back'] = 0;
      $data['processing_mode'] = 'user';
      $data['updatetime'] = time();

      $sql = "insert into t_flow_process (id,	flow_id, process_name, process_type,
        set_left,	set_top,	style,	is_del,	is_back, processing_mode,	updatetime)
        values ('%s','%s','%s','%s','%d','%d','%s','%d','%d','%s','%s')";

      $info = $db->execute($sql, $data);
    }

    if (!$info) {
      $db->rollback();
      return $this->failAction("操作失败");
    }
    $db->commit();
    return $this->successAction("操作成功", $data['id']);
  }

  /**
   * 获取步骤信息
   * @param $params
   * @return mixed
   */
  public function getNodeInfo($params)
  {
    $db = $this->db;
    if ($params['id']) {
      $sql = "select * from t_flow_process where id = '%s' and is_del = 0";
      $info = $db->query($sql, $params['id']);
      if (!count($info)) {
        return $this->failAction('无法获取步骤信息');
      }
      return $this->successAction('', $info[0]);
    }
    return $this->failAction('无法得到步骤id');
  }

  /**
   * 加载所有步骤
   * @param $params
   * @return mixed
   */
  public function loadDesign($params)
  {
    $db = $this->db;
    $sql = "select id,process_name,process_type,set_left,set_top,process_to from t_flow_process 
    where flow_id = '%s' and is_del = 0";
    $data = $db->query($sql, $params['id']);
    return $this->successAction('', $data);
  }

  /**
   * 保存设计
   * @param $params
   * @return mixed
   */
  public function saveDesign($params)
  {
    $db = $this->db;
    $db->startTrans();

    $sql = "update t_flow_process 
    set process_to = '%s', set_left = '%d', set_top = '%d', updatetime = '%s' 
    where id = '%s' and is_del = 0";

    $info = $db->execute($sql, $params['process_to'], $params['set_left'], $params['set_top'], time(), $params['id']);
    if (!$info) {
      $db->rollback();
      return $this->failAction('保存失败');
    }
    $db->commit();
    return $this->successAction('');
  }

  /**
   * 删除步骤
   * @param $params
   * @return mixed
   */
  public function deleteNode($params)
  {
    $db = $this->db;
    $data = $db->query("select * from t_flow_process where id = '%s' and is_del = 0", $params['id']);
    if (!count($data))
      return $this->failAction('找不到数据');

    $info = $db->execute("update t_flow_process set is_del = 1,updatetime = '%s' where id = '%s'",
      time(),$params['id']);
    if (!$info) {
      return $this->failAction("操作失败");
    }
    return $this->successAction('删除成功');
  }
}
