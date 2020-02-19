<?php

namespace Home\DAO;

class ExamineDAO extends PSIBaseExDAO
{
  /**
   * 加载数据
   * @param $params
   * @return mixed
   */
  public function loadFlow($params)
  {
    $db = $this->db;
    $sql = "select frp.id, frp.run_id, fr.run_name,	fr.uid,	fr.uname,	fr.remark as run_remark, frp.receive_time,
	  frp.status,	frp.is_back, frp.remark, fp.is_user_end, f.status as flow_status, fr.is_urgent 
	  from t_flow_run_process frp
    left join t_flow_process fp on fp.id = frp.process_id
    left join t_flow_run fr on frp.run_id = fr.id
    left join t_flow_process parent_fp on parent_fp.id = frp.parent_process
    left join t_flow f on frp.flow_id = f.id
    where	frp.is_receive = 1 and frp.status > 1 and frp.sponsor_ids = '" . $params['uid'] . "' and frp.is_del = 0";
    if ($params['query_name'])
      $sql .= " and fr.run_name like ('%" . $params['query_name'] . "%')";
    if ($params['query_type'] != "")
      $sql .= " and frp.status = '" . $params['query_type'] . "'";

    $sql .= " order by f.status asc, frp.status asc, fr.is_urgent desc";
    $data = $db->query($sql);
    $rs['dataList'] = [];
    foreach ($data as $v) {
      $item['id'] = $v['id'];
      $item['runId'] = $v['run_id'];
      $item['runName'] = $v['run_name'];
      $item['uId'] = $v['uid'];
      $item['uName'] = $v['uname'];
      $item['runRemark'] = $v['run_remark'];
      $item['receiveTime'] = date("Y-m-d H:i:s", $v['receive_time']);
      $item['status'] = $v['status'];
      $item['isBack'] = $v['is_back'];
      $item['remark'] = $v['remark'];
      $item['isUserEnd'] = $v['is_user_end'];
      $item['flowStatus'] = $v['flow_status'];
      $item['isUrgent'] = $v['is_urgent'];

      array_push($rs['dataList'], $item);
    }
    return $rs;
  }

  /**
   * 加载流程进度
   * @param $params
   * @param null $is_current
   */
  public function flowAdvance($params, $is_current = true)
  {
    if (!$params['id']) {
      return;
    }
    $db = $this->db;
    $run_process = $db->query("select * from t_flow_run_process where id = '%s'", $params['id']);
    $run_flow = $db->query("select * from t_flow_run where id = '%s'", $run_process[0]['run_id']);

    $item["sponsorUser"] = $run_process[0]['sponsor_text'] ?? $run_flow[0]['uname'];
    $item['bltime'] = $is_current ? "" : date("Y-m-d H:i:s", $run_process[0]['bl_time']);
    $item['remark'] = $run_process[0]['remark'];
    $item['status'] = $is_current ? "当前步骤" : (empty($run_process[0]['parent_process']) ? "发起流程" : "通过");

    $data['id'] = $run_process[0]['parent_process'];
    $arr['dataList'] = [];
    if ($data['id']) {
      $arr = $this->flowAdvance($data, false);
    }
    array_push($arr['dataList'], $item);

    return $arr;
  }

}
