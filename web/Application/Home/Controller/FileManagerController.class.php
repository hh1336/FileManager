<?php

namespace Home\Controller;

use Home\Common\FIdConst;
use Home\Service\FileManagerPermissionService;
use Home\Service\SuffixConfigService;
use Home\Service\UserService;
use Home\Service\FileManagerService;

class FileManagerController extends PSIBaseController
{

  public function index()
  {
    $this->initVar();
    $us = new UserService();
    if (!$us->hasPermission(FIdConst::WJGL)) {
      $this->gotoLoginPage("/Home/User/index");
    }
    $this->assign("title", "文件管理");

    $this->assign("AddDir", $us->hasPermission(FIdConst::WJGL_ADD_DIR) ? 1 : 0);
    $this->assign("EditDir", $us->hasPermission(FIdConst::WJGL_EDIT_DIR) ? 1 : 0);
    $this->assign("DeleteDir", $us->hasPermission(FIdConst::WJGL_DEL_DIR) ? 1 : 0);
    $this->assign("UpFile", $us->hasPermission(FIdConst::WJGL_UP_FILE) ? 1 : 0);
    $this->assign("DeleteFile", $us->hasPermission(FIdConst::WJGL_DEL_FILE) ? 1 : 0);
    $this->assign("EditFile", $us->hasPermission(FIdConst::WJGL_EDIT_FILE) ? 1 : 0);
    $this->assign("PreviewFile", $us->hasPermission(FIdConst::WJGL_YL_FILE) ? 1 : 0);
    $this->assign("Move", $us->hasPermission(FIdConst::WJGL_MOVE_FILE) ? 1 : 0);
    $this->assign("DownLoad", $us->hasPermission(FIdConst::WJGL_DOWN_FILE) ? 1 : 0);
    $this->assign("LookActionLog", $us->hasPermission(FIdConst::WJGL_CKCZJL) ? 1 : 0);
    $this->assign("ActionLog", $us->hasPermission(FIdConst::WJGL_BBHT) ? 1 : 0);
    $this->assign("FilePermission", $us->hasPermission(FIdConst::WJGL_DWJQX) ? 1 : 0);
    $this->display();
  }

  public function loadDir()
  {
    if (IS_POST) {
      $params["parent_dir_id"] = I("post.parentDirID");
      $fms = new FileManagerService();
      $us = new UserService();
      if ($us->hasPermission(FIdConst::WJGL)) {
        $data = $fms->loadTree($params);
        $this->ajaxReturn($data);
      } else {
        $this->ajaxReturn($this->noPermission("没有权限"));
      }

    }
  }

  public function queryFiles()
  {
    if (IS_POST) {
      $params = [
        "name" => I("post.name"),
        "type" => I("post.type")
      ];
      $fms = new FileManagerService();
      $this->ajaxReturn($fms->queryFiels($params));
    }
  }

  public function queryTree()
  {
    $fms = new FileManagerService();
    $this->ajaxReturn($fms->queryTree());
  }

  public function mkDirOrEdit()
  {
    if (IS_POST) {
      $params = [
        "id" => I("post.id"),
        "dir_name" => I("post.dirName"),
        "parent_dir_id" => I("post.parentDirID"),
        "action_info" => I("post.actionInfo")
      ];
      $fms = new FileManagerService();
      $us = new UserService();
      $params["login_user_id"] = $us->getLoginUserId();
      $rs = $fms->createDir($params);
      $this->ajaxReturn($rs);
    }
  }

  public function MoveToDir()
  {
    if (IS_POST) {
      $params = [
        "mid" => I("post.mid"),
        "dir_id" => I("post.dirid"),
        "name" => I("post.name"),
        "to_dir_name" => I("post.todirname")
      ];
      $fms = new FileManagerService();
      $us = new UserService();
      $params["login_user_id"] = $us->getLoginUserId();
      $rs = $fms->moveFilesTo($params);
      $this->ajaxReturn($rs);
    }
  }

  public function dirParentName()
  {
    if (IS_POST) {
      $params = [
        "id" => I("post.id")
      ];
      $fms = new FileManagerService();
      $this->ajaxReturn($fms->loadParentDirName($params));
    }
  }

