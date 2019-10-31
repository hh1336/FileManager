<?php

namespace Home\DAO;

use Home\Common\FIdConst;
use Home\Service\FileManagerlogService;
use Home\Service\FileManagerPermissionService;
use Home\Service\SuffixConfigService;
use Home\Service\UserService;
use Org\Util\OfficeConverter;
use Think\Exception;

class FileManagerDAO extends PSIBaseExDAO
{

  /**
   * 按需加载文件
   * @param $params
   * @return array
   */
  public function loadDir($params)
  {
    $db = $this->db;
    $sql = "SELECT	d.id,	di.id AS id2,	di.dir_name,	di.dir_path, di.dir_version,
                di.action_user_id,	di.action_time,	di.parent_dir_id ,	di.action_info, u.name as user_name 
                FROM	t_dir d	
                LEFT JOIN t_dir_info di ON d.id = di.dir_fid LEFT JOIN t_user u on di.action_user_id = u.id
                WHERE	di.parent_dir_id = '%s'	AND di.is_del = 0
                order by di.dir_name";

    $rootsql = "SELECT	d.id,	di.id AS id2,	di.dir_name,	di.dir_path, di.dir_version,
                di.action_user_id,	di.action_time,	di.parent_dir_id ,	di.action_info, u.name as user_name 
                FROM	t_dir d	
                LEFT JOIN t_dir_info di ON d.id = di.dir_fid LEFT JOIN t_user u on di.action_user_id = u.id
                WHERE	di.parent_dir_id is null 	AND di.is_del = 0
                order by di.dir_name";
    $root = $db->query($rootsql);

    $datalist = [];
    //是根目录
    if (empty($params["parent_dir_id"]) || $root[0]["id2"] == $params["parent_dir_id"]) {
      $params["parent_dir_id"] = $root[0]["id2"];
      $params["is_root"] = true;
    }
    //找当前目录
    $grand_dir = $db->query("select id,parent_dir_id from t_dir_info where id = '%s' AND is_del = 0",
      $params["parent_dir_id"]);
    //找子目录
    $dirlist = $db->query($sql, $params["parent_dir_id"]);
    $file_sql = "SELECT	f.id,	fi.id AS id2,	fi.file_name,	fi.file_path,	fi.file_size,	fi.file_suffix,	fi.file_version,
	  fi.action_user_id,	fi.action_time,	fi.parent_dir_id,	fi.action_info,	u.NAME AS user_name 
    FROM
	  t_file f LEFT JOIN t_file_info fi ON f.id = fi.file_fid	LEFT JOIN t_user u ON fi.action_user_id = u.id 
    WHERE	fi.parent_dir_id = '%s'	AND fi.is_del = 0 
    ORDER BY	fi.file_name";
    $filelist = $db->query($file_sql, $params["parent_dir_id"]);
    $datalist = array_merge($dirlist, $filelist);

    $result = [];
    foreach ($datalist as $i => $v) {
      //公共信息
      $result[$i]["id"] = $v["id"];
      $result[$i]["id2"] = $v["id2"];
      $result[$i]["actionUserID"] = $v["action_user_id"];
      $result[$i]["actionTime"] = date("Y-m-d H:i:s", strtotime($v["action_time"]));
      $result[$i]["parentDirID"] = $v["parent_dir_id"];
      $result[$i]["userName"] = $v["user_name"];
      $result[$i]["Version"] = $v["dir_version"] ?? $v["file_version"];
      $result[$i]["Name"] = $v["dir_name"] ?? ($v["file_name"] . "." . $v["file_suffix"]);
      $result[$i]["actionInfo"] = $v["action_info"];
      //加载文件
      $result[$i]["fileSize"] = $v["file_size"] ?? "";
      $result[$i]["fileSuffix"] = !isset($v["dir_name"]) ? $v["file_suffix"] : "dir";
      $result[$i]['children'] = array();
      $result[$i]['leaf'] = true;
      $result[$i]['expanded'] = true;//不展开
      $result[$i]["iconCls"] = isset($v["dir_name"]) ? $this->getCss("dir") : $this->getCss($v["file_suffix"]);
      $result[$i]["checked"] = false;
    }
    if (!$params["is_root"]) {
      $parent_data["Name"] = "../";
      $parent_data['leaf'] = true;
      $parent_data['expanded'] = true;
      $parent_data["iconCls"] = $this->getCss("dir");
      $parent_data["fileSuffix"] = "dir";
      $parent_data["id2"] = $grand_dir[0]["parent_dir_id"];
      $parent_data["parentDirID"] = $grand_dir[0]["id"];

      array_unshift($result, $parent_data);
      if ($params["parent_dir_id"]) {
        $permissionService = new FileManagerPermissionService();
        $data["file_id"] = $params["parent_dir_id"];
        if (!$permissionService->hasPermission($data, FIdConst::WJGL_INTO_DIR)) {
          return array($parent_data);
        }
      }
    }


    return $result;
  }

