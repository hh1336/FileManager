<?php

namespace Home\Service;

use Home\Common\FIdConst;
use Home\DAO\FileManagerDAO;

class FileManagerService extends PSIBaseExService
{
  public function loadTree($params)
  {

    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $us = new UserService();

    $params["loginUserId"] = $us->getLoginUserId();
    $dao = new FileManagerDAO($this->db());
    return $dao->loadTree($params);
  }

  public function loadLog($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $us = new UserService();
    if (!$us->hasPermission(FIdConst::WJGL_CKCZJL)) {
      $rs["msg"] = "没有权限";
      $rs["success"] = false;
      return $rs;
    }
    $params["loginUserId"] = $us->getLoginUserId();
    $dao = new FileManagerDAO($this->db());
    $rs = $dao->loadLog($params);
    return $rs;
  }

  public function backVersion($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $us = new UserService();
    if(!$us->hasPermission(FIdConst::WJGL_BBHT)){
      $rs["msg"] = "没有权限";
      $rs["success"] = false;
      return $rs;
    }
    $dao = new FileManagerDAO($this->db());
    return $dao->backVersion($params);
  }

  /**创建目录
   * @param $params
   * @return array
   */
  public function mkDir($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $dao = new FileManagerDAO($this->db());

    $params["logInfo"] = empty($params["id"]) ? "创建文件夹[" . $params["dirName"] . "]" : "编辑文件夹[" . $params["dirName"] . "]";
    $dao->log($params);

    return $dao->mkDir($params);

  }

  public function Move($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $dao = new FileManagerDAO($this->db());
    $params["logInfo"] = "移动[" . $params["name"] . "]--->[" . $params["todirname"] . "]";
    $dao->log($params);
    return $dao->Move($params);
  }

  public function loadParentDirName($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $dao = new FileManagerDAO($this->db());
    return $dao->getParentDirName($params);
  }

  public function delDir($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $dao = new FileManagerDAO($this->db());
    $params["logInfo"] = "删除文件夹[" . $params["name"] . "]";
    $dao->log($params);
    return $dao->delDir($params);
  }

  public function delFile($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $dao = new FileManagerDAO($this->db());
    $params["logInfo"] = "删除文件[" . $params["name"] . "]";
    $dao->log($params);
    return $dao->delFile($params);
  }

  public function reFile($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $dao = new FileManagerDAO($this->db());
    $dao->reFile($params);
  }

  public function upFile(&$params, $arr)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $rs["success"] = false;
    $rs["msg"] = "";
    $us = new UserService();
    $params["loginUserId"] = $us->getLoginUserId();
    $params["fileSuffix"] = substr($params["path"], (strripos($params["path"], '.') + 1), strlen($params["path"]));
    $params["fileName"] = substr($params["path"], (strripos($params["path"], '\\') + 1), (-1 - strlen($params["fileSuffix"])));
    if (!$us->hasPermission(FIdConst::WJGL_UP_FILE)) {
      $rs["msg"] = "没有权限";
      return $rs;
    }

    if (!in_array(strtolower($params["fileSuffix"]), $arr)) {
      $rs["msg"] = "非法文件，请上传有效格式";
      return $rs;
    }
    $dao = new FileManagerDAO($this->db());
    $params["logInfo"] = "上传文件[" . $params["fileName"] . "." . $params["fileSuffix"] . "]";
    $dao->log($params);
    $dao->upFile($params, $rs);
    return $rs;
  }

  public function setFileSize($id, $size)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $dao = new FileManagerDAO($this->db());
    $dao->setFileSize($id, $size);
  }

  public function convertFile($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $rs["success"] = false;
    $rs["msg"] = "";
    $us = new UserService();
    if (!$us->hasPermission(FIdConst::WJGL_YL_FILE)) {
      $rs["msg"] = "没有权限";
      return $rs;
    }
    $dao = new FileManagerDAO($this->db());
    return $dao->convertFile($params);
  }

  public function getFileByInfoId($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $dao = new FileManagerDAO($this->db());
    return $dao->getFileByInfoId($params);
  }

  public function getPathById($params)
  {
    $rs["success"] = false;
    $us = new UserService();
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    if (!$us->hasPermission(FIdConst::WJGL_DOWN_FILE)) {
      $rs["msg"] = "权限不足";
      return $rs;
    }
    if (!count($params)) {
      $rs["msg"] = "请刷新重试";
      return $rs;
    }
    $dao = new FileManagerDAO($this->db());
    $paths = $dao->getPathById($params);
    if (!count($paths)) {
      $rs["msg"] = "请刷新重试";
      return $rs;
    }
    $rs["success"] = true;
    $rs["paths"] = $paths;
    return $rs;
  }
}
