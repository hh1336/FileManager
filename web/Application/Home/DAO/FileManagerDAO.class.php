<?php

namespace Home\DAO;

use Home\Common\FIdConst;
use Home\Service\UserService;
use Org\Util\OfficeConverter;
use Think\Exception;

class FileManagerDAO extends PSIBaseExDAO
{

  /**加载树
   * @param $params
   */
  public function loadTree($params)
  {
    $db = $this->db;

    $sql = "SELECT	d.id,	di.id AS id2,	di.dirName,	di.dirPath,	di.dirTruePath,	di.dirVersion,	di.actionUserID,	di.actionTime,	di.parentDirID ,	di.actionInfo, u.name as userName 
    FROM	t_dir d	
    LEFT JOIN t_dir_info di ON d.id = di.dirFKID LEFT JOIN t_user u on di.actionUserID = u.id
    WHERE	di.parentDirID is null 	AND di.isDel = 0
    order by di.dirName";

    $fileslist1 = $db->query($sql);
    $result = [];
    foreach ($fileslist1 as $i => $list1) {
      $result[$i]["id"] = $list1["id"];
      $result[$i]["id2"] = $list1["id2"];
      $result[$i]["Name"] = $list1["dirname"];
      $result[$i]["userName"] = $list1["username"];
      $result[$i]["Path"] = $list1["dirpath"];
      $result[$i]["TruePath"] = $list1["dirtruepath"];
      $result[$i]["Version"] = $list1["dirversion"];
      $result[$i]["actionUserID"] = $list1["actionuserid"];
      $result[$i]["actionTime"] = $list1["actiontime"];
      $result[$i]["parentDirID"] = $list1["parentdirid"];
      $result[$i]["actionInfo"] = $list1["actioninfo"];

      $fileslist2 = $this->AllFileInternal($list1["id2"], $db);
      $result[$i]['children'] = $fileslist2;
      $result[$i]['leaf'] = count($fileslist2) == 0;
      $result[$i]['expanded'] = true;
      $result[$i]["iconCls"] = "PSI-FileManager-Dir";
    }
    return $result;
  }

  /**加载子节点
   * @param $parentId
   * @param $db
   * @return mixed
   */
  private function AllFileInternal($parentId, $db)
  {
    $result = [];
    $sql = "SELECT	d.id,	di.id AS id2,	di.dirName,	di.dirPath,	di.dirTruePath,	di.dirVersion,	di.actionUserID,	di.actionTime,	di.parentDirID , di.actionInfo,	u.name as userName 
    FROM	t_dir d	
    LEFT JOIN t_dir_info di ON d.id = di.dirFKID LEFT JOIN t_user u on di.actionUserID = u.id
    WHERE	di.parentDirID = '%s'	AND di.isDel = 0
    order by di.dirName";

    $file_sql = "SELECT	f.id,	fi.id AS id2,	fi.fileName,	fi.filePath,	fi.fileTruePath,	fi.fileSize,	fi.fileSuffix,	fi.fileVersion,
	  fi.actionUserID,	fi.actionTime,	fi.parentDirID,	fi.actionInfo,	u.NAME AS userName 
    FROM
	  t_file f LEFT JOIN t_file_info fi ON f.id = fi.fileFKID	LEFT JOIN t_user u ON fi.actionUserID = u.id 
    WHERE	fi.parentDirID = '%s'	AND fi.isDel = 0 
    ORDER BY	fi.fileName";
    $data = array_merge($db->query($sql, $parentId), $db->query($file_sql, $parentId));
    foreach ($data as $i => $v) {
      //公共信息
      $result[$i]["id"] = $v["id"];
      $result[$i]["id2"] = $v["id2"];
      $result[$i]["actionUserID"] = $v["actionuserid"];
      $result[$i]["actionTime"] = $v["actiontime"];
      $result[$i]["parentDirID"] = $v["parentdirid"];
      $result[$i]["userName"] = $v["username"];
      $result[$i]["Version"] = $v["dirversion"] ?? $v["fileversion"];
      $result[$i]["Name"] = $v["dirname"] ?? ($v["filename"] . "." . $v["filesuffix"]);
      $result[$i]["Path"] = $v["dirpath"] ?? $v["filepath"];
      $result[$i]["TruePath"] = $v["dirtruepath"] ?? $v["filetruepath"];
      $result[$i]["actionInfo"] = $v["actioninfo"];
      //加载文件
      $result[$i]["fileSize"] = $v["filesize"] ?? "";
      $result[$i]["fileSuffix"] = !isset($v["dirname"]) ? $v["filesuffix"] : "dir";

      $data2 = empty($v["filesuffix"]) ? $this->AllFileInternal($v["id2"], $db) : array();

      $result[$i]['children'] = $data2;
      $result[$i]['leaf'] = count($data2) == 0;
      $result[$i]['expanded'] = false;//不展开
      $result[$i]["iconCls"] = isset($v["dirname"]) ? $this->getCss("dir") : $this->getCss($v["filesuffix"]);
      $result[$i]["checked"] = false;

    }
    return $result;
  }

