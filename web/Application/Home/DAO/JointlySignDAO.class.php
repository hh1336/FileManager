<?php

namespace Home\DAO;

class JointlySignDAO extends PSIBaseExDAO
{
  /**
   * 加载数据
   * @param $params
   * @return mixed
   */
  public function loadData($params)
  {
    $db = $this->db;
    $sql = "select frs.id, frs.is_agree, frs.content, fr.run_name, fr.remark as run_remark, fr.is_urgent,
    fr.status as run_status, frp.status as run_process_status, f.status as flow_status, fr.uname as run_user,
    frs.receive_time,frp.id as run_process_id, fr.id as run_id
    from t_flow_run_sign frs
    left join t_flow_run fr on fr.id = frs.run_flow_id
    left join t_flow_run_process frp on frp.id = frs.run_flow_process_id
    left join t_flow f on f.id = frp.flow_id
    where	frs.uid = '%s'";
    if ($params['query_name'])
      $sql .= " and fr.run_name like('\%" . $params['query_name'] . "\%')";
    if (!empty($params['query_type']))
      $sql .= " and frs.status = " . $params['query_type'];

    $list = $db->query($sql, $params['uid']);
    $rs['dataList'] = [];
    foreach ($list as $item) {
      $data['id'] = $item['id'];
      $data['isAgree'] = $item['is_agree'];
      $data['content'] = $item['content'];
      $data['runName'] = $item['run_name'];
      $data['runRemark'] = $item['run_remark'];
      $data['isUrgent'] = $item['is_urgent'];
      $data['runStatus'] = $item['run_status'];
      $data['runProcessStatus'] = $item['run_process_status'];
      $data['flowStatus'] = $item['flow_status'];
      $data['runUser'] = $item['run_user'];
      $data['receiveTime'] = date("Y-m-d H:i:s", $item['receive_time']);
      $data['runProcessId'] = $item['run_process_id'];
      $data['runId'] = $item['run_id'];
      array_push($rs['dataList'], $data);
    }
    return $rs;
  }

  /**
   * 加载审核进度
   * @param $params
   * @param bool $is_current
   * @return mixed
   */
  public function flowAdvance($params, $is_current = true)
  {
    $db = $this->db;
    $run_process = $db->query("select * from t_flow_run_process where id = '%s'", $params['run_process_id']);
    $run_flow = $db->query("select * from t_flow_run where id = '%s'", $run_process[0]['run_id']);

    $item["sponsorUser"] = $run_process[0]['is_singpost'] == 1 ?
      "多人会签" : ($run_process[0]['sponsor_text'] ?? $run_flow[0]['uname']);
    $item['bltime'] = $is_current ? "" : date("Y-m-d H:i:s", $run_process[0]['bl_time']);
    $item['remark'] = $run_process[0]['remark'];
    $item['status'] = $is_current ? "当前步骤" : (empty($run_process[0]['parent_process']) ?
      "发起流程" : ($run_process[0]['status'] == "3" ? "已通过" : "退回"));
    $item['receiveTime'] = $run_process[0]['receive_time'] ?
      date("Y-m-d H:i:s", $run_process[0]['receive_time']) : "";
    $arr['dataList'] = [];
    if ($run_process[0]['parent_process']) {
      $data['run_process_id'] = $run_process[0]['parent_process'];
      $arr = $this->flowAdvance($data, false);
    }
    array_push($arr['dataList'], $item);
    return $arr;
  }

  /**
   * 加载会签进度
   * @param $params
   * @return array
   */
  public function jointlySignAdvance($params)
  {
    $db = $this->db;
    $sql = "select * from t_flow_run_sign where run_flow_process_id = '%s' 
    order by is_agree asc";
    $data = $db->query($sql, $params['run_process_id']);
    $arr = [];
    foreach ($data as $datum) {
      $item['id'] = $datum['id'];
      $item['uName'] = $datum['uname'];
      $item['isAgree'] = $datum['is_agree'];
      $item['receiveTime'] = date("Y-m-d H:i:s", $datum['receive_time']);
      $item['content'] = $datum['content'];
      array_push($arr, $item);
    }

    return $arr;
  }

