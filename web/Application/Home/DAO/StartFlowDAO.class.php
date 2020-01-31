<?php

namespace Home\DAO;

class StartFlowDAO extends PSIBaseExDAO
{
  /**
   * 加载发起的流程
   * @param $params
   * @return mixed
   */
  public function loadRunFlow($params)
  {
    $db = $this->db;
    $sql = "select * from t_flow_run where uid = '" . $params['uid'] . "' and is_del = 0";

    if ($params['query_name'])
      $sql .= " and run_name like('%" . $params['query_name'] . "%')";
    if ($params['query_type'])
      $sql .= " and status = " . $params['query_type'];

    $data = $db->query($sql);
    $list = [];
    foreach ($data as $i => $item) {
      $nextProcessName = "";
      if ($item['run_flow_process']) {
        $id_arr = explode(",", $item['run_flow_process']);
        foreach ($id_arr as $id) {
          $users = $db->query("select user_text,role_text,processing_mode from t_flow_process
            where id = '%s' and is_del = 0", $id);
          if (count($users)) {
            $nextProcessName .= $users[0]['processing_mode'] == "user" ? $users[0]['user_text'] : $users[0]['role_text'];
            $nextProcessName .= ",";
          }
        }
      }
      $list[$i]['id'] = $item['id'];
      $list[$i]['runName'] = $item['run_name'];
      $list[$i]['action'] = $item['action'];
      $list[$i]['nextProcessUsers'] = $nextProcessName;
      $list[$i]['json'] = $item['params_json'];
      $list[$i]['isUrgent'] = $item['is_urgent'];
      $list[$i]['updatetime'] = date("Y-m-d H:i:s", $item["updatetime"]);
      $list[$i]['status'] = $item['status'];
    }
    $rs['dataList'] = $list;
    return $rs;

  }

  /**
   * 加载编辑发起流程的下拉框
   * @param $params
   * @return array
   */
  public function loadCheckFlow($params)
  {
    $db = $this->db;
    $sql = "select	f.id,	f.flow_name as name from t_flow as f
    left join t_flow_process as p on f.id = p.flow_id
    left join t_role_user ru on ru.user_id = '%s'
    where	p.process_type = 'StartStep' 
    and (p.user_ids like (ru.user_id) or p.role_ids like (ru.role_id)) and f.status = 0 and f.is_del = 0";

    $data = $db->query($sql, $params['uid']);
    $arr = [];
    foreach ($data as $item) {
      array_push($arr, array($item['id'], $item['name']));
    }

//    $rs['dataList'] = $db->query($sql,$params['uid']);
    return $arr;
  }

  /**
   * 预览文件
   * @param $params
   * @return mixed
   */
  public function previewFile($params)
  {
    $root = $_SERVER['HTTP_ORIGIN'] . ":" . $_SERVER['SERVER_PORT'] . $_SERVER['SCRIPT_NAME'];
    $root = substr($root, 0, strlen($root) - 9);
    $params['file_name'] = str_replace(strrchr($params['file_name'], "."), "", $params['file_name']);
    if ($params['ext'] == 'pdf') {
      return $this->successAction('/Public/pdfjs/web/viewer.html?file=' . $root . $params['file_path']);
    }
    $out_path = "Uploads/" . $params['file_name'];

    if (file_exists($out_path . ".pdf"))
      return $this->successAction('/Public/pdfjs/web/viewer.html?file=' . $root . $out_path . '.pdf');

    $p = "soffice --headless --convert-to pdf "
      . realpath($params['file_path'])
      . " --outdir Uploads/";
    $log = "";
    $arr = [];
    exec($p, $log, $arr);
    if ($arr) {
      return $this->failAction("转换失败");
    }
    return $this->successAction('/Public/pdfjs/web/viewer.html?file=' . $root . $out_path . '.pdf');
  }

  /**
   * 保存流程
   * @param $params
   * @return mixed
   */
  public function saveFlow($params)
  {
    $db = $this->db;

    $sel_data = $db->query("select * from t_flow_run 
    where id = '%s' and is_del = 0 and status = 0 ", $params['id']);
    if (!count($sel_data))
      return $this->failAction('操作失败，请刷新后重试');

    $process_info = $db->query("select * from t_flow_process 
    where flow_id = '%s' and process_type = 'StartStep' and is_del = 0", $params['flow_id']);
    if (!count($process_info))
      return $this->failAction('操作失败，请检查流程设计');


    $sql = "update t_flow_run set run_name = '%s',run_flow_process = '%s',updatetime = '%s',is_urgent = '%s' 
    where id = '%s' and is_del = 0";
    $info = $db->execute($sql, $params['run_name'], $process_info[0]['process_to'], time(),
      $params['is_urgent'] ? 1 : 0, $params['id']);
    if (!$info)
      return $this->failAction('保存失败');
    return $this->successAction('保存成功');

  }

  /**
   * 发起流程
   * @param $params
   * @return mixed
   */
  public function startFlow($params)
  {
    $db = $this->db;
    $sel_sql = "select * from t_flow_run where id = '%s' and is_del = 0";
    $sel_data = $db->query($sel_sql, $params['id']);
    if (!count($sel_data))
      return $this->failAction('请刷新数据');
    if (isset($sel_data[0]['flow_id']))
      return $this->failAction('请先设置要执行的流程');
    if ($sel_data[0]['status'] != 0)
      return $this->failAction('流程已在执行中');
    $info = $db->execute("update t_flow_run set status = 1 where id = '%s'", $params['id']);
    if (!$info)
      return $this->failAction('开启失败');
    return $this->successAction('操作成功');
  }
}