  private function getCss($suffix)
  {
    $dir = "PSI-FileManager-Dir";
    $picture = "PSI-FileManager-Picture";
    $word = "PSI-FileManager-Word";
    $xls = "PSI-FileManager-Xls";
    $ppt = "PSI-FileManager-Ppt";
    $zip = "PSI-FileManager-Zip";

    $type = array('jpg' => $picture, 'gif' => $picture, 'png' => $picture, 'jpeg' => $picture,
      'doc' => $word, 'docx' => $word,
      'xls' => $xls, 'xlsx' => $xls,
      'ppt' => $ppt, 'pptx' => $ppt,
      'zip' => $zip, 'rar' => $zip, '7z' => $zip,
      'dir' => $dir
    );
    if (!empty($type[$suffix])) {
      return $type[$suffix];
    }
    return "PSI-FileManager-UnKnown";
  }

  /** 操作所有子文件夹和子文件
   * @param $dirs
   * @param $oldId 上级文件夹旧id
   * @param $newID 上级文件夹新id
   * @param $db
   * @param $actionUserId
   * @param $actioninfo
   */
  private function moveChildrenVersion(&$dirs, &$oldId, &$newID, $db, &$actionUserId, &$actioninfo, $logData)
  {
    if (count($dirs)) {
      foreach ($dirs as $i => $v) {
        //记录旧的t_dir_info表id
        $old_dir_info_id = $v["id"];
        //操作t_dir表
//        $new_dir_id = $this->newId();
//        $db->execute("insert into t_dir values('%s')", $new_dir_id);
        //操作t_dir_info表
        //修改为已删除
        $db->execute("update t_dir_info set isDel = 1000 where id = '%s'", $v['id']);

        $logData["actionType"] = "delete";
        $logData["fileType"] = "dir";
        $logData["fileID"] = $v['id'];
        $this->addLogAction($logData, $db);

        //构造t_dir_info数据
        $insert_dir_sql = "INSERT INTO t_dir_info ( `id`, `dirName`, `dirPath`, `dirTruePath`, `dirVersion`, `dirFKID`, `actionUserID`, `actionTime`, `isDel`, `parentDirID`, `actionInfo` )
            VALUES	('%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s');";
        $v["id"] = $this->newId();
        $v["dirversion"] = $this->newId();
        //$v["dirfkid"] = $new_dir_id;
        $v['dirpath'] = $this->getFullPath($newID, $db) . $v['dirversion'];
        //mkdir($v['dirpath']);
        $v["actionuserid"] = $actionUserId;
        $v["parentdirid"] = $newID; //子文件夹的上层目录id发生了改变
        $v["actiontime"] = Date("Y-m-d H:i:s");
        //$v["actioninfo"] = $actioninfo;

        $db->execute($insert_dir_sql, $v);

        $logData["actionType"] = "insert";
        $logData["fileID"] = $v['id'];
        $this->addLogAction($logData, $db);

        $childrenDir = $db->query("select * from t_dir_info where parentDirID = '%s' and isDel = 0", $old_dir_info_id);
        $this->moveChildrenVersion($childrenDir, $old_dir_info_id, $v["id"], $db, $actionUserId, $actioninfo, $logData);
      }
    }
    //操作文件
    $files = $db->query("select * from t_file_info where parentDirID = '%s' and isDel = 0", $oldId);
    if (count($files)) {
      foreach ($files as $i => $v) {
        //操作t_file表
//        $new_file_id = $this->newId();
//        $db->execute("insert into t_file values('%s')", $new_file_id);
        //操作t_file_info表
        //修改为已删除
        $db->execute("update t_file_info set isDel = 1000 where id = '%s'", $v['id']);

        $logData["actionType"] = "delete";
        $logData["fileType"] = "file";
        $logData["fileID"] = $v['id'];
        $this->addLogAction($logData, $db);


        //构建数据
        $old_path = $v['filepath'];
        $old_version = $v["fileversion"];
        $insert_file_sql = "INSERT INTO t_file_info ( 
        `id`, `fileName`, `filePath`, `fileTruePath`, `fileSize`, `fileSuffix`, `parentDirID`,
         `fileVersion`, `fileFKID`, `actionUserID`, `actionTime`, `isDel`, `actionInfo` )
        VALUES	('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s');";
        $v["id"] = $this->newId();
        $v["parentdirid"] = $newID;
        $v["fileversion"] = $this->newId();
        //$v["filefkid"] = $new_file_id;
        $v['filepath'] = $this->getFullPath($newID, $db);
        $v["actionuserid"] = $actionUserId;
        $v["actiontime"] = Date("Y-m-d H:i:s");
        $v["actioninfo"] = $actioninfo;
        $db->execute($insert_file_sql, $v);

        $logData["actionType"] = "insert";
        $logData["fileID"] = $v['id'];
        $this->addLogAction($logData, $db);

        //拷贝文件
        if (file_exists($old_path . $old_version . "." . $v["filesuffix"])) {
          $dir_info = $db->query("select * from t_dir_info where id = '%s'", $newID)[0];
          $this->createTrueDir($dir_info["dirpath"]);
          copy($old_path . $old_version . "." . $v["filesuffix"], $dir_info["dirpath"] . $v["fileversion"] . "." . $v["filesuffix"]);
        }

      }
    }

  }


  /** 创建或编辑目录
   * @param $params
   */
  public function mkDir($params)
  {
    $rs["success"] = false;
    $rs["msg"] = "";
    $db = $this->db;
    if (!$params['dirName']) {
      $this->delLog($params["logID"], $db);
      $rs["msg"] = "文件名为空";
      return $rs;
    }
    $us = new UserService();
    if ($params["id"]) {//编辑
      if ($us->hasPermission(FIdConst::WJGL_EDIT_DIR)) {
        //判断这个文件夹是否存在
        $dir_info = $db->query("select * from t_dir_info where id = '%s' and isDel = 0", $params["id"]);

        //保存旧id，用来修改子文件夹
        $old_dir_id = $dir_info[0]["id"];

        if (!count($dir_info)) {
          $this->delLog($params["logID"], $db);
          $rs["msg"] = "文件夹已不存在";
          return $rs;
        }
        if (!$dir_info[0]["parentdirid"]) {
          $this->delLog($params["logID"], $db);
          $rs["msg"] = "不能编辑根目录";
          return $rs;
        }

        $db->startTrans();
        //修改为已删除
        $db->execute("update t_dir_info set isDel = 1000 where id = '%s'", $params['id']);

        $logData["logID"] = $params["logID"];
        $logData["actionType"] = "delete";
        $logData["fileType"] = "dir";
        $logData["fileID"] = $params['id'];
        $this->addLogAction($logData, $db);

        //构造t_dir_info数据
        $insert_dir_sql = "INSERT INTO t_dir_info ( `id`, `dirname`, `dirpath`, `dirtruepath`, `dirversion`, `dirfkid`, `actionuserid`, `actiontime`, `isdel`, `parentdirid`, `actioninfo` )
            VALUES	('%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s');";

        $dir_info[0]["id"] = $this->newId();
        $dir_info[0]["dirname"] = $params["dirName"];
        $dir_info[0]["dirversion"] = $this->newId();
        $dir_info[0]["parentdirid"] = $params["parentDirID"];
        $dir_info[0]["actionuserid"] = $params["loginUserId"];
        $dir_info[0]["actiontime"] = Date("Y-m-d H:i:s");
        $dir_info[0]["actioninfo"] = $params["actionInfo"];

        //验证该路径下是否存在相同文件夹
        $is_dirName = $db->query("select count(*) from t_dir_info 
        where parentDirID = '%s' AND isDel = 0 AND dirName in ('%s')", $dir_info[0]["parentdirid"], $dir_info[0]["dirname"]);
        if ($is_dirName[0]["count(*)"]) {
          $db->rollback();
          $this->delLog($params["logID"], $db);
          $rs["msg"] = "文件夹已存在";
          return $rs;
        }

        $logData["actionType"] = "insert";
        $logData["fileID"] = $dir_info[0]['id'];
        $this->addLogAction($logData, $db);

        //构建目录
        $dir_info[0]["dirpath"] = $this->getFullPath($dir_info[0]["parentdirid"], $db) . $dir_info[0]["dirversion"] . "\\";
//        mkdir($dir_info[0]["dirpath"]);

        $edmkinfo = $db->execute($insert_dir_sql, $dir_info[0]);
        if (!$edmkinfo) {
          $db->rollback();
          $this->delLog($params["logID"], $db);
          $rs["msg"] = "操作失败";
          return $rs;
        }

        //迁移所有文件和文件夹到当前版本
        $childrenDir = $db->query("select * from t_dir_info where parentDirID = '%s' and isDel = 0", $old_dir_id);
        $this->moveChildrenVersion($childrenDir, $old_dir_id, $dir_info[0]["id"], $db, $params["loginUserId"], $params["actionInfo"], $logData);

        $db->commit();
        $rs["msg"] = "操作成功";
        $rs["success"] = true;
        return $rs;


      }
    } else {
      if ($us->hasPermission(FIdConst::WJGL_ADD_DIR)) {
        //插入一条id
        $dirId = $this->newId();
        $mkDirIdSql = "insert into t_dir values('%s')";
        $db->execute($mkDirIdSql, $dirId);
        //开始事务
        $db->startTrans();
        //构造数据
        $data['id'] = $this->newId();
        $data['dirName'] = $params['dirName'];
        $data['dirPath'] = "/";
        $data['dirTruePath'] = "/";
        $data['dirVersion'] = $this->newId();
        $data['dirFKID'] = $dirId;
        $data['actionUserID'] = $params['loginUserId'];
        $data['actionTime'] = Date("Y-m-d H:i:s");
        $data['isDel'] = 0;
        $data['parentDirID'] = $params['parentDirID'];
        $data['actionInfo'] = $params['actionInfo'];

        $dir_info_sql = "insert into t_dir_info 
                    (id,dirName,dirPath,dirTruePath,dirVersion,dirFKID,actionUserID,actionTime,isDel,parentDirID,actionInfo) values 
                    ('%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s')";
        //判断是否存在上级目录，不存在就设置root为目录
        $is_parent_id = $db->query("select count(*) from t_dir_info where id = '%s' AND isDel = 0", $params['parentDirID']);
        if (!$params['parentDirID'] || !$is_parent_id[0]["count(*)"]) {
          $rootDir = $db->query('select id from t_dir_info where parentDirID is null');
          $data['parentDirID'] = $rootDir[0]['id'];
        }
        //构建路径
        $data["dirPath"] = $this->getFullPath($data['parentDirID'], $db) . $data["dirVersion"] . "\\";
//        mkdir($data["dirPath"]);

        //验证该路径下是否存在相同文件夹
        $is_dirName = $db->query("select count(*) from t_dir_info 
        where parentDirID = '%s' AND isDel = 0 AND dirName in ('%s')", $data["parentDirID"], $data["dirName"]);
        if ($is_dirName[0]["count(*)"]) {
          $db->rollback();
          $db->execute("delete from t_dir where id = '%s'", $dirId);
          $this->delLog($params["logID"], $db);
          $rs["msg"] = "文件夹已存在";
          return $rs;
        }
        //插入数据
        $mkinfo = $db->execute($dir_info_sql, $data);
        if ($mkinfo) {
          $logData["logID"] = $params["logID"];
          $logData["actionType"] = "insert";
          $logData["fileType"] = "dir";
          $logData["fileID"] = $data["id"];
          $this->addLogAction($logData, $db);
          $db->commit();
          $rs["success"] = true;
          $rs["msg"] = "操作成功";
          return $rs;
        } else {
          $db->rollback();
          $rs["msg"] = "操作失败";
          return $rs;
        }
      }
    }
    $this->delLog($params["logID"], $db);
    $rs["msg"] = "权限不足";
    return $rs;
  }


  /**移动到某文件夹
   * @param $params
   */
  public function Move($params)
  {
    $db = $this->db;
    $rs["success"] = false;
    $rs["msg"] = "";
    $us = new UserService();
    if (!$us->hasPermission(FIdConst::WJGL_MOVE_FILE)) {
      $rs["msg"] = "权限不足";
      $this->delLog($params["logID"], $db);
      return $rs;
    }
    //判断要移动到的是否是一个文件夹
    $is_dir = $db->query("select count(*) from t_dir_info where id = '%s' and isDel = 0", $params["dirid"]);
    if (!$is_dir[0]["count(*)"]) {
      $rs["msg"] = "不能移动到文件中";
      $this->delLog($params["logID"], $db);
      return $rs;
    }

    $is_t_dir = $db->query("select count(*) from t_dir where id = '%s'", $params["mid"]);
    if ($is_t_dir[0]["count(*)"]) {//被移动的是文件夹
      //找到最新版本的该文件夹
      $dir_info = $db->query("select * from t_dir_info where dirFKID = '%s' and isDel = 0", $params["mid"])[0];
      $data["id"] = $dir_info["id"];
      $data["dirName"] = $dir_info["dirname"];
      $data["parentDirID"] = $params["dirid"];
      $data["loginUserId"] = $params["loginUserId"];
      $data["actionInfo"] = $dir_info["actioninfo"];
      $data["logID"] = $params["logID"];
      return $this->mkDir($data);
    }
    $is_t_file = $db->query("select count(*) from t_file where id = '%s'", $params["mid"]);

    if ($is_t_file[0]["count(*)"]) {//被移动的是文件
      $dir_info = $db->query("select * from t_dir_info where id = '%s' and isDel = 0", $params["dirid"])[0];
      $file_info = $db->query("select * from t_file_info where fileFKID = '%s' and isDel = 0", $params["mid"])[0];
      //验证目录是否存在相同文件
      $is_container = $db->query("select * from t_file_info 
    where parentDirID = '%s' AND isDel = 0 AND fileName in ('%s') AND fileSuffix in ('%s')",
        $dir_info["id"], $file_info["filename"], $file_info["filesuffix"]);
      if (count($is_container)) {
        $this->delLog($params["logID"], $db);
        $rs["msg"] = "文件已存在";
        return $rs;
      }

      $db->startTrans();
      $db->execute("update t_file_info set isDel = 1000 where id = '%s'", $file_info["id"]);

      $logData["logID"] = $params["logID"];
      $logData["actionType"] = "delete";
      $logData["fileType"] = "file";
      $logData["fileID"] = $file_info["id"];
      $this->addLogAction($logData, $db);

      $insert_sql = "insert into t_file_info
    (id, fileName, filePath, fileTruePath, fileSize, fileSuffix, parentDirID, fileVersion, fileFKID, actionUserID, actionTime, isDel, actionInfo) 
    values ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s')";

      $old_file_path = $file_info["filepath"];
      $old_file_version = $file_info["fileversion"];
      $file_info["id"] = $this->newId();
      $file_info["filepath"] = $dir_info["dirpath"];
      $file_info["parentdirid"] = $dir_info["id"];
      $file_info["fileversion"] = $this->newId();
      $file_info["actionuserid"] = $params["loginUserId"];
      $file_info["actiontime"] = Date("Y-m-d H:i:s");
      $file_info["isdel"] = 0;
      $exinfo = $db->execute($insert_sql, $file_info);

      $logData["actionType"] = "insert";
      $logData["fileID"] = $file_info["id"];
      $this->addLogAction($logData, $db);

      if (!$exinfo) {
        $db->rollback();
        $rs["msg"] = "插入数据失败";
        $this->delLog($params["logID"], $db);
        return $rs;
      }
      $this->createTrueDir($dir_info["dirpath"]);
      copy($old_file_path . $old_file_version . "." . $file_info["filesuffix"],
        $dir_info["dirpath"] . $file_info["fileversion"] . "." . $file_info["filesuffix"]);
      $db->commit();
      $rs["success"] = true;
      $rs["msg"] = "操作成功";
      return $rs;
    }
    $rs["msg"] = "数据错误";
    $this->delLog($params["logID"], $db);
    return $rs;
  }

  /**获取完整文件夹路径
   * @param $parentId
   * @param $db
   * @return string
   */
  private function getFullPath(&$parentId, $db)
  {
    $parentDir = $db->query("select dirName,parentDirID,dirVersion from t_dir_info where id = '%s' and isDel = 0", $parentId);
    $path = $parentDir[0]['dirversion'];
    $parentPath = "";
    if ($parentDir[0]["parentdirid"]) {
      $parentPath = $this->getFullPath($parentDir[0]["parentdirid"], $db);
    }

    $path = $parentPath . $path . "\\";
    return $path;
  }

  public function getParentDirName($params)
  {
    $rs["success"] = false;
    $rs["msg"] = "";
    $rs["parentDirName"] = "";
    if (!$params["id"]) {
      return ($rs["msg"] = "加载错误，ID不存在");
    }
    $sql = "select dirName from t_dir_info where id = '%s' and isDel = 0";
    $data = $this->db->query($sql, $params["id"]);

    $rs["success"] = true;
    $rs["parentDirName"] = $data[0]["dirname"];
    return $rs;
  }

  /** 删除文件夹
   * @param $params
   */
  public function delDir($params)
  {
    $rs["success"] = false;
    $rs['msg'] = "";
    $db = $this->db;
    $us = new UserService();
    if (!$us->hasPermission(FIdConst::WJGL_DEL_DIR)) {
      $this->delLog($params["logID"], $db);
      $rs["msg"] = "权限不足";
      return $rs;
    }
    $dir_info = $db->query("select * from t_dir_info where id = '%s' AND parentDirID is not null", $params["id"]);
    if (!count($dir_info)) {
      $this->delLog($params["logID"], $db);
      $rs["msg"] = "数据不存在";
      return $rs;
    }
    $db->execute("update t_dir_info set isDel = 1000 where id = '%s' ", $params["id"]);

    $logData["logID"] = $params["logID"];
    $logData["actionType"] = "delete";
    $logData["fileType"] = "dir";
    $logData["fileID"] = $params["id"];
    $this->addLogAction($logData, $db);

    $this->delDirChildren($dir_info[0]["id"], $db, $logData);
    $rs["success"] = true;
    $rs["msg"] = "操作成功";
    return $rs;
  }

  /**删除文件
   * @param $params
   * @return string
   */
  public function delFile($params)
  {
    $rs["success"] = false;
    $rs['msg'] = "";
    $us = new UserService();
    $db = $this->db;
    if (!$us->hasPermission(FIdConst::WJGL_DEL_FILE)) {
      $this->delLog($params["logID"], $db);
      $rs["msg"] = "权限不足";
      return $rs;
    }

    $file_info = $db->query("select * from t_file_info where id = '%s' AND isDel = 0", $params["id"]);
    if (!count($file_info)) {
      $rs["msg"] = "数据不存在";
      $this->delLog($params["logID"], $db);
      return $rs;
    }
    $db->execute("update t_file_info set isDel = 1000 where id = '%s' ", $params["id"]);

    $logData["logID"] = $params["logID"];
    $logData["actionType"] = "delete";
    $logData["fileType"] = "file";
    $logData["fileID"] = $params["id"];
    $this->addLogAction($logData, $db);

    unlink($file_info[0]["filepath"] . $file_info[0]["fileversion"] . ".pdf");
    $rs["success"] = true;
    $rs["msg"] = "操作成功";
    return $rs;
  }

  public function reFile($params)
  {
    $db = $this->db;
    $this->delLog($params["logID"], $db);
    $db->execute("delete from t_file_info where id = '%s' ", $params["id"]);
    $db->execute("delete from t_log_action where logID = '%s'", $params["logID"]);
  }

  /**删除文件夹中的数据
   * @param $parentId
   * @param $db
   */
  private function delDirChildren(&$parentId, $db, $logData)
  {
    //找到文件夹
    $dirs = $db->query("select * from t_dir_info where parentdirid = '%s' AND isDel = 0", $parentId);
    if (count($dirs)) {
      foreach ($dirs as $i => $v) {
        $db->execute("update t_file_info set isDel = 1000 where id = '%s'", $v["id"]);

        $logData["actionType"] = "delete";
        $logData["fileType"] = "dir";
        $logData["fileID"] = $v["id"];
        $this->addLogAction($logData, $db);

        $this->delDirChildren($v['id'], $db);
      }
    }
    //找到文件
    $files = $db->query("select * from t_file_info where parentdirid = '%s' AND isDel = 0", $parentId);
    if (count($files)) {
      foreach ($files as $i => $v) {
        $db->execute("update t_file_info set isDel = 1000 where id = '%s'", $v["id"]);
        $logData["actionType"] = "delete";
        $logData["fileType"] = "file";
        $logData["fileID"] = $v["id"];
        $this->addLogAction($logData, $db);
      }
    }

  }

  /**文件上传
   * @param $param
   * @param $rs
   */
  public function upFile(&$param, &$rs)
  {
    $db = $this->db;
    //验证名称
    $is_container = $db->query("select * from t_file_info 
                        where parentDirID = '%s' AND isDel = 0 AND fileName in ('%s') AND fileSuffix in ('%s')",
      $param["parentDirID"], $param["fileName"], $param["fileSuffix"]);
    if (count($is_container)) {
      $rs["msg"] = "文件已存在";
      $this->delLog($param["logID"], $db);
      return $rs;
    }

    $db->startTrans();
    //t_file表数据
    $t_file_id = $this->newId();
    $db->execute("insert into t_file values ('%s')", $t_file_id);

    $file_info_sql = "insert into t_file_info
    (id, fileName, filePath, fileTruePath, fileSize, fileSuffix, parentDirID, fileVersion, fileFKID, actionUserID, actionTime, isDel, actionInfo) 
    values ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s')";


    $data["id"] = $this->newId();
    $data["fileName"] = $param["fileName"];
    $data["filePath"] = "/";
    $data["fileTruePath"] = "/";
    $data["fileSize"] = "0";
    $data["fileSuffix"] = $param["fileSuffix"];
    $data["parentDirID"] = $param["parentDirID"];
    $data["fileVersion"] = $this->newId();
    $data["fileFKID"] = $t_file_id;
    $data["actionUserID"] = $param["loginUserId"];
    $data["actionTime"] = Date("Y-m-d H:i:s");
    $data["isDel"] = 0;
    $data["actionInfo"] = $param["actionInfo"];

    //判断是否存在上级目录，不存在就设置root为目录
    $is_parent_id = $db->query("select count(*) from t_dir_info where id = '%s' AND isDel = 0", $param['parentDirID']);
    if (!$param['parentDirID'] || !$is_parent_id[0]["count(*)"]) {
      $db->rollback();
      $rs["msg"] = "目录不存在或选择的不是一个目录";
      $this->delLog($param["logID"], $db);
      return $rs;
    }

    $data["filePath"] = $this->getFullPath($data['parentDirID'], $db);

    $db->execute($file_info_sql, $data);
    $this->createTrueDir($data["filePath"]);

    $logData["logID"] = $param["logID"];
    $logData["actionType"] = "insert";
    $logData["fileType"] = "file";
    $logData["fileID"] = $data["id"];
    $this->addLogAction($logData, $db);


    $db->commit();
    $param["data"] = $data;
    $rs["msg"] = "上传成功";
    $rs["success"] = true;
  }

  public function setFileSize($id, $size)
  {
    $db = $this->db;
    $sql = "update t_file_info set fileSize = '%s' where id = '%s'";
    $db->execute($sql, $size, $id);
  }

  /**转换文件
   * @param $params
   * @return string
   */
  public function convertFile($params)
  {
    $db = $this->db;
    $rs["success"] = false;
    $rs['msg'] = "";
    $sql = "select * from t_file_info where id = '%s' AND isDel = 0";
    $officeType = array('doc', 'docx', 'xls', 'xlsx', 'pptx');
    $imgType = array('jpg', 'gif', 'png', 'jpeg','pdf');
    $data = $db->query($sql, $params);
    if (!$data) {
      $rs["msg"] = "文件已不存在";
      return $rs;
    }
    //为office格式
    if (in_array(strtolower($data[0]["filesuffix"]), $officeType)) {
      $path = $data[0]["filepath"] . $data[0]["fileversion"] . "." . $data[0]["filesuffix"];
      $outpath = $data[0]["filepath"] . $data[0]["fileversion"] . '.pdf';
      if (empty($path)) {
        $rs['msg'] = "路径出错";
        return $rs;
      }
      if (file_exists($outpath)) {
        $rs["success"] = true;
        $rs['msg'] = "转换成功";
        $rs["id"] = $data[0]["id"];
        return $rs;
      }
      try {
        $p = C('JDK_PATH') . " -jar " . realpath(C("JODCONVERTER_PATH")) . " " .
          $path . " " . $outpath;
        $res = exec($p);
      } catch (Exception $e) {
        $rs['msg'] = "转换失败：" . $e->getMessage();
        return $rs;
      }

      $rs["success"] = true;
      $rs['msg'] = "转换成功";
      $rs["id"] = $data[0]["id"];
      return $rs;
    }

    //图片不需要转换
    if (in_array(strtolower($data[0]["filesuffix"]), $imgType)) {
      $rs["msg"] = "操作成功";
      $rs["success"] = true;
      $rs["id"] = $data[0]["id"];
      return $rs;
    }

    $rs["msg"] = "暂不支持格式";
    return $rs;
  }

  /**根据id查找文件信息
   * @param $params
   * @return mixed
   */
  public function getFileByInfoId($params)
  {
    $db = $this->db;
    $data = $db->query("select * from t_file_info where id = '%s' and isDel = 0", $params["fileid"]);
    return $data[0];
  }

  /**得到文件或文件夹的路径
   * @param $params id数组
   * @return array  路径数组
   */
  public function getPathById($params)
  {
    $db = $this->db;
    $paths = array();
    foreach ($params as $v) {
      $dirpath = $db->query("select dirPath,dirName from t_dir_info where id = '%s' and isDel = 0", $v);
      if (count($dirpath)) {

        //获取显示路径
        $arr = explode("\\", $dirpath[0]['dirpath']);
        $showPath = $this->getShowPath($arr, $db);

        //获取真实路径
        array_push($paths, array(/*$dirpath[0]['dirpath'] => $dirpath[0]['dirname'],*/
          "DirPath" => $showPath/*.$dirpath[0]['dirname']*/));

      } else {
        $fileinfo = $db->query("select * from t_file_info where id = '%s' and isDel = 0", $v);
        if (count($fileinfo)) {
          $arr = explode("\\", $fileinfo[0]["filepath"]);
          $showPath = $this->getShowPath($arr, $db);

          array_push($paths, array(
            "TruePath" => $fileinfo[0]["filepath"] . $fileinfo[0]["fileversion"] . "." . $fileinfo[0]["filesuffix"],
            "ShowName" => $fileinfo[0]["filename"] . "." . $fileinfo[0]["filesuffix"],
            "FilePath" => $showPath));
        }
      }
    }
    $root = $db->query("select dirVersion from t_dir_info where parentDirID is null and isDel = 0")[0]["dirversion"];
    $paths["root"] = $root;
    return $paths;
  }

  /**获取用户看到的路径
   * @param $arr
   */
  public function getShowPath(&$arr, $db)
  {
    $path = "";
    foreach ($arr as $v) {
      if (!$v) continue;
      if (strpbrk($v, ".")) return $path;
      $dirname = $db->query("select dirName from t_dir_info where dirVersion = '%s'", $v)[0]["dirname"];
      if ($dirname == '/') {
        $path = "/";
        continue;
      }
      $path = $path . $dirname . '/';
    }
    return $path;
  }

  /**记录操作记录
   * @param $params
   */
  public function log(&$params)
  {
    $db = $this->db;
    $params["logID"] = $this->newId();
    $db->execute("insert into t_log 
                (id, actionTime, actionUserID, isDel, Remarks, actionInfo)
                values('%s','%s','%s',0,'%s','%s')",
      $params["logID"], Date("Y-m-d H:i:s"), $params["loginUserId"], $params["logInfo"], $params["actionInfo"]);
  }

  /**出错时删除操作记录
   * @param $id
   * @param $db
   */
  public function delLog($id, $db)
  {
    $db->execute("delete from t_log where id = '%s'", $id);
    $db->execute("delete from t_log_action where logID = '%s'", $id);
  }

  /**添加操作记录
   * @param $data
   * @param $db
   */
  public function addLogAction($data, $db)
  {
    $data["id"] = $this->newId();
    $db->execute("insert into t_log_action ( id, actionType, fileType, fileID, logID )
        values('%s','%s','%s','%s','%s')",
      $data["id"], $data["actionType"], $data["fileType"], $data["fileID"], $data["logID"]);
  }

  /**加载版本
   * @param $params
   * @return mixed
   */
  public function loadLog($params)
  {
    $db = $this->db;

    $data = $db->query("SELECT	l.id,	l.actionUserID,	u.name as actionUserName,	l.actionTime,	l.actionInfo,	l.Remarks
            FROM	t_log AS l
	          LEFT JOIN t_user AS u ON l.actionUserID = u.id 
            WHERE	isDel = 0	ORDER BY l.actionTime DESC
            LIMIT %d,%d", $params["start"], $params["limit"]);

    $totalCount = $db->query("select count(*) from t_log where isDel = 0");
    $rs["dataList"] = $data;
    $rs["totalCount"] = $totalCount[0]["count(*)"];
    return $rs;
  }

  /**版本回退
   * @param $params
   * @return mixed
   */
  public function backVersion($params)
  {
    $db = $this->db;
    $rs["success"] = false;

    $toVersion = $db->query("select * from t_log where id = '%s' and isDel = 0", $params["id"]);
    if (!count($toVersion)) {
      $rs["msg"] = "没有对应版本，请刷新";
      return $rs;
    }

    $versions = $db->query("select id,actionTime from t_log where actionTime >= '%s' and isDel = 0 
                                order by actionTime desc", $toVersion[0]["actiontime"]);
    $db->startTrans();
    $db->execute("update t_log set isDel = 1000 where actionTime >= '%s' and isDel = 0", $toVersion[0]["actiontime"]);
    try {
      foreach ($versions as $v) {
        $actions = $db->query("select * from t_log_action where logID = '%s'", $v["id"]);

        foreach ($actions as $action) {
          $this->backAction($action, $db);
        }
      }
    } catch (Exception $e) {
      $db->rollback();
      $rs["msg"] = "操作失败：" + $e->getMessage();
      return $rs;
    }
    $rs["success"] = true;
    $rs["msg"] = "操作成功";
    $db->commit();
    return $rs;
  }

  private function backAction($params, &$db)
  {
    $action_sql = "update %s set isDel = ";
    if ($params["actiontype"] == "insert") {//撤回插入操作
      $action_sql .= "1000";
    } else {
      $action_sql .= "0";
    }
    $action_sql .= " where id = '%s' ";

    if ($params["filetype"] == "file") {//对文件进行撤回
      $db->execute($action_sql, "t_file_info", $params["fileid"]);
    } else {
      $db->execute($action_sql, "t_dir_info", $params["fileid"]);
    }
  }

  /**创建真实路径
   * @param $path
   */
  private function createTrueDir($path)
  {
    $temp = "";
    $temppath = substr($path,0,strlen($path)-1);
    $dirNames = explode('\\', $temppath);
    foreach ($dirNames as $p) {
      if (!empty($p)) {
        $temp .= $p . "\\";
        mkdir($temp);
      }
    }
  }
}