  /**
   * 获取会签人数
   * @param $params
   * @return array
   */
  public function loadJointlyCount($params)
  {
    $db = $this->db;
    $sql = "select fp.* from t_flow_run_process frp
    left join t_flow_process fp on fp.id = frp.process_id
    where	frp.id = '%s'";
    $process = $db->query($sql, $params['run_process_id']);
    $user_ids = [];
    if ($process[0]['processing_mode'] == 'user') {
      $arr = explode(',', $process[0]['user_ids']);
      $user_ids = array_merge($user_ids, $arr);
    } else {
      $role_ids = explode(',', $process[0]['role_ids']);
      $sql = "select user_id from t_role_user where 1 = 1 ";
      foreach ($role_ids as $role_id) {
        $sql .= " or role_id = '" . $role_id . "'";
      }
      $sql .= "group by user_id";
      $users = $db->query($sql);
      foreach ($users as $id) {
        array_push($user_ids, $id);
      }
    }

    $sql = "select r_count.*,u_count.*,p_count.*,f_count.* from
(select count(*) as receive_count from t_flow_run_sign where run_flow_process_id = '%s') r_count,
(select count(*) as unaudited_count from t_flow_run_sign where run_flow_process_id = '%s' and is_agree = '0') u_count,
(select count(*) as pass_count from t_flow_run_sign where run_flow_process_id = '%s' and is_agree = '1') p_count,
(select count(*) as fail_count from t_flow_run_sign where run_flow_process_id = '%s' and is_agree = '2') f_count";
    $count = $db->query($sql,
      $params['run_process_id'], $params['run_process_id'], $params['run_process_id'], $params['run_process_id']);
    $arr = array(
      "jointlySignCount" => count($user_ids),
      "receiveCount" => $count[0]['receive_count'],
      "unauditedCount" => $count[0]['unaudited_count'],
      "passCount" => $count[0]['pass_count'],
      "failCount" => $count[0]['fail_count']

    );
    return $arr;
  }

