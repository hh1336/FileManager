<?php

namespace Home\Controller;

use Home\Common\FIdConst;
use Home\Service\UserService;
use Home\Service\FileManagerService;
use Org\Util\Date;

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
      $rs = $fms->mkDir($params);
      $this->ajaxReturn($rs);
    }
  }

  public function MoveToDir()
  {
    if (IS_POST) {
      $params["mid"] = I("post.mid");
      $params["dir_id"] = I("post.dirid");
      $params["name"] = I("post.name");
      $params["to_dir_name"] = I("post.todirname");
      $fms = new FileManagerService();
      $us = new UserService();
      $params["login_user_id"] = $us->getLoginUserId();
      $rs = $fms->Move($params);
      $this->ajaxReturn($rs);
    }
  }

  public function dirParentName()
  {
    if (IS_POST) {
      $params["id"] = I("post.id");
      $fms = new FileManagerService();
      $this->ajaxReturn($fms->loadParentDirName($params));
    }
  }

  public function delDir()
  {
    if (IS_POST) {
      $us = new UserService();
      $params["id"] = I("post.id");
      $params["name"] = I("post.name");
      $params["login_user_id"] = $us->getLoginUserId();
      $fms = new FileManagerService();

      $this->ajaxReturn($fms->delDir($params));
    }
  }

  public function delFile()
  {
    if (IS_POST) {
      $us = new UserService();
      $params["id"] = I("post.id");
      $params['name'] = I("post.name");
      $params["login_user_id"] = $us->getLoginUserId();
      $fms = new FileManagerService();

      $this->ajaxReturn($fms->delFile($params));
    }
  }

  public function upFile()
  {
    if (IS_POST) {
      $params["path"] = I("post.path");
      $params["parent_dir_id"] = I("post.parentDirID");
      $params["action_info"] = I("post.actionInfo");

      $upType = array('zip', 'rar', '7z',
        'jpg', 'gif', 'png', 'jpeg',
        'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf',
        'mp4', 'avi', 'mov', 'rmvb', 'flv', '3gp', 'mpeg', 'wmv', 'asf', 'mkv', 'rm',
        'mp3', 'wma', 'm4a', 'flac', 'ape', 'wav', 'aac',
        'iso', 'html', 'exe', 'txt', 'apk', 'bat');

      $fms = new FileManagerService();
      $rs = $fms->upFile($params, $upType);
      if (!$rs["success"]) {
        return $this->ajaxReturn($rs);
      }

      $upload = new \Think\Upload();// 实例化上传类
      $upload->maxSize = 20971520;// 设置附件上传大小 5M
      $upload->exts = $upType;// 设置附件上传类型
      $upload->savePath = ''; // 设置附件上传（子）目录
      $upload->autoSub = false;
      $upload->hash = false;
      $upload->rootPath = $params["data"]["file_path"]; // 设置附件上传根目录
      $upload->saveName = $params["data"]["file_version"];

      $info = $upload->upload();

      if (!$info) {// 上传错误提示错误信息
        $rs["msg"] = "上传错误，出现了：" . $upload->getError();
        $del_params["id"] = $params["data"]["id"];
        $del_params["login_user_id"] = $params["login_user_id"];
        $del_params["log_id"] = $params['log_id'];
        $fms->reFile($del_params);
        $this->ajaxReturn($rs);
      } else {// 上传成功
        $fms->setFileSize($params["data"]["id"], $info["file"]["size"]);
        $this->ajaxReturn($rs);
      }
    }
  }

  public function convertFile()
  {
    if (IS_POST) {
      $params["id"] = I("post.id");
      $fms = new FileManagerService();
      $rs = $fms->convertFile($params);
      return $this->ajaxReturn($rs);
    }
  }

  public function getFile()
  {
    $us = new UserService();
    if ($us->hasPermission(FIdConst::WJGL_YL_FILE)) {
      $imgType = array('jpg', 'gif', 'png', 'jpeg');
      $officeType = array('doc', 'docx', 'xls', 'xlsx', 'pptx','ppt');
      $params["file_id"] = I("fileid");
      if ($params["file_id"]) {
        $fms = new FileManagerService();
        $data = $fms->getFileByInfoId($params);
        if (in_array(strtolower($data["file_suffix"]), $officeType) || $data["file_suffix"] == "pdf") {

//          header ( "Content-Type: application/vnd.ms-excel");
          //返回的文件(流形式)
          header("Content-type: application/octet-stream");
          //按照字节大小返回
          header("Accept-Ranges: bytes");

          header ( "Content-Disposition: attachment; filename=".$data["file_version"] . "." . "pdf");
          header("Pragma:No-cache;");
          header("Cache-Control:No-cache;");
          header("Expires:0;");
          readfile ($data["file_path"] . $data["file_version"] . "." . "pdf" ,"预览.pdf");
          //@unlink($data["file_path"] . $data["file_version"] . "." . "pdf");

        } elseif (in_array(strtolower($data["file_suffix"]), $imgType)) {
          $this->show(file_get_contents($data["file_path"] . $data["file_version"] . "." . $data["file_suffix"]), "utf-8", "image/" . $data["file_suffix"]);
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
      $params["limit"] = I("post.limit");
      $params["page"] = I("post.page");
      $params["start"] = I("post.start");
      $params["id"] = I("post.id");
      $fms = new FileManagerService();
      $data = $fms->loadLog($params);
      $this->ajaxReturn($data);
    }
  }

  public function backVersion()
  {
    if (IS_POST) {
      $params["id"] = I("post.id");
      $fms = new FileManagerService();
      $data = $fms->backVersion($params);
      $this->ajaxReturn($data);
    }
  }
  
}
