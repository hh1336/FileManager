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

    $params["login_user_id"] = $this->getLoginUserId();
    $dao = new FileManagerDAO($this->db());
    //$tree_data = $dao->loadTree($params);
    $tree_data = $dao->loadDir($params);
    return $tree_data;
  }

  public function queryTree()
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $dao = new FileManagerDAO($this->db());
    $tree_data = $dao->loadTree();
    return $tree_data;
  }

  public function queryFiels($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $params["login_user_id"] = $this->getLoginUserId();
    $dao = new FileManagerDAO($this->db());
    return $dao->queryFiles($params);
  }

  public function loadLog($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    if (!$this->hasPermission(FIdConst::WJGL_CKCZJL)) {
      return $this->notPermission();
    }
    $logService = new FileManagerlogService();
    $rs = $logService->loadLog($params);
    return $rs;
  }

  public function backVersion($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }

    if (!$this->hasPermission(FIdConst::WJGL_BBHT)) {
      return $this->notPermission();
    }
    $logService = new FileManagerlogService();
    return $logService->backLog($params);
  }

  /**
   * 创建目录
   * @param $params
   * @return array
   */
  public function createDir($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $permissionService = new FileManagerPermissionService();
    if ($params["id"]) {
      if (!$this->hasPermission(FIdConst::WJGL_EDIT_DIR))
        return $this->notPermission();
      $data["file_id"] = $params["id"];
      if (!$permissionService->hasPermission($data, FIdConst::WJGL_EDIT_DIR)) {
        $msg = "你没有编辑这个文件夹的权限";
        return $this->failAction($msg);
      }
    } else {
      if (!$this->hasPermission(FIdConst::WJGL_ADD_DIR))
        return $this->notPermission();
    }


    //检测是否需要发起流程
    $params['vType'] = "dir";
    $params['vAction'] = empty($params["id"]) ? 'create' : 'edit';
    $params['service_name'] = __CLASS__;
    $params['function_name'] = __FUNCTION__;
    $pValidation = new ProcessValidationService();
    if ($pValidation->isOpenValidation($params['vType']) & !isset($params['validated']) ?? true) {//没有经过审核
      return $pValidation->validation($params);
    }

    $dao = new FileManagerDAO($this->db());
    $params["log_info"] = empty($params["id"]) ?
      "创建文件夹[" . $params["dir_name"] . "]" :
      "编辑文件夹[" . $params["dir_name"] . "]";

    $this->logAction($params);
    return $dao->createDir($params);

  }

  public function moveFilesTo($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $dao = new FileManagerDAO($this->db());
    $params["log_info"] = "移动[" . $params["name"] . "]
    --->[" . $params["to_dir_name"] . "]";

    $this->logAction($params);
    return $dao->moveFiles($params);
  }

  public function loadParentDirName($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $dao = new FileManagerDAO($this->db());
    return $dao->getParentDirName($params);
  }

  public function deleteDir($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    if (!$this->hasPermission(FIdConst::WJGL_DEL_DIR))
      return $this->notPermission();
    $permissionService = new FileManagerPermissionService();

    $data["file_id"] = $params["id"];
    if (!$permissionService->hasPermission($data, FIdConst::WJGL_DEL_DIR)) {
      return $this->notPermission();
    }

    //检测是否需要发起流程
    $params['vType'] = "dir";
    $params['vAction'] = 'delete';
    $params['service_name'] = __CLASS__;
    $params['function_name'] = __FUNCTION__;
    $pValidation = new ProcessValidationService();
    if ($pValidation->isOpenValidation($params['vType']) & !isset($params['validated']) ?? true) {//没有经过审核
      return $pValidation->validation($params);
    }
    $dao = new FileManagerDAO($this->db());
    $params["log_info"] = "删除文件夹[" . $params["name"] . "]";
    $this->logAction($params);
    return $dao->deleteDir($params);
  }

  public function deleteFile($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }

    if (!$this->hasPermission(FIdConst::WJGL_DEL_FILE)) {
      return $this->notPermission();
    }

    //验证单文件权限
    $permissionService = new FileManagerPermissionService();
    $data["file_id"] = $params["id"];
    if (!$permissionService->hasPermission($data, FIdConst::WJGL_DEL_FILE)) {
      return $this->notPermission();
    }

    //检测是否需要发起流程
    $params['vType'] = "file";
    $params['vAction'] = 'delete';
    $params['service_name'] = __CLASS__;
    $params['function_name'] = __FUNCTION__;
    $pValidation = new ProcessValidationService();
    if ($pValidation->isOpenValidation($params['vType']) & !isset($params['validated']) ?? true) {//没有经过审核
      return $pValidation->validation($params);
    }

    $dao = new FileManagerDAO($this->db());
    $params["log_info"] = "删除文件[" . $params["name"] . "]";
    $this->logAction($params);
    return $dao->deleteFile($params);
  }

  public function cancelUpLoadFile($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $dao = new FileManagerDAO($this->db());
    $dao->cancelUpLoadFile($params);
  }

  public function upLoadFile($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }

    if (!$this->hasPermission(FIdConst::WJGL_UP_FILE)) {
      return $this->notPermission();
    }
    //检测是否需要发起流程
    $params['vType'] = "file";
    $params['vAction'] = 'create';
    $params['service_name'] = __CLASS__;
    $params['function_name'] = __FUNCTION__;
    $pValidation = new ProcessValidationService();
    if ($pValidation->isOpenValidation($params['vType']) & !isset($params['validated']) ?? true) {//没有经过审核
      return $pValidation->validation($params);
    }

    $dao = new FileManagerDAO($this->db());
    $params["log_info"] = "上传文件[" . $params["name"] . "." . $params["suffix"] . "]";

    $this->logAction($params);
    $rs = $dao->upLoadFile($params);
    return $rs;
  }

  public function editFile($params, $info)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }

    if (!$this->hasPermission(FIdConst::WJGL_DEL_FILE)) {
      return $this->notPermission();
    }
    //检测是否需要发起流程
    $params['vType'] = "file";
    $params['vAction'] = 'edit';
    $params['service_name'] = __CLASS__;
    $params['function_name'] = __FUNCTION__;
    $pValidation = new ProcessValidationService();
    if ($pValidation->isOpenValidation($params['vType']) & !isset($params['validated']) ?? true) {//没有经过审核
      return $pValidation->validation($params);
    }

    $params["log_info"] = "编辑文件[" . $params["file_name"] . "]";
    $this->logAction($params);
    $dao = new FileManagerDAO($this->db());
    return $dao->editFile($params, $info);
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
    if (!$this->hasPermission(FIdConst::WJGL_YL_FILE)) {
      return $this->notPermission();
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
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    if (!$this->hasPermission(FIdConst::WJGL_DOWN_FILE)) {
      return $this->notPermission();
    }
    if (!count($params)) {
      return $this->failAction("请刷新重试");
    }
    $dao = new FileManagerDAO($this->db());
    $paths = $dao->getPathById($params);
    if (!count($paths)) {
      return $this->failAction("请刷新重试");
    }
    $rs["success"] = true;
    $rs["paths"] = $paths;
    return $rs;
  }

  public function revokeFile($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    if (!$this->hasPermission(FIdConst::WJGL_BBHT)) {
      return $this->notPermission();
    }
    $params["log_info"] = "撤回文件[" . $params["name"] . "]";
    $logService = new FileManagerlogService();
    $logService->log($params);

    return $logService->revokeFile($params);
  }
}
