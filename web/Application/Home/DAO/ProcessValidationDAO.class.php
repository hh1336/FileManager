<?php

namespace Home\DAO;

class ProcessValidationDAO extends PSIBaseExDAO
{

  /**
   * 验证是否开启流程控制
   * 01流程开关
   * 02文件开关
   * 03文件夹开关
   *
   * @param $vType
   * @return bool
   */
  public function isOpenValidation($vType)
  {
    $db = $this->db;
    $sql = "select id,value from t_config 
    where id = '9004-01' or id = '%s'";
    $data = $db->query($sql, $vType == "file" ? "9004-02" : "9004-03");

    foreach ($data as $item) {
      if (!$item['value'])
        return false;
    }
    return true;

  }

  public function validation($params, $userId)
  {
    $db = $this->db;
    $db->startTrans();
    $params['login_id'] = $userId;

//    $is_have_flow = $db->query("select	f.id,	f.flow_name as name from t_flow as f
//    left join t_flow_process as p on f.id = p.flow_id
//    left join t_role_user ru on ru.user_id = '%s'
//    where	p.process_type = 'StartStep' and (p.user_ids like (ru.user_id)
//    or p.role_ids like (ru.role_id)) and f.status = 0 and f.is_del = 0", $userId);
//
//    if (!count($is_have_flow)) {
//      if (isset($params['path'])) unlink($params['path']);
//      return $this->failAction('请先设计流程');
//    }

    $run_sql = "insert into t_flow_run (id,	uid,action,run_name, params_json,
    updatetime, status,is_urgent, is_del) values ('%s','%s','%s','%s','%s','%s','%s','%s','%s')";

    $json_str = json_encode($params,JSON_UNESCAPED_UNICODE);
    //$json_str = addslashes($json_str);
    $data['id'] = $this->newId();
    $data['uid'] = $userId;
    $data['action'] = $params['vAction'] ."-". $params['vType'];
    $data['run_name'] = "请设置流程名称";
    $data['params_json'] = $json_str;
    $data['updatetime'] = time();
    $data['status'] = "0";
    $data['is_urgent'] = "0";
    $data['is_del'] = "0";

    $info = $db->execute($run_sql, $data);
    if (!$info) {
      $db->rollback();
      return $this->failAction('创建流程时出现了一个错误');
    }

    $db->commit();
    return $this->successAction("操作成功，请在[发起流程]模块中查看您的申请");
  }

}