  /**
   * 一次性加载文件呈树状
   * @param $params
   * @return mixed
   */
  public function loadTree($params)
  {
    $db = $this->db;

    $sql = "SELECT	d.id,	di.id AS id2,	di.dir_name,	di.dir_path, di.dir_version,
                di.action_user_id,	di.action_time,	di.parent_dir_id ,	di.action_info, u.name as user_name
                FROM	t_dir d
                LEFT JOIN t_dir_info di ON d.id = di.dir_fid LEFT JOIN t_user u on di.action_user_id = u.id
                WHERE	di.parent_dir_id is null 	AND di.is_del = 0
                order by di.dir_name";

    $fileslist1 = $db->query($sql);
    $result = [];
    foreach ($fileslist1 as $i => $list1) {
      $result[$i]["id"] = $list1["id"];
      $result[$i]["id2"] = $list1["id2"];
      $result[$i]["Name"] = $list1["dir_name"];
      $result[$i]["userName"] = $list1["user_name"];
      $result[$i]["Version"] = $list1["dir_version"];
      $result[$i]["actionUserID"] = $list1["action_user_id"];
      $result[$i]["actionTime"] = date("Y-m-d H:i:s", strtotime($list1["action_time"]));
      $result[$i]["parentDirID"] = $list1["parent_dir_id"];
      $result[$i]["actionInfo"] = $list1["action_info"];

      $fileslist2 = $this->allFileInternal($list1["id2"], $db);
      $result[$i]['children'] = $fileslist2;
      $result[$i]['leaf'] = count($fileslist2) == 0;
      $result[$i]['expanded'] = true;
      $result[$i]["iconCls"] = "PSI-FileManager-Dir";
    }
    return $result[0];
  }

  /**
   * 加载子节点
   * @param $parentId
   * @param $db
   * @return array
   */
  private function allFileInternal($parentId, $db)
  {
    $result = [];
    $sql = "SELECT	d.id,	di.id AS id2,	di.dir_name,	di.dir_path, di.dir_version,	di.action_user_id,
                di.action_time,	di.parent_dir_id ,	di.action_info, u.name as user_name
                FROM	t_dir d
                LEFT JOIN t_dir_info di ON d.id = di.dir_fid LEFT JOIN t_user u on di.action_user_id = u.id
                WHERE	di.parent_dir_id = '%s'	AND di.is_del = 0
                order by di.dir_name";

    $file_sql = "SELECT	f.id,	fi.id AS id2,	fi.file_name,	fi.file_path,	fi.file_size,	fi.file_suffix,	fi.file_version,
                fi.action_user_id,	fi.action_time,	fi.parent_dir_id,	fi.action_info,	u.NAME AS user_name
                FROM
                t_file f LEFT JOIN t_file_info fi ON f.id = fi.file_fid	LEFT JOIN t_user u ON fi.action_user_id = u.id
                WHERE	fi.parent_dir_id = '%s'	AND fi.is_del = 0
                ORDER BY	fi.file_name";
    $data = array_merge($db->query($sql, $parentId), $db->query($file_sql, $parentId));
    foreach ($data as $i => $v) {
      //公共信息
      $result[$i]["id"] = $v["id"];
      $result[$i]["id2"] = $v["id2"];
      $result[$i]["actionUserID"] = $v["action_user_id"];
      $result[$i]["actionTime"] = date("Y-m-d H:i:s", strtotime($v["action_time"]));
      $result[$i]["parentDirID"] = $v["parent_dir_id"];
      $result[$i]["userName"] = $v["user_name"];
      $result[$i]["Version"] = $v["dir_version"] ?? $v["file_version"];
      $result[$i]["Name"] = $v["dir_name"] ?? ($v["file_name"] . "." . $v["file_suffix"]);
      $result[$i]["actionInfo"] = $v["action_info"];
      //加载文件
      $result[$i]["fileSize"] = $v["file_size"] ?? "";
      $result[$i]["fileSuffix"] = !isset($v["dir_name"]) ? $v["file_suffix"] : "dir";

      $data2 = empty($v["file_suffix"]) ? $this->allFileInternal($v["id2"], $db) : array();

      $result[$i]['children'] = $data2;
      $result[$i]['leaf'] = count($data2) == 0;
      $result[$i]['expanded'] = false;//不展开
      $result[$i]["iconCls"] = isset($v["dir_name"]) ? $this->getCss("dir") : $this->getCss($v["file_suffix"]);
      $result[$i]["checked"] = false;

    }
    return $result;
  }

