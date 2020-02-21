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
	  frp.status,	frp.is_back, frp.remark, fp.is_user_end, f.status as flow_status, fr.is_urgent,
	  fp.process_to, fp.process_type from t_flow_run_process frp
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
      $item['processTo'] = $v['process_to'];
      $item['processType'] = $v['process_type'];

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

  /**
   * 获取文件上传信息
   * @param $params
   * @return mixed
   */
  public function getFileInfoByRunId($params)
  {
    $db = $this->db;
    $run_flow = $db->query("select params_json from t_flow_run where id = '%s'", $params['run_id']);

    return $run_flow[0];
  }

  /**
   * 通过或结束流程
   *
   * 当多个流程节点中的其中一个流程节点通过时，将流程转交到下一步，其他节点的流程停止审核
   * 如果流程开关关闭了，则不允许审核
   * 如果是最后一步，则开始上传文件
   * @param $params
   * @return mixed
   */
  public function passFlow($params)
  {
    $db = $this->db;
    $sel_sql = "select frp.id, frp.status, fr.status as run_status,	frp.process_id,	fr.current_process,
     fp.process_to,	fp.process_type, fr.params_json, f.status as flow_status, frp.parent_process
     from t_flow_run_process frp
    left join t_flow_process fp on frp.process_id = fp.id
    left join t_flow_run fr on frp.run_id = fr.id
    left join t_flow f on frp.flow_id = f.id
    where	frp.id = '%s'";
    $process_data = $db->query($sel_sql, $params['id']);

    if ($process_data[0]['flow_status'] != "0")
      return $this->failAction("流程已关闭，请先开启流程后再进行审核");

    if ($process_data[0]['run_status'] != "1")
      return $this->failAction("流程已结束，不需要继续审核");

    //判断其他流程步骤已经通过了
    $other_is_pass = false;
    if (strpos($process_data[0]['current_process'], ',') !== false) {
      $arr = explode($process_data[0]['current_process'], ',');
      foreach ($arr as $item) {
        if ($item == $process_data[0]['id']) {
          $other_is_pass = true;
          break;
        }
      }
    } else {
      $other_is_pass = $process_data[0]['current_process'] == $process_data[0]['id'];
    }

    $db->startTrans();
    $info = "";
    if ($other_is_pass) {//其他流程步骤未审核
      //修改当前步骤状态
      $update_sql = "update t_flow_run_process 
        set  status = '3', remark = '%s', bl_time = '%s', updatetime = '%s',
        where	id = '%s'";
      $info = $db->execute($update_sql, $params['remark'], time(), time(), $params['id']);
      if (!$info) {
        $db->rollback();
        return $this->failAction('操作失败');
      }
      //修改其他步骤状态
      $update_sql = "update t_flow_run_process
       set status = '6' where id != '%s' and parent_process = '%s'";
      $info = $db->execute($update_sql, $params['id'],$process_data[0]['parent_process']);
      if (!$info) {
        $db->rollback();
        return $this->failAction('操作失败');
      }
      //TODO 判断是否结束流程进行文件上传，下一步则修改运行中流程的信息，根据下一步是否需要会签进行操作
      //修改运行中的流程信息


    } else {//其他流程步骤已审核

    }

  }


}