  public function delDir()
  {
    if (IS_POST) {
      $us = new UserService();
      $params = [
        "id" => I("post.id"),
        "name" => I("post.name"),
        "login_user_id" => $us->getLoginUserId()
      ];
      $fms = new FileManagerService();
      $this->ajaxReturn($fms->deleteDir($params));
    }
  }

  public function delFile()
  {
    if (IS_POST) {
      $us = new UserService();
      $params = [
        "id" => I("post.id"),
        "name" => I("post.name"),
        "login_user_id" => $us->getLoginUserId()
      ];
      $fms = new FileManagerService();
      $this->ajaxReturn($fms->deleteFile($params));
    }
  }

  public function upFile()
  {
    if (IS_POST) {
      $params = [
        "parent_dir_id" => I("post.parentDirID"),
        "action_info" => I("post.actionInfo"),
        "file_code" => I("post.fileCode")
      ];

      $suffixService = new SuffixConfigService();
      $upType = $suffixService->getSuffixs();

      $fms = new FileManagerService();

      $upload = new \Think\Upload();// 实例化上传类
      $upload->maxSize = 20971520;// 设置附件上传大小 5M
      $upload->exts = $upType;// 设置附件上传类型
      $upload->savePath = ''; // 设置附件上传（子）目录
      $upload->autoSub = false;
      $upload->hash = false;

      $info = $upload->upload();

      if (!$info) {// 上传错误提示错误信息
        $rs["msg"] = "上传[" . $params["data"]['file_name'] . "]，出现了：" . $upload->getError();
        $del_params["id"] = $params["data"]["id"];
        $del_params["login_user_id"] = $params["login_user_id"];
        $del_params["log_id"] = $params['log_id'];
        $fms->cancelUpLoadFile($del_params);
        $this->ajaxReturn($rs);
      } else {// 上传成功
        $params['name'] = $info['file']['name'];
        $params['save_name'] = $info['file']['savename'];
        $params['path'] = 'Uploads/' . $info['file']['savename'];
        $params['size'] = $info['file']['size'];
        $params['suffix'] = $info['file']['ext'];
        $rs = $fms->upLoadFile($params);
        $this->ajaxReturn($rs);
      }
    }
  }


  public function editFile()
  {
    if (IS_POST) {
      $params = [
        "id" => I("post.fileId"),
        "action_info" => I("post.actionInfo"),
        "path" => I("post.path"),
        "file_name" => I("post.fileName"),
        "file_code" => I("post.fileCode")
      ];

      $info = "";

      $suffixService = new SuffixConfigService();
      $upType = $suffixService->getSuffixs();
      if (!empty($params["path"])) {
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 20971520;// 设置附件上传大小 5M
        $upload->exts = $upType;// 设置附件上传类型
        $upload->savePath = ''; // 设置附件上传（子）目录
        $upload->autoSub = false;
        $upload->hash = false;
        $upload->rootPath = "Uploads/"; // 设置附件上传根目录

        $info = $upload->upload();
      }

      $fms = new FileManagerService();
      $rs = $fms->editFile($params, $info);
      $this->ajaxReturn($rs);
    }
  }

  public function convertFile()
  {
    if (IS_POST) {
      $params = [
        "id" => I("post.id")
      ];
      $fms = new FileManagerService();
      $rs = $fms->convertFile($params);
      return $this->ajaxReturn($rs);
    }
  }