  /**
   * 通过会签
   * @param $params
   * @return mixed
   * @throws \ReflectionException
   */
  public function pass($params)
  {
    $db = $this->db;
    $sql = "select frs.is_agree, f.status as flow_status, frp.status as run_process_status,
	  fr.status as run_flow_status,fp.process_to,fp.processing_mode,fp.user_ids as fp_user_ids,
	  fp.role_ids as fp_role_ids, frp.id as run_process_id, fp.process_type,frp.parent_process,fr.id as run_id,
	  fr.params_json
    from t_flow_run_sign frs
    left join t_flow_run_process frp on frs.run_flow_process_id = frp.id
    left join t_flow_run fr on frs.run_flow_id = fr.id
    left join t_flow_process fp on frp.process_id = fp.id
    left join t_flow f on fr.flow_id = f.id
    where	frs.id = '%s'";
    $data = $db->query($sql, $params['sign_id']);
    if ($data[0]['is_agree'] != 0)
      return $this->failAction("请刷新数据后重试");
    if ($data[0]['flow_status'] != 0)
      return $this->failAction("流程已被关闭，请先开启流程后继续进行审核");
    if ($data[0]['run_process_status'] > 2)
      return $this->failAction("其他步骤已通过审核，会签已结束");
    if ($data[0]['run_flow_status'] != 1)
      return $this->failAction("流程已结束，不需要再进行审核");

    $db->startTrans();
    $info = "";
    //修改会签数据
    $update_sql = "update t_flow_run_sign set is_agree = '1', content = '%s', bl_time = '%s' where id = '%s'";
    $info = $db->execute($update_sql, $params['content'], time(), $params['sign_id']);
    if (!$info) {
      $db->rollback();
      return $this->failAction("处理失败");
    }
    //判断是否全部办理结束
    $user_ids = [];
    if ($data[0]['processing_mode'] == 'user') {
      $arr = explode(',', $data[0]['fp_user_ids']);
      $user_ids = array_merge($user_ids, $arr);
    } else {
      $role_ids = explode(',', $data[0]['fp_role_ids']);
      $sql = "select user_id from t_role_user where 1 = 1 ";
      foreach ($role_ids as $role_id) {
        $sql .= " or role_id = '" . $role_id . "'";
      }
      $sql .= "group by user_id";
      $users = $db->query($sql);
      foreach ($users as $id) {
        array_push($user_ids, $id);
      }
    }
    $sql = "select count(*) from t_flow_run_sign where run_flow_process_id = '%s' and is_agree != 0";
    $signs_count = $db->query($sql, $data[0]['run_process_id']);
    //判断是否是最后一个会签的人，是则判断是否全部都同意了，然后进行下一步或发放文件，不是则提交操作
    if (count($user_ids) == $signs_count[0]['count(*)']) {
      //判断是否全部同意
      $sql = "select count(*) from t_flow_run_sign where run_flow_process_id = '%s' and is_agree = '1'";
      $signs_count = $db->query($sql, $data[0]['run_process_id']);
      if ($signs_count[0]['count(*)'] == count($user_ids)) {

        //判断是否拥有下一步操作，没有则发放文件
        if ($data[0]['process_to'] != "" && $data[0]['process_type'] != "End") {
          $next_process_ids = array($data[0]['process_to']);
          if (strpos($data[0]['process_to'], ',') !== false) {
            $next_process_ids = explode(',', $data[0]['process_to']);
          }

          $current_process_ids = [];
          foreach ($next_process_ids as $item) {
            $next_process = $db->query("select * from t_flow_process where id = '%s'", $item);
            if ($next_process[0]['role_ids'] == "" && $next_process[0]['user_ids'] == "") {
              $db->rollback();
              return $this->failAction("请先在流程设计中完善流程审核人");
            }

            $insert_sql = "insert into t_flow_run_process (id, uid, run_id, flow_id, process_id,
        is_singpost, is_back, is_receive, status, bl_time, is_del, remark, parent_process)
        values ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')";
            $insert_data['id'] = $this->newId();
            $insert_data['uid'] = $params['uid'];
            $insert_data['run_id'] = $data[0]['run_id'];
            $insert_data['flow_id'] = $next_process[0]['flow_id'];
            $insert_data['process_id'] = $item;
            $insert_data['is_singpost'] = $next_process[0]['is_sing'];
            $insert_data['is_back'] = $next_process[0]['is_back'];
            $insert_data['is_receive'] = 0;
            $insert_data['status'] = 0;
            $insert_data['bl_time'] = "";
            $insert_data['is_del'] = 0;
            $insert_data['remark'] = "";
            $insert_data['parent_process'] = $data[0]['run_process_id'];
            $info = $db->execute($insert_sql, $insert_data);
            if (!$info) {
              $db->rollback();
              return $this->failAction('操作失败');
            }
            array_push($current_process_ids, $insert_data['id']);
          }
          //修改流程步骤状态
          $update_sql = "update t_flow_run_process set status = '3', remark = '会签通过', sponsor_text = '会签',
            bl_time = '%s' where id = '%s'";
          $info = $db->execute($update_sql, time(), $data[0]['run_process_id']);
          if (!$info) {
            $db->rollback();
            return $this->failAction('操作失败');
          }
          //修改同级步骤状态
          $update_sql = "update t_flow_run_process set status = '6' where id != '%s' and parent_process = '%s'";
          $db->execute($update_sql, $data[0]['run_process_id'], $data[0]['parent_process']);

          //修改run表数据
          $current_process_id = implode(',', $current_process_ids);
          $info = $db->execute("update t_flow_run set current_process = '%s' where id = '%s'",
            $current_process_id, $data[0]['run_id']);
          if (!$info) {
            $db->rollback();
            return $this->failAction('操作失败');
          }
          $db->commit();
          return $this->successAction("审核成功");
        }
        //发放文件
        $info = $db->execute("update t_flow_run set status = '2', endtime = '%s' 
    where id = '%s'", time(), $data[0]['run_id']);
        if (!$info) {
          $db->rollback();
          return $this->failAction('操作失败');
        }
        $json = json_decode($data[0]['params_json'], JSON_UNESCAPED_UNICODE);
        $json['validated'] = false;
        $class = new \ReflectionClass($json['service_name']);
        $instance = $class->newInstance();
        $func = $show_method = $class->getMethod($json['function_name']);
        $func->invokeArgs($instance, array($json));

      } else {
        //有部分不同意，修改流程步骤，结束申请流程
        $update_sql = "update t_flow_run_process set status = '4', bl_time = '%s' where id = '%s'";
        $info = $db->execute($update_sql, time(), $data[0]['run_process_id']);
        if (!$info) {
          $db->rollback();
          return $this->failAction('操作失败');
        }
        //修改run表数据
        $update_sql = "update t_flow_run set status = '3', endtime = '%s' where id = '%s' ";
        $info = $db->execute($update_sql, time(), $data[0]['run_id']);
        if (!$info) {
          $db->rollback();
          return $this->failAction('操作失败');
        }
      }
    }
    $db->commit();
    return $this->successAction("操作成功");
  }

