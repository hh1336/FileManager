<?php

namespace Home\Service;

use Home\DAO\FormDAO;

/**
 * 自定义表单Service
 *
 * @author 李静波
 */
class FormService extends PSIBaseExService
{
  private $LOG_CATEGORY = "自定义表单";

  /**
   * 自定义表单列表
   */
  public function categoryList()
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }

    $params = [
      "loginUserId" => $this->getLoginUserId()
    ];

    $dao = new FormDAO($this->db());

    return $dao->categoryList($params);
  }

  /**
   * 新增或编辑表单分类
   */
  public function editFormCategory($params)
  {
    if ($this->isNotOnline()) {
      return $this->notOnlineError();
    }

    $id = $params["id"];
    $name = $params["name"];

    $db = $this->db();
    $db->startTrans();

    $log = null;
    $dao = new FormDAO($db);
    if ($id) {
      // 编辑
      $rc = $dao->updateFormCategory($params);
      if ($rc) {
        $db->rollback();
        return $rc;
      }

      $log = "编辑表单分类：{$name}";
    } else {
      // 新增
      $rc = $dao->addFormCategory($params);
      if ($rc) {
        $db->rollback();
        return $rc;
      }

      $id = $params["id"];
      $log = "新增表单分类：{$name}";
    }

    // 记录业务日志
    $bs = new BizlogService($db);
    $bs->insertBizlog($log, $this->LOG_CATEGORY);

    $db->commit();

    return $this->ok($id);
  }

  /**
   * 删除表单分类
   */
  public function deleteFormCategory($params)
  {
    if ($this->isNotOnline()) {
      return $this->notOnlineError();
    }

    $db = $this->db();
    $db->startTrans();

    $dao = new FormDAO($db);
    $rc = $dao->deleteFormCategory($params);
    if ($rc) {
      $db->rollback();
      return $rc;
    }

    $name = $params["name"];
    $log = "删除表单分类：{$name}";

    // 记录业务日志
    $bs = new BizlogService($db);
    $bs->insertBizlog($log, $this->LOG_CATEGORY);

    $db->commit();

    return $this->ok();
  }
}
