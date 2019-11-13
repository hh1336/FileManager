<?php

namespace Home\Service;

/**
 * Service 扩展基类
 *
 * @author 李静波
 */
class PSIBaseExService extends PSIBaseService
{
  private $userService = null;
  private $actionLog = null;

  private function us()
  {
    if (!$this->userService) {
      $this->userService = new UserService();
    }

    return $this->userService;
  }

  private function al()
  {
    if(!$this->actionLog){
      $this->actionLog = new FileManagerlogService();
    }

    return $this->actionLog;
  }

  /**
   * 验证用户权限
   * @param null $fid
   * @return bool
   */
  protected function hasPermission($fid = null){
    $us = $this->us();
    return $us->hasPermission($fid);
  }

  /**
   * 记录用户操作
   * @param $params
   */
  protected function logAction(&$params){
    $al = $this->al();
    return $al->log($params);
  }

  /**
   * 当前登录用户的id
   * 
   * @return string|NULL
   */
  protected function getLoginUserId()
  {
    $us = $this->us();
    return $us->getLoginUserId();
  }

  /**
   * 当前登录用户的姓名
   */
  protected function getLoginUserName()
  {
    $us = $this->us();
    return $us->getLoginUserName();
  }

  /**
   * 当前登录用户的数据域
   */
  protected function getLoginUserDataOrg()
  {
    $us = $this->us();
    return $us->getLoginUserDataOrg();
  }

  /**
   * 当前登录用户所属公司的id
   */
  protected function getCompanyId()
  {
    $us = $this->us();
    return $us->getCompanyId();
  }

  /**
   * 返回权限不足
   * @return mixed
   */
  protected function notPermission(){
    $msg = "权限不足";
    return $this->failAction($msg);
  }

  /**
   * 返回一个操作失败的信息
   * @param $msg
   * @return mixed
   */
  protected function failAction($msg){
    $rs["success"] = false;
    $rs["msg"] = $msg;
    return $rs;
  }



  /**
   * 数据库操作类
   *
   * @return \Think\Model
   */
  protected function db()
  {
    return M();
  }
}
