<?php

namespace Home\DAO;

class ReceiveFlowDAO extends PSIBaseExDAO
{
  /**
   * 加载数据
   * @param $params
   * @return mixed
   */
  public function loadData($params)
  {
    $db = $this->db;
    $role_ids = $db->query("select role_id from t_role_user where user_id = '%s'", $params['uid']);
    $sql = "select frp.id, frp.run_id, frp.process_id, frp.flow_id, fr.remark, fr.run_name, fr.is_urgent,
    fp.is_sing, p_frp.sponsor_text as parent_sponsor_text, p_frp.id as parent_process_id, fr.uid, fr.uname 
    from t_flow_run_process frp
    left join t_flow_process fp on frp.process_id = fp.id
    left join t_flow_run fr on fr.id = frp.run_id
    left join t_flow f on frp.flow_id = f.id
    left join t_flow_run_process p_frp on p_frp.id = frp.parent_process
    where	frp.is_del = 0 and fp.is_del = 0 and fr.is_del = 0 and f.is_del = 0 and frp.is_receive = 0 and fr.status = 1
    and (fp.user_ids like ('%" . $params['uid'] . "%') ";
    foreach ($role_ids as $role_id) {
      $sql .= " or fp.role_ids like ('%" . $role_id['role_id'] . "%')";
    }
    $sql .= ")";

    if ($params['query_name']) {
      $sql .= " and fr.run_name like ('%" . $params['query_name'] . "%')";
    }
    if ($params['query_type'] != "") {
      $sql .= " and fr.is_urgent = '" . $params['query_type'] . "'";
    }


    $data = $db->query($sql);
    $list = [];
    foreach ($data as $v) {
      if ($v['is_sing'] == "1") {
        $is_sign = $db->query("select count(*) from t_flow_run_sign 
        where uid = '%s' and run_flow_process_id = '%s'", $params['uid'], $v['id']);
        if ($is_sign[0]['count(*)'] > 0) {
          continue;
        }
      }
      $item["id"] = $v['id'];
      $item["runId"] = $v['run_id'];
      $item["processId"] = $v['process_id'];
      $item["flowId"] = $v['flow_id'];
      $item["remark"] = $v['remark'];
      $item["runName"] = $v['run_name'];
      $item["isUrgent"] = $v['is_urgent'];
      $item["isSing"] = $v['is_sing'];
      $item["parentSponsorText"] = $v['parent_sponsor_text'] ?? "起始步骤";
      $item["parentProcessId"] = $v['parent_process_id'];
      $item["uId"] = $v['uid'];
      $item["uName"] = $v['uname'];
      array_push($list, $item);
    }
    $rs['dataList'] = $list;
    return $rs;
  }

  /**
   * 接收流程
   * @param $params
   * @return mixed
   */
  public function receive($params)
  {
    $db = $this->db;
    $run_process = $db->query("select * from t_flow_run_process where id = '%s'", $params['id']);
    if ($run_process[0]['is_receive'] == '1') {
      return $this->failAction('流程已被其他用户接收，请刷新数据');
    }


    $process = $db->query("select * from t_flow_process where id = '%s'", $run_process[0]['process_id']);

    $info = 0;
    $db->startTrans();
    //是否是会签，会签需要所有人都接收后状态才能改变成审核中
    if ($params['is_sing']) {
      //插入一条会签记录
      $insert_sign_sql = "insert into t_flow_run_sign (id, uid, run_flow_id, run_flow_process_id,	is_agree)
        values('%s','%s','%s','%s','%s')";
      $insert_sign_data['id'] = $this->newId();
      $insert_sign_data['uid'] = $params['uid'];
      $insert_sign_data['run_flow_id'] = $run_process[0]['run_id'];
      $insert_sign_data['run_flow_process_id'] = $run_process[0]['id'];
      $insert_sign_data['is_agree'] = 0;
      $info = $db->execute($insert_sign_sql, $insert_sign_data);
      if (!$info) {
        $db->rollback();
        return $this->failAction('操作失败');
      }

      $user_ids = [];
      if ($process[0]['processing_mode'] == 'user') {
        $user_ids = array_merge($user_ids, explode($process[0]['user_ids'], ','));
      } else {
        $role_ids = explode($process[0]['role_ids'], ',');
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
      $sql = "select count(*) from t_flow_run_sign where run_flow_process_id = '%s'";
      $signs_count = $db->query($sql, $run_process[0]['id']);
      //判断会签人的人是否都已接收，接收完毕后则将状态改为审核中
      $status = 1;
      if (count($user_ids) == $signs_count[0]['count(*)']) {
        $status = 2;
      }
      $info = $db->execute("update t_flow_run_process set status = '%s' where id = '%s'",
        $status, $run_process[0]['id']);

    } else {
      $sql = "update t_flow_run_process set is_receive = 1, status = 2,
        sponsor_ids = '%s', sponsor_text = '%s', receive_time = '%s' 
        where id = '%s' ";
      $info = $db->execute($sql, $params['uid'], $params['uname'], time(), $params['id']);
    }

    if (!$info) {
      $db->rollback();
      return $this->failAction('操作失败');
    }
    //接收完流程后，将流程步骤改为当前步骤
    $info = $db->execute("update t_flow_run set current_process = '%s',run_flow_process = '%s', updatetime = '%s'
    where id = '%s'", $run_process[0]['id'], $process[0]['process_to'], time(), $run_process[0]['run_id']);

    if (!$info) {
      $db->rollback();
      return $this->failAction('操作失败');
    }

    $db->commit();
    return $this->successAction('操作成功');
  }

}
