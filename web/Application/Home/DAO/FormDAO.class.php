<?php

namespace Home\DAO;

/**
 * 自定义表单 DAO
 *
 * @author 李静波
 */
class FormDAO extends PSIBaseExDAO
{

  /**
   * 表单分类列表
   */
  public function categoryList($params)
  {
    $db = $this->db;

    $sql = "select id, code, name
            from t_form_category
            order by code";
    $data = $db->query($sql);

    $result = [];
    foreach ($data as $v) {
      $result[] = [
        "id" => $v["id"],
        "code" => $v["code"],
        "name" => $v["name"]
      ];
    }

    return $result;
  }

  /**
   * 新增表单分类
   */
  public function addFormCategory(&$params)
  {
    $db = $this->db;

    $code = $params["code"] ?? "";
    $code = strtoupper($code);
    $name = $params["name"];

    // 检查编码是否存在
    if ($code) {
      $sql = "select count(*) as cnt from t_form_category where code = '%s' ";
      $data = $db->query($sql, $code);
      $cnt = $data[0]["cnt"];
      if ($cnt) {
        return $this->bad("表单分类编码[{$code}]已经存在");
      }
    } else {
      $code = "";
    }

    // 检查分类名称是否存在
    $sql = "select count(*) as cnt from t_form_category where name = '%s' ";
    $data = $db->query($sql, $name);
    $cnt = $data[0]["cnt"];
    if ($cnt) {
      return $this->bad("表单分类[{$name}]已经存在");
    }

    $id = $this->newId();
    $sql = "insert into t_form_category (id, code, name, parent_id)
            values ('%s', '%s', '%s', null)";

    $rc = $db->execute($sql, $id, $code, $name);
    if ($rc === false) {
      return $this->sqlError(__METHOD__, __LINE__);
    }

    // 操作成功
    $params["id"] = $id;
    return null;
  }

  public function getCategoryById($id)
  {
    $db = $this->db;

    $sql = "select code, name from t_form_category where id = '%s' ";
    $data = $db->query($sql, $id);
    if ($data) {
      return [
        "code" => $data[0]["code"],
        "name" => $data[0]["name"]
      ];
    } else {
      return null;
    }
  }

  /**
   * 编辑表单分类
   */
  public function updateFormCategory(&$params)
  {
    $db = $this->db;

    $id = $params["id"];
    $code = $params["code"] ?? "";
    $code = strtoupper($code);
    $name = $params["name"];

    $category = $this->getCategoryById($id);
    if (!$category) {
      return $this->bad("要编辑的表单分类不存在");
    }

    // 检查编码是否已经存在
    if ($code) {
      $sql = "select count(*) as cnt from t_form_category 
              where code = '%s' and id <> '%s' ";
      $data = $db->query($sql, $code, $id);
      $cnt = $data[0]["cnt"];
      if ($cnt) {
        return $this->bad("表单分类编码[{$code}]已经存在");
      }
    } else {
      $code = "";
    }

    // 检查分类名称是否已经存在
    $sql = "select count(*) as cnt from t_form_category 
            where name = '%s' and id <> '%s' ";
    $data = $db->query($sql, $name, $id);
    $cnt = $data[0]["cnt"];
    if ($cnt) {
      return $this->bad("表单分类[{$name}]已经存在");
    }

    $sql = "update t_form_category
            set code = '%s', name = '%s'
            where id = '%s' ";
    $rc = $db->execute($sql, $code, $name, $id);
    if ($rc === false) {
      return $this->sqlError(__METHOD__, __LINE__);
    }

    // 操作成功
    $params["id"] = $id;
    return null;
  }

  /**
   * 删除表单分类
   */
  public function deleteFormCategory(&$params)
  {
    $db = $this->db;

    $id = $params["id"];
    $category = $this->getCategoryById($id);
    if (!$category) {
      return $this->bad("要删除的表单分类不存在");
    }
    $name = $category["name"];

    // 检查是否有下级分类
    $sql = "select count(*) as cnt from t_form_category where parent_id = '%s' ";
    $data = $db->query($sql, $id);
    $cnt = $data[0]["cnt"];
    if ($cnt > 0) {
      return $this->bad("分类[{$name}]还有下级分类，不能删除");
    }

    // 检查该分类下是否有表单

    return $this->todo();
  }
}
