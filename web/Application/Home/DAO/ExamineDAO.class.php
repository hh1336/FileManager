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
   * @param true $is_current
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
    $item['status'] = $is_current ? "当前步骤" : (empty($run_process[0]['parent_process']) ?
      "发起流程" : ($run_process[0]['status'] == "3" ? "已通过" : "退回"));
    $item['receiveTime'] = $run_process[0]['receive_time'] ?
      date("Y-m-d H:i:s", $run_process[0]['receive_time']) : "";
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
   * @param bool $is_end
   * @return mixed
   * @throws \ReflectionException
   */
  public function passFlow($params, $is_end = false)
  {
    $db = $this->db;
    $sel_sql = "select frp.id, frp.status, fr.status as run_status,	frp.process_id,	fr.current_process,
     fp.process_to,	fp.process_type, fr.params_json, f.status as flow_status, frp.parent_process,frp.run_id
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

    if ($process_data[0]['status'] != "2")
      return $this->failAction("其他步骤已通过");

    //判断其他流程步骤已经通过了
//    $other_is_pass = $process_data[0]['current_process'] == $process_data[0]['id'];
//    if (strpos($process_data[0]['current_process'], ',') !== false) {
//      $arr = explode($process_data[0]['current_process'], ',');
//      foreach ($arr as $item) {
//        if ($item == $process_data[0]['id']) {
//          $other_is_pass = true;
//          break;
//        }
//      }
//    }

    $info = "";
    $db->startTrans();
    //修改当前步骤状态
    $update_sql = "update t_flow_run_process 
        set  status = '3', remark = '%s', bl_time = '%s', updatetime = '%s'
        where	id = '%s'";
    $info = $db->execute($update_sql, $params['remark'], time(), time(), $params['id']);
    if (!$info) {
      $db->rollback();
      return $this->failAction('操作失败');
    }
    //修改其他步骤状态
    $update_sql = "update t_flow_run_process
       set status = '6' where id != '%s' and parent_process = '%s'";
    $db->execute($update_sql, $params['id'], $process_data[0]['parent_process']);

    //判断是否存在下一步，不存在则表示可以发放文件
    if (!$is_end & $process_data[0]['process_to'] != "" && $process_data[0]['process_type'] != "End") {
      $next_process_ids = array($process_data[0]['process_to']);
      if (strpos($process_data[0]['process_to'], ',') !== false) {
        $next_process_ids = explode($process_data[0]['process_to'], ',');
      }

      $current_process_ids = [];
      foreach ($next_process_ids as $item) {
        $next_process = $db->query("select * from t_flow_process where id = '%s'", $item);
        if ($next_process[0]['role_ids'] == "" && $next_process[0]['user_ids'] == "") {
          return $this->failAction("请先在流程设计中完善流程审核人");
        }

        $insert_sql = "insert into t_flow_run_process (id,	uid, run_id, flow_id,	process_id,
        is_singpost, is_back, is_receive, status, bl_time, is_del, remark, parent_process)
        values ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')";
        $insert_data['id'] = $this->newId();
        $insert_data['uid'] = $params['uid'];
        $insert_data['run_id'] = $process_data[0]['run_id'];
        $insert_data['flow_id'] = $next_process[0]['flow_id'];
        $insert_data['process_id'] = $item;
        $insert_data['is_singpost'] = $next_process[0]['is_sing'];
        $insert_data['is_back'] = $next_process[0]['is_back'];
        $insert_data['is_receive'] = 0;
        $insert_data['status'] = 0;
        $insert_data['bl_time'] = "";
        $insert_data['is_del'] = 0;
        $insert_data['remark'] = "";
        $insert_data['parent_process'] = $params['id'];
        $info = $db->execute($insert_sql, $insert_data);
        if (!$info) {
          $db->rollback();
          return $this->failAction('操作失败');
        }
        array_push($current_process_ids, $insert_data['id']);
      }
      //修改run表数据
      $current_process_id = implode(',', $current_process_ids);
      $info = $db->execute("update t_flow_run set current_process = '%s' where id = '%s'",
        $current_process_id, $process_data[0]['run_id']);
      if (!$info) {
        $db->rollback();
        return $this->failAction('操作失败');
      }
      $db->commit();
      return $this->successAction("审核操作成功");
    }
    //发放文件
    $info = $db->execute("update t_flow_run set status = '2', endtime = '%s' 
    where id = '%s'", time(), $process_data[0]['run_id']);
    if (!$info) {
      $db->rollback();
      return $this->failAction('操作失败');
    }
    $json = json_decode($process_data[0]['params_json'], JSON_UNESCAPED_UNICODE);
    $json['validated'] = false;
    $class = new \ReflectionClass($json['service_name']);
    $instance = $class->newInstance();
    $func = $show_method = $class->getMethod($json['function_name']);
    $func->invokeArgs($instance, array($json));
    $db->commit();
    return $this->successAction("审核操作成功");
  }

  /**
   * 流程不通过，运行中的流程单将停止
   * @param $params
   * @return mixed
   */
  public function fail($params)
  {
    $db = $this->db;
    $sel_sql = "select frp.id, frp.status, fr.status as run_status,	frp.process_id,	fr.current_process,
     fp.process_to,	fp.process_type, fr.params_json, f.status as flow_status, frp.parent_process,frp.run_id
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

    if ($process_data[0]['status'] != "2")
      return $this->failAction("其他步骤已通过");

    $db->startTrans();
    $info = "";
    //修改当前流程步骤
    $update_sql = "update t_flow_run_process set status = '4', remark = '%s', bl_time = '%s'
        where id = '%s' ";
    $info = $db->execute($update_sql, $params['remark'], time(), $params['id']);
    if (!$info) {
      $db->rollback();
      return $this->failAction("操作失败");
    }
    //修改其他流程步骤为状态6
    $update_sql = "update t_flow_run_process set status = '6' where id != '%s' and parent_process = '%s'";
    $db->execute($update_sql, $params['id'], $process_data[0]['parent_process']);

    //修改运行中的流程单为已结束
    $update_sql = "update t_flow_run set status = '3', endtime = '%s' where id = '%s'";
    $info = $db->execute($update_sql, time(), $process_data[0]['run_id']);
    if (!$info) {
      $db->rollback();
      return $this->failAction("操作失败");
    }
    $db->commit();
    return $this->successAction("操作成功");
  }

  /**
   * 退回操作，
   * 将流程退回上一步重新审核，但是之前的审核记录依然保存
   * @param $params
   * @return mixed
   */
  public function back($params)
  {
    $db = $this->db;
    $sel_sql = "select frp.id, frp.status, fr.status as run_status,	frp.process_id,	fr.current_process,
     fp.process_to,	fp.process_type, fr.params_json, f.status as flow_status, frp.parent_process,frp.run_id
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

    if ($process_data[0]['status'] != "2")
      return $this->failAction("其他步骤已通过");

    $db->startTrans();
    $info = "";

    //修改当前流程步骤的状态
    $update_sql = "update t_flow_run_process set status = '5', remark = '%s',bl_time = '%s' where id = '%s'";
    $info = $db->execute($update_sql, $params['remark'], time(), $params['id']);
    if (!$info) {
      $db->rollback();
      return $this->failAction("操作失败");
    }

    $update_sql = "update t_flow_run_process set status = '6' where id != '%s' and parent_process = '%s'";
    $db->execute($update_sql, $params['id'], $process_data[0]['parent_process']);

    $parent_process = $db->query("select * from t_flow_run_process where id = '%s'"
      , $process_data[0]['parent_process']);

    //如果是第一步，直接复制上一步骤的数据，如果不是第一步，需要判断上一步是否还有同级步骤
    $insert_sql = "insert into t_flow_run_process (id, uid,	run_id,	flow_id, process_id, remark, sponsor_ids,
	  sponsor_text,	is_singpost, is_back,	is_receive,	status,	receive_time,	bl_time, is_del, updatetime, parent_process)
    values ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')";
    if ($parent_process[0]['parent_process']) {

      $parent_process[0]['id'] = $this->newId();
      $parent_process[0]['remark'] = "审核被退回，请重新审核";
      $parent_process[0]['sponsor_ids'] = "";
      $parent_process[0]['sponsor_text'] = "";
      $parent_process[0]['is_receive'] = "0";
      $parent_process[0]['status'] = "0";
      $parent_process[0]['receive_time'] = "";
      $parent_process[0]['bl_time'] = "";
      $parent_process[0]['updatetime'] = "";
      $parent_process[0]['parent_process'] = $params['id'];

      $info = $db->execute($insert_sql, $parent_process[0]);
      if (!$info) {
        $db->rollback();
        return $this->failAction("操作失败");
      }

    } else {

      $sel_sql = "select fp.* from t_flow_run_process frp
    left join t_flow_process fp on frp.process_id on fp.id where frp.id = '%s'";

      $p_parent_process = $db->query($sel_sql, $parent_process[0]['parent_process']);

      $p_ids = array($p_parent_process[0]['process_to']);
      if (strpos($p_parent_process[0]['process_to'], ','))
        $p_ids = explode($p_parent_process[0]['process_to'], ',');
      $current_ids = [];
      foreach ($p_ids as $p_id) {
        $process = $db->query("select * from t_flow_process where id = '%s'", $p_id);
        $run_p_data['id'] = $this->newId();
        $run_p_data['uid'] = $params['uid'];
        $run_p_data['run_id'] = $process_data[0]['run_id'];
        $run_p_data['flow_id'] = $process[0]['flow_id'];
        $run_p_data['process_id'] = $p_id;
        $run_p_data['remark'] = "审核被退回，请重新审核";
        $run_p_data['sponsor_ids'] = "";
        $run_p_data['sponsor_text'] = "";
        $run_p_data['is_singpost'] = $process[0]["is_sing"];
        $run_p_data['is_back'] = $process[0]['is_back'];
        $run_p_data['is_receive'] = 0;
        $run_p_data['status'] = 0;
        $run_p_data['receive_time'] = "";
        $run_p_data['bl_time'] = "";
        $run_p_data['is_del'] = 0;
        $run_p_data['updatetime'] = "";
        $run_p_data['parent_process'] = $params['id'];
        $info = $db->execute($insert_sql, $run_p_data);
        if (!$info) {
          $db->rollback();
          return $this->failAction('开启失败');
        }
        array_push($current_ids, $run_p_data['id']);
      }
      $current_id_str = implode(',', $current_ids);
      $info = $db->execute("update t_flow_run set current_process = '%s' where id = '%s'",
        $current_id_str, $process_data[0]['run_id']);
      if (!$info) {
        $db->rollback();
        return $this->failAction('操作失败');
      }
    }
    $db->commit();
    return $this->successAction("操作成功");

  }
}