  /**
   * 获取文件对应的css样式
   * @param $suffix
   * @return mixed|string
   */
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
    if (!empty($type[strtolower($suffix)])) {
      return $type[strtolower($suffix)];
    }
    return "PSI-FileManager-UnKnown";
  }

  /**
   * 递归移动文件夹下的所有文件夹和文件
   * @param $dirs
   * @param $oldId
   * @param $newID
   * @param $db
   * @param $actionUserId
   * @param $actioninfo
   * @param $logData
   */
  private function moveChildrenVersion(&$dirs, &$oldId, &$newID, $db, &$actionUserId, &$actioninfo, $logData)
  {
    $logService = new FileManagerlogService();
    if (count($dirs)) {
      foreach ($dirs as $i => $v) {
        //记录旧的t_dir_info表id
        $old_dir_info_id = $v["id"];
        //操作t_dir表
//        $new_dir_id = $this->newId();
//        $db->execute("insert into t_dir values('%s')", $new_dir_id);
        //操作t_dir_info表
        //修改为已删除
        $db->execute("update t_dir_info set is_del = 1000 where id = '%s'", $v['id']);

        $logData["action_type"] = "delete";
        $logData["file_type"] = "dir";
        $logData["file_id"] = $v['id'];
        $logService->addLogAction($logData);

        //构造t_dir_info数据
        $insert_dir_sql = "INSERT INTO t_dir_info ( id, dir_name, dir_path, dir_version, dir_fid,
                action_user_id, action_time, is_del, parent_dir_id, action_info )
                VALUES	('%s','%s','%s','%s','%s','%s','%s','%d','%s','%s');";
        $v["id"] = $this->newId();
        $v["dir_version"] = $this->newId();
        //$v["dirfkid"] = $new_dir_id;
        $v['dir_path'] = $this->getFullPath($newID, $db) . $v['dir_version'];
        //mkdir($v['dirpath']);
        $v["action_user_id"] = $actionUserId;
        $v["parent_dir_id"] = $newID; //子文件夹的上层目录id发生了改变
        $v["action_time"] = Date("Y-m-d H:i:s");
        //$v["actioninfo"] = $actioninfo;

        $db->execute($insert_dir_sql, $v);

        $logData["action_type"] = "insert";
        $logData["file_id"] = $v['id'];
        $logService->addLogAction($logData);

        $childrenDir = $db->query("select * from t_dir_info where parent_dir_id = '%s' and is_del = 0", $old_dir_info_id);
        $this->moveChildrenVersion($childrenDir, $old_dir_info_id,
          $v["id"], $db, $actionUserId, $actioninfo, $logData);
      }
    }
    //操作文件
    $files = $db->query("select * from t_file_info where parent_dir_id = '%s' and is_del = 0", $oldId);
    if (count($files)) {
      foreach ($files as $i => $v) {
        //操作t_file表
//        $new_file_fid = $this->newId();
//        $db->execute("insert into t_file values('%s')", $new_file_fid);
        //操作t_file_info表
        //修改为已删除
        $db->execute("update t_file_info set is_del = 1000 where id = '%s'", $v['id']);

        $logData["action_type"] = "delete";
        $logData["file_type"] = "file";
        $logData["file_id"] = $v['id'];
        $logService = new FileManagerlogService();
        $logService->addLogAction($logData);


        //构建数据
        $old_path = $v['file_path'];
        $old_version = $v["file_version"];
        $insert_file_sql = "INSERT INTO t_file_info (
        id, file_name, file_path,  file_size, file_suffix, parent_dir_id,
         file_version, file_fid, action_user_id, action_time, is_del, action_info )
        VALUES	('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s');";
        $v["id"] = $this->newId();
        $v["parent_dir_id"] = $newID;
        $v["file_version"] = $this->newId();
        //$v["filefkid"] = $new_file_id;
        $v['file_path'] = $this->getFullPath($newID, $db);
        $v["action_user_id"] = $actionUserId;
        $v["action_time"] = Date("Y-m-d H:i:s");
        $v["action_info"] = $actioninfo;
        $db->execute($insert_file_sql, $v);

        $logData["action_type"] = "insert";
        $logData["file_id"] = $v['id'];
        $logService->addLogAction($logData);

        //拷贝文件
        if (file_exists($old_path . $old_version . "." . $v["file_suffix"])) {
          $dir_info = $db->query("select * from t_dir_info where id = '%s'", $newID)[0];
          $this->createTrueDirPath($dir_info["dir_path"]);
          copy($old_path . $old_version . "." . $v["file_suffix"],
            $dir_info["dir_path"] . $v["file_version"] . "." . $v["file_suffix"]);
        }

      }
    }

  }


  /**
   * 创建或编辑文件夹
   * @param $params
   * @return mixed
   */
  public function createDir($params)
  {
    $rs["success"] = false;
    $rs["msg"] = "";
    $db = $this->db;
    $logService = new FileManagerlogService();
    $permissionService = new FileManagerPermissionService();
    if (!$params['dir_name']) {
      $logService->deleteLog($params["log_id"]);

      $rs["msg"] = "文件名为空";
      return $rs;
    }
    $us = new UserService();
    if ($params["id"]) {//编辑
      if ($us->hasPermission(FIdConst::WJGL_EDIT_DIR)) {


        $data["file_id"] = $params["id"];
        if (!$permissionService->hasPermission($data, FIdConst::WJGL_EDIT_DIR)) {
          $logService->deleteLog($params["log_id"]);
          $rs["msg"] = "你没有编辑这个文件夹的权限";
          return $rs;
        }


        //判断这个文件夹是否存在
        $dir_info = $db->query("select * from t_dir_info where id = '%s' and is_del = 0", $params["id"]);

        //保存旧id，用来修改子文件夹
        $old_dir_id = $dir_info[0]["id"];

        if (!count($dir_info)) {
          $logService->deleteLog($params["log_id"]);
          $rs["msg"] = "文件夹已不存在";
          return $rs;
        }
        if (!$dir_info[0]["parent_dir_id"]) {
          $logService->deleteLog($params["log_id"]);
          $rs["msg"] = "不能编辑根目录";
          return $rs;
        }

        $db->startTrans();
        //修改为已删除
        $db->execute("update t_dir_info set is_del = 1000 where id = '%s'", $params['id']);

        $logData["log_id"] = $params["log_id"];
        $logData["action_type"] = "delete";
        $logData["file_type"] = "dir";
        $logData["file_id"] = $params['id'];
        $logService->addLogAction($logData);

        //构造t_dir_info数据
        $insert_dir_sql = "INSERT INTO t_dir_info ( id, dir_name, dir_path,  dir_version, dir_fid,
                  action_user_id, action_time, is_del, parent_dir_id, action_info )
                  VALUES	('%s','%s','%s','%s','%s','%s','%s','%d','%s','%s');";

        $dir_info[0]["id"] = $this->newId();
        $dir_info[0]["dir_name"] = $params["dir_name"];
        $dir_info[0]["dir_version"] = $this->newId();
        $dir_info[0]["parent_dir_id"] = $params["parent_dir_id"];
        $dir_info[0]["action_user_id"] = $params["login_user_id"];
        $dir_info[0]["action_time"] = Date("Y-m-d H:i:s");
        $dir_info[0]["action_info"] = $params["action_info"];

        //验证该路径下是否存在相同文件夹
        $is_dirName = $db->query("select count(*) from t_dir_info
            where parent_dir_id = '%s' AND is_del = 0 AND dir_name in ('%s')",
          $dir_info[0]["parent_dir_id"], $dir_info[0]["dir_name"]);
        if ($is_dirName[0]["count(*)"]) {
          $db->rollback();
          $logService->deleteLog($params["log_id"]);
          $rs["msg"] = "文件夹已存在";
          return $rs;
        }

        $logData["action_type"] = "insert";
        $logData["file_id"] = $dir_info[0]['id'];
        $logService->addLogAction($logData);

        //构建目录
        $dir_info[0]["dir_path"] = $this->getFullPath($dir_info[0]["parent_dir_id"], $db) .
          $dir_info[0]["dir_version"] . "\\";
//        mkdir($dir_info[0]["dirpath"]);

        $edmkinfo = $db->execute($insert_dir_sql, $dir_info[0]);
        if (!$edmkinfo) {
          $db->rollback();
          $logService->deleteLog($params["log_id"]);
          $rs["msg"] = "操作失败";
          return $rs;
        }

        //迁移所有文件和文件夹到当前版本
        $childrenDir = $db->query("select * from t_dir_info where parent_dir_id = '%s' and is_del = 0", $old_dir_id);
        $this->moveChildrenVersion($childrenDir, $old_dir_id, $dir_info[0]["id"],
          $db, $params["login_user_id"], $params["action_info"], $logData);

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
        $data['dir_name'] = $params['dir_name'];
        $data['dir_path'] = "/";
        $data['dir_version'] = $this->newId();
        $data['dir_fid'] = $dirId;
        $data['action_user_id'] = $params['login_user_id'];
        $data['action_time'] = Date("Y-m-d H:i:s");
        $data['is_del'] = 0;
        $data['parent_dir_id'] = $params['parent_dir_id'];
        $data['action_info'] = $params['action_info'];

        $dir_info_sql = "insert into t_dir_info
                    (id,dir_name,dir_path,dir_version,dir_fid,action_user_id,action_time,
                    is_del,parent_dir_id,action_info) 
                    values ('%s','%s','%s','%s','%s','%s','%s','%d','%s','%s')";
        //判断是否存在上级目录，不存在就设置root为目录
        $is_parent_id = $db->query("select count(*) from t_dir_info where id = '%s' AND is_del = 0",
          $params['parent_dir_id']);
        if (!$params['parent_dir_id'] || !$is_parent_id[0]["count(*)"]) {
          $rootDir = $db->query('select id from t_dir_info where parent_dir_id is null');
          $data['parent_dir_id'] = $rootDir[0]['id'];
        } else {
          //验证单文件权限

          $data["file_id"] = $params["parent_dir_id"];
          if (!$permissionService->hasPermission($data, FIdConst::WJGL_ADD_DIR)) {
            $logService->deleteLog($params["log_id"]);
            $rs["msg"] = "你没有在此处创建文件夹的权限";
            return $rs;
          }
        }

        //构建路径
        $data["dir_path"] = $this->getFullPath($data['parent_dir_id'], $db) . $data["dir_version"] . "\\";
//        mkdir($data["dirPath"]);

        //验证该路径下是否存在相同文件夹
        $is_dirName = $db->query("select count(*) from t_dir_info
        where parent_dir_id = '%s' AND is_del = 0 AND dir_name in ('%s')", $data["parent_dir_id"], $data["dir_name"]);
        if ($is_dirName[0]["count(*)"]) {
          $db->rollback();
          $db->execute("delete from t_dir where id = '%s'", $dirId);
          $logService->deleteLog($params["log_id"]);
          $rs["msg"] = "文件夹已存在";
          return $rs;
        }
        //插入数据
        $mkinfo = $db->execute($dir_info_sql, $data);

        if ($mkinfo) {
          //增加日志
          $logData["log_id"] = $params["log_id"];
          $logData["action_type"] = "insert";
          $logData["file_type"] = "dir";
          $logData["file_id"] = $data["id"];
          $logService->addLogAction($logData);
          $db->commit();
          //上传后给自己设置权限
          $role_id = $db->query("select role_id from t_role_user where user_id = '%s'", $params["login_user_id"])[0];
          $permissionDara["file_id"] = $data["id"];
          $permissionDara["role_id"] = $role_id['role_id'];
          $permissionDara["checked"] = true;
          $permissionService->setFileCRUDPermission($permissionDara, "dir");

          $rs["success"] = true;
          $rs["msg"] = "操作成功";
          return $rs;
        } else {
          $db->rollback();
          $logService->deleteLog($params["log_id"]);
          $rs["msg"] = "操作失败";
          return $rs;
        }
      }
    }
    $logService->deleteLog($params["log_id"]);
    $rs["msg"] = "权限不足";
    return $rs;
  }


  /**
   * 移动文件
   * @param $params
   * @return mixed
   */
  public function moveFiles($params)
  {
    $db = $this->db;
    $rs["success"] = false;
    $rs["msg"] = "";
    $us = new UserService();
    $logService = new FileManagerlogService();
    $permissionService = new FileManagerPermissionService();
    if (!$us->hasPermission(FIdConst::WJGL_MOVE_FILE)) {
      $rs["msg"] = "权限不足";
      $logService->deleteLog($params["log_id"]);
      return $rs;
    }

    //判断要移动到的是否是一个文件夹
    $is_dir = $db->query("select count(*) from t_dir_info where id = '%s' and is_del = 0", $params["dir_id"]);
    if (!$is_dir[0]["count(*)"]) {
      $rs["msg"] = "不能移动到文件中";
      $logService->deleteLog($params["log_id"]);
      return $rs;
    }

    $is_t_dir = $db->query("select count(*) from t_dir where id = '%s'", $params["mid"]);
    if ($is_t_dir[0]["count(*)"]) {//被移动的是文件夹
      //找到最新版本的该文件夹
      $dir_info = $db->query("select * from t_dir_info where dir_fid = '%s' and is_del = 0", $params["mid"])[0];
      //验证单文件权限
      $data["file_id"] = $dir_info["id"];
      if (!$permissionService->hasPermission($data, FIdConst::WJGL_MOVE_DIR)) {
        $logService->deleteLog($params["log_id"]);
        $rs["msg"] = "你没有权限对这个文件夹进行移动";
        return $rs;
      }

      $data["id"] = $dir_info["id"];
      $data["dir_name"] = $dir_info["dir_name"];
      $data["parent_dir_id"] = $params["dir_id"];
      $data["login_user_id"] = $params["login_user_id"];
      $data["action_info"] = $dir_info["action_info"];
      $data["log_id"] = $params["log_id"];
      return $this->createDir($data);
    }
    $is_t_file = $db->query("select count(*) from t_file where id = '%s'", $params["mid"]);

    if ($is_t_file[0]["count(*)"]) {//被移动的是文件
      $dir_info = $db->query("select * from t_dir_info where id = '%s' and is_del = 0", $params["dir_id"])[0];
      $file_info = $db->query("select * from t_file_info where file_fid = '%s' and is_del = 0", $params["mid"])[0];
      //验证单文件权限
      $data["file_id"] = $file_info["id"];
      if (!$permissionService->hasPermission($data, FIdConst::WJGL_MOVE_FILE)) {
        $logService->deleteLog($params["log_id"]);
        $rs["msg"] = "你没有权限对这个文件进行移动";
        return $rs;
      }

      //验证目录是否存在相同文件
      $is_container = $db->query("select * from t_file_info
        where parent_dir_id = '%s' AND is_del = 0 AND file_name in ('%s') AND file_suffix in ('%s')",
        $dir_info["id"], $file_info["file_name"], $file_info["file_suffix"]);
      if (count($is_container)) {
        $logService->deleteLog($params["log_id"]);
        $rs["msg"] = "文件已存在";
        return $rs;
      }

      $db->startTrans();
      $db->execute("update t_file_info set is_del = 1000 where id = '%s'", $file_info["id"]);

      $logData["log_id"] = $params["log_id"];
      $logData["action_type"] = "delete";
      $logData["file_type"] = "file";
      $logData["file_id"] = $file_info["id"];
      $logService->addLogAction($logData);

      $insert_sql = "insert into t_file_info
    (id, file_name, file_path, file_size, file_suffix, parent_dir_id, file_version, file_fid,
    action_user_id, action_time, is_del, action_info) 
    values ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s')";

      $old_file_path = $file_info["file_path"];
      $old_file_version = $file_info["file_version"];
      $file_info["id"] = $this->newId();
      $file_info["file_path"] = $dir_info["dir_path"];
      $file_info["parent_dir_id"] = $dir_info["id"];
      $file_info["file_version"] = $this->newId();
      $file_info["action_user_id"] = $params["login_user_id"];
      $file_info["action_time"] = Date("Y-m-d H:i:s");
      $file_info["is_del"] = 0;
      $exinfo = $db->execute($insert_sql, $file_info);

      $logData["action_type"] = "insert";
      $logData["file_id"] = $file_info["id"];
      $logService->addLogAction($logData);

      if (!$exinfo) {
        $db->rollback();
        $rs["msg"] = "插入数据失败";
        $logService->deleteLog($params["log_id"]);
        return $rs;
      }
      $this->createTrueDirPath($dir_info["dir_path"]);
      copy($old_file_path . $old_file_version . "." . $file_info["file_suffix"],
        $dir_info["dir_path"] . $file_info["file_version"] . "." . $file_info["file_suffix"]);
      $db->commit();
      $rs["success"] = true;
      $rs["msg"] = "操作成功";
      return $rs;
    }
    $rs["msg"] = "数据错误";
    $logService->deleteLog($params["log_id"]);
    return $rs;
  }

  /**
   * 获取完整文件夹路径
   * @param $parentId
   * @param $db
   * @return string
   */
  private function getFullPath(&$parentId, $db)
  {
    $parentDir = $db->query("select dir_name,parent_dir_id,dir_version from t_dir_info
    where id = '%s' and is_del = 0", $parentId);
    $path = $parentDir[0]['dir_version'];
    $parentPath = "Uploads\\";
    if ($parentDir[0]["parent_dir_id"]) {
      $parentPath = $this->getFullPath($parentDir[0]["parent_dir_id"], $db);
    }

    $path = $parentPath . $path . "\\";
    return $path;
  }

  /**
   * 得到父级文件夹的名称
   * @param $params
   * @return string
   */
  public function getParentDirName($params)
  {
    $rs["success"] = false;
    $rs["msg"] = "";
    $rs["parentDirName"] = "";
    if (!$params["id"]) {
      return ($rs["msg"] = "加载错误，ID不存在");
    }
    $sql = "select dir_name from t_dir_info where id = '%s' and is_del = 0";
    $data = $this->db->query($sql, $params["id"]);

    $rs["success"] = true;
    $rs["parentDirName"] = $data[0]["dir_name"];
    return $rs;
  }

  /**
   * 删除文件夹
   * @param $params
   * @return mixed
   */
  public function deleteDir($params)
  {
    $rs["success"] = false;
    $rs['msg'] = "";
    $db = $this->db;
    $us = new UserService();
    $logService = new FileManagerlogService();
    if (!$us->hasPermission(FIdConst::WJGL_DEL_DIR)) {
      $logService->deleteLog($params["log_id"]);
      $rs["msg"] = "权限不足";
      return $rs;
    }
    //验证单文件权限
    $permissionService = new FileManagerPermissionService();
    $data["file_id"] = $params["id"];
    if (!$permissionService->hasPermission($data, FIdConst::WJGL_DEL_DIR)) {
      $logService->deleteLog($params["log_id"]);
      $rs["msg"] = "权限不足";
      return $rs;
    }

    $dir_info = $db->query("select * from t_dir_info where id = '%s' AND parent_dir_id is not null", $params["id"]);
    if (!count($dir_info)) {
      $logService->deleteLog($params["log_id"]);
      $rs["msg"] = "数据不存在";
      return $rs;
    }
    $db->execute("update t_dir_info set is_del = 1000 where id = '%s' ", $params["id"]);

    $logData["log_id"] = $params["log_id"];
    $logData["action_type"] = "delete";
    $logData["file_type"] = "dir";
    $logData["file_id"] = $params["id"];
    $logService->addLogAction($logData);

    $this->deleteDirChildren($dir_info[0]["id"], $db, $logData);
    $rs["success"] = true;
    $rs["msg"] = "操作成功";
    return $rs;
  }

  /**
   * 删除文件
   * @param $params
   * @return string
   */
  public function deleteFile($params)
  {
    $rs["success"] = false;
    $rs['msg'] = "";
    $us = new UserService();
    $db = $this->db;
    $logService = new FileManagerlogService();
    if (!$us->hasPermission(FIdConst::WJGL_DEL_FILE)) {
      $logService->deleteLog($params["log_id"]);
      $rs["msg"] = "权限不足";
      return $rs;
    }
    //验证单文件权限
    $permissionService = new FileManagerPermissionService();
    $data["file_id"] = $params["id"];
    if (!$permissionService->hasPermission($data, FIdConst::WJGL_DEL_FILE)) {
      $logService->deleteLog($params["log_id"]);
      $rs["msg"] = "权限不足";
      return $rs;
    }

    $file_info = $db->query("select * from t_file_info where id = '%s' AND is_del = 0", $params["id"]);
    if (!count($file_info)) {
      $rs["msg"] = "数据不存在";
      $logService->deleteLog($params["log_id"]);
      return $rs;
    }
    $db->execute("update t_file_info set is_del = 1000 where id = '%s' ", $params["id"]);

    $logData["log_id"] = $params["log_id"];
    $logData["action_type"] = "delete";
    $logData["file_type"] = "file";
    $logData["file_id"] = $params["id"];
    $logService->addLogAction($logData);

    unlink($file_info[0]["file_path"] . $file_info[0]["file_version"] . ".pdf");
    $rs["success"] = true;
    $rs["msg"] = "操作成功";
    return $rs;
  }

  /**
   * 文件上传失败时取消数据的插入
   * @param $params
   */
  public function cancelUpLoadFile($params)
  {
    $db = $this->db;
    $logService = new FileManagerlogService();
    $logService->deleteLog($params["log_id"]);
    $db->execute("delete from t_file_info where id = '%s' ", $params["id"]);
    $db->execute("delete from t_log_action where log_id = '%s'", $params["log_id"]);
  }

  /**
   * 删除文件夹中的数据
   * @param $parentId
   * @param $db
   */
  private function deleteDirChildren(&$parentId, $db, $logData)
  {
    $logService = new FileManagerlogService();
    //找到文件夹
    $dirs = $db->query("select * from t_dir_info where parent_dir_id = '%s' AND is_del = 0", $parentId);
    if (count($dirs)) {
      foreach ($dirs as $i => $v) {
        $db->execute("update t_file_info set is_del = 1000 where id = '%s'", $v["id"]);

        $logData["action_type"] = "delete";
        $logData["file_type"] = "dir";
        $logData["file_id"] = $v["id"];
        $logService->addLogAction($logData);

        $this->deleteDirChildren($v['id'], $db, $logData);
      }
    }
    //找到文件
    $files = $db->query("select * from t_file_info where parent_dir_id = '%s' AND is_del = 0", $parentId);
    if (count($files)) {
      foreach ($files as $i => $v) {
        $db->execute("update t_file_info set is_del = 1000 where id = '%s'", $v["id"]);
        $logData["action_type"] = "delete";
        $logData["file_type"] = "file";
        $logData["file_id"] = $v["id"];
        $logService->addLogAction($logData);
      }
    }

  }

  /**
   * 文件上传
   * @param $param
   * @param $rs
   */
  public function upLoadFile(&$param, &$rs)
  {
    $db = $this->db;
    $logService = new FileManagerlogService();
    $permissionService = new FileManagerPermissionService();
    //验证名称
    $file_info = $db->query("select * from t_file_info
                        where parent_dir_id = '%s' AND is_del = 0 AND file_name in ('%s') AND file_suffix in ('%s')",
      $param["parent_dir_id"], $param["file_name"], $param["file_suffix"]);
    if (count($file_info)) {//更新文件

      //验证单文件权限
      $data["file_id"] = $file_info[0]['id'];
      if (!$permissionService->hasPermission($data, FIdConst::WJGL_EDIT_FILE)) {
        $logService->deleteLog($param["log_id"]);
        $rs["msg"] = "更新文件权限不足";
        return;
      }
      $db->startTrans();

      $db->execute("update t_file_info set is_del = 1000 where id = '%s'", $file_info[0]["id"]);

      $logData["log_id"] = $param["log_id"];
      $logData["action_type"] = "delete";
      $logData["file_type"] = "file";
      $logData["file_id"] = $file_info[0]["id"];
      $logService->addLogAction($logData);

      $file_id = $this->newId();
      $file_info[0]["id"] = $file_id;
      $file_info[0]["file_version"] = $this->newId();
      $file_info[0]["action_info"] = $param["action_info"];
      $file_info[0]["action_time"] = Date("Y-m-d H:i:s");
      $file_info_sql = "insert into t_file_info
    (id, file_name, file_path, file_size, file_suffix, parent_dir_id, file_version, file_fid,
    action_user_id, action_time, is_del, action_info) 
    values ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s')";

      $is_commit = $db->execute($file_info_sql, $file_info[0]);
      if (!$is_commit) {
        $db->rollback();
        $rs["msg"] = "更新失败";
        $logService->deleteLog($param["log_id"]);
        return $rs;
      }
      $db->commit();
      $logData["action_type"] = "insert";
      $logData["file_id"] = $file_id;
      $logService->addLogAction($logData);
      $remarks = "更新了文件[" . $param["file_name"] . "." . $param["file_suffix"] . "]";
      $logService->editLogRemarksById($param["log_id"], $remarks);
      $param["data"] = $file_info[0];
      $rs["msg"] = "更新成功";
      $rs["success"] = true;
      return $rs;
    }

    $db->startTrans();
    //t_file表数据
    $t_file_id = $this->newId();
    $db->execute("insert into t_file values ('%s')", $t_file_id);

    $file_info_sql = "insert into t_file_info
    (id, file_name, file_path, file_size, file_suffix, parent_dir_id, file_version, file_fid,
    action_user_id, action_time, is_del, action_info) 
    values ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s')";


    $data["id"] = $this->newId();
    $data["file_name"] = $param["file_name"];
    $data["file_path"] = "/";
    $data["file_size"] = "0";
    $data["file_suffix"] = $param["file_suffix"];
    $data["parent_dir_id"] = $param["parent_dir_id"];
    $data["file_version"] = $this->newId();
    $data["file_fid"] = $t_file_id;
    $data["action_user_id"] = $param["login_user_id"];
    $data["action_time"] = Date("Y-m-d H:i:s");
    $data["is_del"] = 0;
    $data["action_info"] = $param["action_info"];

    //判断是否存在上级目录，不存在就设置root为目录
    $is_parent_id = $db->query("select count(*) from t_dir_info
    where id = '%s' AND is_del = 0", $param['parent_dir_id']);
    if (!$param['parent_dir_id'] || !$is_parent_id[0]["count(*)"]) {
      $db->rollback();
      $rs["msg"] = "目录不存在或选择的不是一个目录";
      $logService->deleteLog($param["log_id"]);
      return $rs;
    } else {
      //验证单文件权限
      $data["file_id"] = $param["parent_dir_id"];
      if (!$permissionService->hasPermission($data, FIdConst::WJGL_UP_FILE)) {
        $logService->deleteLog($param["log_id"]);
        $rs["msg"] = "权限不足";
        $db->rollback();
        return;
      }
    }

    $data["file_path"] = $this->getFullPath($data['parent_dir_id'], $db);

    $db->execute($file_info_sql, $data);
    $this->createTrueDirPath($data["file_path"]);

    $logData["log_id"] = $param["log_id"];
    $logData["action_type"] = "insert";
    $logData["file_type"] = "file";
    $logData["file_id"] = $data["id"];
    $logService->addLogAction($logData);


    $db->commit();

    //上传后给自己设置权限
    $role_id = $db->query("select role_id from t_role_user where user_id = '%s'", $param["login_user_id"])[0];
    $permissionDara["file_id"] = $data["id"];
    $permissionDara["role_id"] = $role_id['role_id'];
    $permissionDara["checked"] = true;
    $permissionService->setFileCRUDPermission($permissionDara, "file");

    $param["data"] = $data;
    $rs["msg"] = "上传成功";
    $rs["success"] = true;
  }

  /**
   * 编辑文件
   * @param $params
   * @param $info
   */
  public function editFile($params,$info){

  }

  /**
   * 文件上传成功后，为对应的数据设置文件大小
   * @param $id
   * @param $size
   */
  public function setFileSize($id, $size)
  {
    $db = $this->db;
    $sql = "update t_file_info set file_size = '%s' where id = '%s'";
    $db->execute($sql, $size, $id);
  }

  /**
   * 转换文件
   * @param $params
   * @return string
   */
  public function convertFile($params)
  {
    $db = $this->db;
    $rs["success"] = false;
    $rs['msg'] = "";
    $sql = "select * from t_file_info where id = '%s'";
    $suffixService = new SuffixConfigService();
    $officeType = $suffixService->getSuffixs('office');
    $imgType = $suffixService->getSuffixs('picture');
    $data = $db->query($sql, $params);
    if (!$data) {
      $rs["msg"] = "文件已不存在";
      return $rs;
    }
    //验证单文件权限
    $permissionService = new FileManagerPermissionService();
    $data["file_id"] = $params["id"];
    if (!$permissionService->hasPermission($data, FIdConst::WJGL_YL_FILE)) {
      $rs["msg"] = "权限不足";
      return $rs;
    }

    //图片不需要转换
    if (in_array(strtolower($data[0]["file_suffix"]), $imgType) ||
      $data[0]["file_suffix"] == "pdf") {
      $rs["msg"] = "操作成功";
      $rs["success"] = true;
      $rs["id"] = $data[0]["id"];
      $rs["file_suffix"] = $data[0]['file_suffix'];
      return $rs;
    }


    //为office格式
    if (in_array(strtolower($data[0]["file_suffix"]), $officeType)) {
      $path = $data[0]["file_path"] . $data[0]["file_version"] . "." . $data[0]["file_suffix"];
      $outpath = $data[0]["file_path"];
      if (empty($path)) {
        $rs['msg'] = "路径出错";
        return $rs;
      }
      if (file_exists($outpath . $data[0]["file_version"] . '.pdf')) {
        $rs["success"] = true;
        $rs['msg'] = "转换成功";
        $rs["id"] = $data[0]["id"];
        $rs["file_suffix"] = 'pdf';
        return $rs;
      }

      try {
        //openoffice
//        $p = C('JDK_PATH') . " -jar " . realpath(C("JODCONVERTER_PATH")) . " " .
//          $path . " " . $outpath . $data[0]["file_version"] . '.pdf 2>&1 ';

        //liberoffice
        $p = "soffice --headless --convert-to pdf " .
          realpath($path) . " --outdir " . $outpath;
        $log = "";
        $arr = [];
        exec($p, $log, $arr);
//        exec($p);
        if ($arr) {
          $rs['msg'] = "转换失败";
          return $rs;
        }

      } catch (Exception $e) {
        $rs['msg'] = "转换失败：" . $e->getMessage();
        return $rs;
      }

      $rs["success"] = true;
      $rs['msg'] = "转换成功";
      $rs["id"] = $data[0]["id"];
      $rs["file_suffix"] = "pdf";
      return $rs;
    }

    $rs["msg"] = "暂不支持格式";
    return $rs;
  }

  /**
   * 根据id查找文件信息
   * @param $params
   * @return mixed
   */
  public function getFileByInfoId($params)
  {
    $db = $this->db;
    $data = $db->query("select * from t_file_info where id = '%s'", $params["file_id"]);
    return $data[0];
  }

  /**
   * 得到文件或文件夹的路径
   * @param $params id数组
   * @return array  路径数组
   */
  public function getPathById($params)
  {
    $db = $this->db;
    $paths = array();
    $permissionService = new FileManagerPermissionService();
    foreach ($params as $v) {
      $dirpath = $db->query("select dir_path,dir_name from t_dir_info where id = '%s' and is_del = 0", $v);

      //验证单文件权限
      $data["file_id"] = $v;
      if (!$permissionService->hasPermission($data, FIdConst::WJGL_DOWN_FILE)) {
        continue;
      }

      if (count($dirpath)) {
        //获取显示路径
        $arr = explode("\\", $dirpath[0]['dir_path']);
        $showPath = $this->getShowPath($arr, $db);

        //得到用户所看到的路径
        array_push($paths, array("DirPath" => $showPath));

        $children_dir_id = $db->query("select id from t_dir_info where parent_dir_id = '%s' and is_del = 0", $v);
        $children_file_id = $db->query("select id from t_file_info where parent_dir_id = '%s' and is_del = 0", $v);
        $childrenpaths = array_merge($children_dir_id, $children_file_id);
        $patharr = $this->getPathById($childrenpaths);
        $paths = array_merge_recursive($paths, $patharr);
      } else {
        $fileinfo = $db->query("select * from t_file_info where id = '%s' and is_del = 0", $v);
        if (count($fileinfo)) {
          $arr = explode("\\", $fileinfo[0]["file_path"]);
          $showPath = $this->getShowPath($arr, $db);

          array_push($paths, array(
            "TruePath" => $fileinfo[0]["file_path"] . $fileinfo[0]["file_version"] . "." . $fileinfo[0]["file_suffix"],
            "ShowName" => $fileinfo[0]["file_name"] . "." . $fileinfo[0]["file_suffix"],
            "FilePath" => $showPath));
        }
      }
    }
    $root = $db->query("select dir_path from t_dir_info where parent_dir_id is null and is_del = 0")[0]["dir_path"];
    $paths["root"] = $root;
    //array_reduce($paths, 'array_merge', array());
    return $paths;
  }

  /**
   * 获取文件用于现实的真实路径
   * @param $arr
   * @param $db
   * @return string
   */
  public function getShowPath(&$arr, $db)
  {
    $path = "";
    foreach ($arr as $v) {
      if (!$v) continue;
      if (strpbrk($v, ".")) return $path;
      $dirname = $db->query("select dir_name from t_dir_info where dir_version = '%s'", $v)[0]["dir_name"];
      if ($dirname == '/') {
        $path = "/";
        continue;
      }
      $path = $path . $dirname . '/';
    }
    return $path;
  }


  /**
   * 创建真实路径
   * @param $path
   */
  private function createTrueDirPath($path)
  {
    $temp = "";
    $temppath = substr($path, 0, strlen($path) - 1);
    $dirNames = explode('\\', $temppath);
    foreach ($dirNames as $p) {
      if (!empty($p)) {
        $temp .= $p . "\\";
        mkdir($temp);
      }
    }
  }

}