  //
  public function fail($params)
  {
    $db = $this->db;
    $sql = "select frs.is_agree, f.status as flow_status, frp.status as run_process_status,
	  fr.status as run_flow_status,fp.process_to,fp.processing_mode,fp.user_ids as fp_user_ids,
	  fp.role_ids as fp_role_ids, frp.id as run_process_id, fp.process_type,frp.parent_process,fr.id as run_id,
	  fr.params_json
    from t_flow_run_sign frs
    left join t_flow_run_process frp on frs.run_flow_process_id = frp.id
    left join t_flow_run fr on frs.run_flow_id = fr.id
    left join t_flow_process fp on frp.process_id = fp.id
    left join t_flow f on fr.flow_id = f.id
    where	frs.id = '%s'";
    $data = $db->query($sql, $params['sign_id']);
    if ($data[0]['is_agree'] != 0)
      return $this->failAction("请刷新数据后重试");
    if ($data[0]['flow_status'] != 0)
      return $this->failAction("流程已被关闭，请先开启流程后继续进行审核");
    if ($data[0]['run_process_status'] > 2)
      return $this->failAction("其他步骤已通过审核，会签已结束");
    if ($data[0]['run_flow_status'] != 1)
      return $this->failAction("流程已结束，不需要再进行审核");

    $db->startTrans();
    $info = "";
    //修改会签数据
    $update_sql = "update t_flow_run_sign set is_agree = '2', content = '%s', bl_time = '%s' where id = '%s'";
    $info = $db->execute($update_sql, $params['content'], time(), $params['sign_id']);
    if (!$info) {
      $db->rollback();
      return $this->failAction("处理失败");
    }
    //判断是否全部办理结束
    $user_ids = [];
    if ($data[0]['processing_mode'] == 'user') {
      $arr = explode(',', $data[0]['fp_user_ids']);
      $user_ids = array_merge($user_ids, $arr);
    } else {
      $role_ids = explode(',', $data[0]['fp_role_ids']);
      $sql = "select user_id from t_role_user where 1 = 1 ";
      foreach ($role_ids as $role_id) {
        $sql .= " or role_id = '" . $role_id . "'";
      }
      $sql .= "group by user_id";
      $users = $db->query($sql);
      foreach ($users as $id) {
        array_push($user_ids, $id);
      }
    }
    $sql = "select count(*) from t_flow_run_sign where run_flow_process_id = '%s' and is_agree != 0";
    $signs_count = $db->query($sql, $data[0]['run_process_id']);
    //判断是否是最后一个会签的人，是则修改流程状态
    if (count($user_ids) == $signs_count[0]['count(*)']) {
      $update_sql = "update t_flow_run_process set status = '4', bl_time = '%s' where id = '%s'";
      $info = $db->execute($update_sql, time(), $data[0]['run_process_id']);
      if (!$info) {
        $db->rollback();
        return $this->failAction('操作失败');
      }
      //修改run表数据
      $update_sql = "update t_flow_run set status = '3', endtime = '%s' where id = '%s' ";
      $info = $db->execute($update_sql, time(), $data[0]['run_id']);
      if (!$info) {
        $db->rollback();
        return $this->failAction('操作失败');
      }
    }
    $db->commit();
    return $this->successAction("操作成功");
  }
}
