<?php

namespace Home\DAO;

class StartFlowDAO extends PSIBaseExDAO
{
  public function loadRunFlow($params)
  {
    $db = $this->db;
    $sql = "select * from t_flow_run where uid = '%s' and is_del = 0";
    $data = $db->query($sql, $params['uid']);
    $list = [];
    foreach ($data as $i => $item) {
      $nextProcessName = "";
      if (!$item['run_flow_process']) {
        $id_arr = explode(",", $item['run_flow_process']);
        foreach ($id_arr as $id) {
          $users = $db->query("select user_text,role_text,processing_mode from t_flow_process
            where id = '%s' and is_del = 0", $id);
          if (count($users)) {
            $nextProcessName .= $users['processing_mode'] == "user" ? $users['user_text'] : $users['role_text'];
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

  //TODO
  public function saveFlow($params)
  {
    $db = $this->db;

    $sql = "update t_flow_run set run_name = '%s',run_flow_process = '%s',updatetime = '%s',is_urgent = '%s' 
    where id = '%s' and is_del = 0";

  }
}
