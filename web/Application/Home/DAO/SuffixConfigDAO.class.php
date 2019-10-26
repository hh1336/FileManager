<?php

namespace Home\DAO;

use Home\DAO\PSIBaseExDAO;

class SuffixConfigDAO extends PSIBaseExDAO
{
  /**
   * 加载所有的后缀
   * @return array
   */
  public function loadSuffix()
  {
    $db = $this->db;
    $arr = [
      "office" => $db->query("select suffix from t_upload_type where classify = 'office'"),
      "picture" => $db->query("select suffix from t_upload_type where classify = 'picture'"),
      "other" => $db->query("select suffix from t_upload_type where classify = 'other'")
    ];
    $rs = [];
    foreach ($arr as $key => $value) {
      $temp = "";
      foreach ($value as $item) {
        $temp .= $item["suffix"] . "-";
      }
      $temp = substr($temp, 0, -1);
      $temp = str_replace("\n", "", $temp);
      $rs[$key] = $temp;
    }
    return $rs;
  }

  /**
   * 保存后缀
   * @param $params
   * @return mixed
   */
  public function saveSuffix($params)
  {
    $db = $this->db;
    $rs["success"] = false;
    $db->startTrans();
    $db->execute("delete from t_upload_type");

    $sql = "insert into t_upload_type ( suffix, classify ) values ";
    foreach ($params as $key => $value) {
      foreach ($value as $type) {
        if ($type) {
          $sql .= "('{$type}','{$key}'), ";
        }
      }
    }

    $sql = substr($sql, 0, -2);
    $sql = str_replace("\n","",$sql);
    $info = $db->execute($sql);
    if (!$info) {
      $rs["msg"] = "操作失败";
      $db->rollback();
      return $rs;
    }
    $db->commit();
    $rs["success"] = true;
    $rs["msg"] = "操作成功";
    return $rs;
  }

  /**
   * 根据分类获取后缀
   * @param null $type
   * @return array
   */
  public function getSuffixs($type = null)
  {
    $db = $this->db;
    $arr = [];
    if($type != null){
      $arr = $db->query("select suffix from t_upload_type where classify = '%s'",$type);
    }else{
      $arr = $db->query("select suffix from t_upload_type");
    }
    if (count($arr)) {
      $rs = [];
      foreach ($arr as $item) {
        array_push($rs,$item["suffix"]);
      }
      return $rs;
    }
  }
}