  public function getFile()
  {
    $us = new UserService();
    if ($us->hasPermission(FIdConst::WJGL_YL_FILE)) {
      $suffixService = new SuffixConfigService();
      $imgType = $suffixService->getSuffixs('picture');
      $officeType = $suffixService->getSuffixs('office');
      $params = [
        "file_id" => I("fileid")
      ];
      if ($params["file_id"]) {
        $fms = new FileManagerService();
        $data = $fms->getFileByInfoId($params);


        if (in_array(strtolower($data["file_suffix"]), $officeType) || $data["file_suffix"] == "pdf") {
          //解决跨域请求问题
          header("Access-Control-Allow-Origin: *");
          header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With');
          header("Content-type: text/json; charset=utf-8");
          //按照字节大小返回
          header("Accept-Ranges: bytes");
          header("Pragma:No-cache;");
          header("Cache-Control:No-cache;");
          header("Expires:0;");
          //返回的文件(流形式)
          header("Content-type: application/octet-stream");
          header("Content-Disposition: attachment; filename=" . $data["file_version"] . "." . "pdf");
          readfile($data["file_path"] . $data["file_version"] . "." . "pdf", "预览.pdf");

        } elseif (in_array(strtolower($data["file_suffix"]), $imgType)) {

          header("Content-type: " . "image/" . strtolower($data["file_suffix"]));
          readfile($data["file_path"] . $data["file_version"] . "." . $data["file_suffix"],
            "预览." . strtolower($data["file_suffix"]));
        } else {
          $this->show("<h2>暂不支持该格式预览</h2>", "utf-8", "text/html");
        }
      }
    }
  }

  public function downLoad()
  {
    if (IS_POST) {
      $params = I("post.str");
      $arr = explode("|", $params);
      $fms = new FileManagerService();
      $rs = $fms->getPathById($arr);
      if ($rs["success"]) {
        if (!file_exists($rs["paths"]["root"] . '/download')) {
          mkdir($rs["paths"]["root"] . '/download');
        }
        $tmpFile = tempnam('/temp', '');  //临时文件
        $zip = new \ZipArchive();
        $zip->open($tmpFile, \ZipArchive::CREATE);
        foreach ($rs["paths"] as $v) {
          if (isset($v["DirPath"])) {//添加文件夹
            $zip->addEmptyDir($v["DirPath"]);
          } elseif (isset($v["FilePath"])) {//添加文件
            if ($v['FilePath'] != "/") {
              $zip->addEmptyDir($v['FilePath']);
            }
            $zip->addFile($v['TruePath'], $v['FilePath'] . $v["ShowName"]);
          }
        }
        $zip->close();
        copy($tmpFile, $rs["paths"]["root"] . '/download/' . Date("Y-m-d H-i-s") . '.zip');
        unlink($tmpFile);
        $rs["url"] = __ROOT__ . '/' . $rs["paths"]["root"] . '/download/' . Date("Y-m-d H-i-s") . '.zip';
      }

      $rs["paths"] = "";
      $this->ajaxReturn($rs);

    }
  }

  public function loadActionLog()
  {
    if (IS_POST) {
      $params = [
        "limit" => I("post.limit"),
        "page" => I("post.page"),
        "start" => I("post.start"),
        "id" => I("post.id")
      ];
      $fms = new FileManagerService();
      $data = $fms->loadLog($params);
      $this->ajaxReturn($data);
    }
  }

  public function backVersion()
  {
    if (IS_POST) {
      $params = [
        "id" => I("post.id")
      ];
      $fms = new FileManagerService();
      $data = $fms->backVersion($params);
      $this->ajaxReturn($data);
    }
  }

  public function revokeFile()
  {
    if (IS_POST) {
      $params = [
        "name" => I("post.fileName"),
        "id" => I("post.id")
      ];
      $fms = new FileManagerService();
      $data = $fms->revokeFile($params);
      $this->ajaxReturn($data);
    }
  }

  public function loadRole()
  {
    if (IS_POST) {
      $permissionService = new FileManagerPermissionService();
      $data = $permissionService->loadRole();
      $this->ajaxReturn($data);
    }
  }

  public function getRolePermission()
  {
    if (IS_POST) {
      $params = [
        "file_id" => I("post.fileId"),
        "role_id" => I("post.roleId")
      ];
      $permissionService = new FileManagerPermissionService();
      $data = $permissionService->loadRolePermission($params);
      $this->ajaxReturn($data);
    }
  }

  public function setRolePermission()
  {
    if (IS_POST) {
      $params = [
        "file_id" => I("post.fileId"),
        "role_id" => I("post.roleId"),
        "file_type" => I("post.fileType"),
        "checked" => I("post.checked")
      ];
      $permissionService = new FileManagerPermissionService();
      $data = $permissionService->setRolePermission($params);
      $this->ajaxReturn($data);
    }
  }

}
