<?php

namespace Home\Service;

use Home\DAO\FileManagerlogDAO;

class FileManagerlogService extends PSIBaseExService
{

  /**记录log
   * @param $params
   */
  public function log(&$params)
  {
    $dao = new FileManagerlogDAO($this->db());
    $us = new UserService();
    $params["login_user_id"] = $us->getLoginUserId();

    $dao->log($params);
  }

  /**删除记录
   * @param $id
   * @param $db
   */
  public function delLog($id)
  {
    $dao = new FileManagerlogDAO($this->db());
    $dao->delLog($id);
  }

  /**添加操作记录
   * @param $params
   */
  public function addLogAction($params)
  {
    $dao = new FileManagerlogDAO($this->db());
    $dao->addLogAction($params);
  }

  /**加载版本
   * @param $params
   * @return mixed
   */
  public function loadLog($params)
  {
    $dao = new FileManagerlogDAO($this->db());
    $us = new UserService();
    $params["login_user_id"] = $us->getLoginUserId();
    return $dao->loadLog($params);
  }

  /**撤销某个版本
   * @param $params
   */
  public function backLog($params){
    $dao = new FileManagerlogDAO($this->db());
    return $dao->backVersion($params);
  }

  public function editLogRemarksById($id,$remarks){
    $dao = new FileManagerlogDAO($this->db());
    $dao->editLogRemarksById($id,$remarks);
  }

  public function revokeFile($params){
    $dao = new FileManagerlogDAO($this->db());
    return $dao->revokeFile($params);
  }
}
