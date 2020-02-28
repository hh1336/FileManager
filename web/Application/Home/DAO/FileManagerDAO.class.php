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
    $sql = "select di.dir_fid as id, di.id as id2,	di.dir_name,	di.dir_path, di.dir_version,
                di.action_user_id,	di.action_time,	di.parent_dir_id ,
                cu.name as create_user_name, di.create_time, di.action_info, u.name as user_name 
                from	t_dir_info di
                left join t_user u on di.action_user_id = u.id
                left join t_user cu on di.create_user_id = cu.id
                where	di.parent_dir_id = '%s'	and di.is_del = 0
                order by di.dir_name";

    $rootsql = "select di.dir_fid as id, di.id as id2,	di.dir_name,	di.dir_path,	di.dir_version,
                di.action_user_id,	di.action_time,	di.parent_dir_id,	di.action_info,	u.name as user_name,
                cu.name as create_user_name, di.create_time,	di.create_user_id,	di.create_time
                from	t_dir_info di
                left join t_user u on di.action_user_id = u.id
	              left join t_user cu on di.create_user_id = cu.id
                where	di.parent_dir_id is null	and di.is_del = 0 
                order by	di.dir_name";
    $root = $db->query($rootsql);

    $datalist = [];
    //是根目录
    if (empty($params["parent_dir_id"]) || $root[0]["id2"] == $params["parent_dir_id"]) {
      $params["parent_dir_id"] = $root[0]["id2"];
      $params["is_root"] = true;
    }
    //找当前目录
    $grand_dir = $db->query("select id,parent_dir_id from t_dir_info where id = '%s' and is_del = 0",
      $params["parent_dir_id"]);
    //找子目录
    $dirlist = $db->query($sql, $params["parent_dir_id"]);
    $file_sql = "select	fi.file_fid as id,	fi.id as id2,	fi.file_name,	fi.file_path,	fi.file_size,
	              fi.file_suffix,	fi.file_version,	fi.action_user_id,	fi.action_time,	fi.parent_dir_id,
	              cu.name as create_user_name, fi.create_time,	fi.action_info,	u.name as user_name,fi.file_code 
                from	t_file_info fi
	              left join t_user u on fi.action_user_id = u.id 
	              left join t_user cu on fi.create_user_id = cu.id
                where	fi.parent_dir_id = '%s' and fi.is_del = 0 
                order by	fi.file_name";
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
      $result[$i]["createUserName"] = $v["create_user_name"];
      $result[$i]["createTime"] = date("Y-m-d H:i:s", strtotime($v["create_time"]));
      $result[$i]["fileCode"] = $v["file_code"] ?? "";
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
   * 查询文件或文件夹
   * @param $params
   * @return array
   */
  public function queryFiles($params)
  {
    $db = $this->db;

    $sql = "";
    if ($params["type"]) {
      $sql = "select	info.*,	cu.name as create_user_name,	tu.name as user_name 
        from	(( select * from t_file_info where is_del = 0 and file_name like ( '%s' ) ) as info,	
        (select	fp.* from	t_file_permission as fp
	      left join t_role_user as ru on ru.role_id = fp.role_id
	      left join t_permission as p on fp.permission_fid = p.fid 
        where	ru.user_id = '%s' 	and p.fid = '%s' 	) as tfp 	)
	      left join t_dir_info as tdi on info.parent_dir_id = tdi.id
	      left join t_user as tu on info.action_user_id = tu.id 
	      left join t_user as cu on cu.id = info.create_user_id
        where	tfp.file_id = tdi.dir_fid";
    } else {
      $sql = "select info.*,cu.name as create_user_name,tu.name as user_name 
        from	((select * from t_dir_info where is_del = 0 and dir_name like ('%s')) as info,
	      (select	fp.* from	t_file_permission as fp
	      left join t_role_user as ru on ru.role_id = fp.role_id
	      left join t_permission as p on fp.permission_fid = p.fid 
	      where	ru.user_id = '%s' and p.fid = '%s' ) as tfp )
	      left join t_user as tu on info.action_user_id = tu.id
	      left join t_user as cu on cu.id = info.create_user_id
        where	tfp.file_id = info.dir_fid";
    }
    $query_data = $db->query($sql,
      "%" . $params["name"] . "%", $params["login_user_id"],
      FIdConst::WJGL_INTO_DIR);
    $rs_data = [];
    foreach ($query_data as $i => $v) {
      $rs_data[$i]["id"] = $v["dir_fid"] ?? $v["file_fid"];
      $rs_data[$i]["id2"] = $v["id"];
      $rs_data[$i]["Name"] = $v["dir_name"] ?? ($v["file_name"] . "." . $v["file_suffix"]);
      $rs_data[$i]["Version"] = $v["dir_version"] ?? $v["file_version"];
      $rs_data[$i]["actionUserId"] = $v["action_user_id"];
      $rs_data[$i]["actionTime"] = date("Y-m-d H:i:s", strtotime($v["action_time"]));
      $rs_data[$i]["userName"] = $v["user_name"];
      $rs_data[$i]["parentDirID"] = $v["parent_dir_id"];
      $rs_data[$i]["actionInfo"] = $v["action_info"];
      $rs_data[$i]["createUserName"] = $v["create_user_name"];
      $rs_data[$i]["createTime"] = $v["create_time"];
      //$rs_data[$i]["path"] = isset($v["dir_path"]) ?
      $arr = explode("\\", $v["dir_path"] ?? $v["file_path"]);
      $path = $this->getShowPath($arr, $db);
      $rs_data[$i]['path'] = $path;
      $rs_data[$i]['fileCode'] = $v['file_code'] ?? "";

      //加载文件
      $rs_data[$i]["fileSize"] = $v["file_size"] ?? "";
      $rs_data[$i]["fileSuffix"] = !isset($v["dir_name"]) ? $v["file_suffix"] : "dir";
      $rs_data[$i]['children'] = array();
      $rs_data[$i]['leaf'] = true;
      $rs_data[$i]['expanded'] = true;//不展开
      $rs_data[$i]["iconCls"] = isset($v["dir_name"]) ? $this->getCss("dir") : $this->getCss($v["file_suffix"]);
      $rs_data[$i]["checked"] = false;
    }
    return $rs_data;

  }

  /**
   * 以树状加载文件夹
   * @param null $parentId
   * @return array|mixed
   */
  public function loadTree($parentId = null)
  {
    $db = $this->db;

    $sql = "select	di.dir_fid as id,	di.id as id2,	di.dir_name,	di.dir_path,	di.dir_version,
	          di.action_user_id,	di.action_time,	di.parent_dir_id,	di.action_info,	cu.name as create_user_name,
	          di.create_time,	u.name as user_name 
	          from	t_dir_info di
	          left join t_user u on di.action_user_id = u.id
	          left join t_user cu on cu.id = di.create_user_id";
    if (!$parentId) {
      $sql .= " where	di.parent_dir_id is null 	and di.is_del = 0";
    } else {
      $sql .= " where	di.parent_dir_id = '%s'	and di.is_del = 0";
    }
    $sql .= " order by di.dir_name";

    $fileslist1 = [];
    if (!$parentId) {
      $fileslist1 = $db->query($sql);
    } else {
      $fileslist1 = $db->query($sql, $parentId);
    }

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
      $result[$i]["createUserName"] = $list1["create_user_name"];
      $result[$i]["createTime"] = $list1["create_time"];

      $fileslist2 = $this->loadTree($list1["id2"]);
      $result[$i]['children'] = $fileslist2;
      $result[$i]['leaf'] = count($fileslist2) == 0;
      $result[$i]['expanded'] = false;
      $result[$i]["iconCls"] = "PSI-FileManager-Dir";
    }

    if (!$parentId) {
      return $result[0];
    } else {
      $permissionService = new FileManagerPermissionService();
      $data["file_id"] = $parentId;
      if (!$permissionService->hasPermission($data, FIdConst::WJGL_INTO_DIR)) {
        return array();
      }
      return $result;
    }
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

        //操作t_dir_info表
        //修改为已删除
        $db->execute("update t_dir_info set is_del = 1000 where id = '%s'", $v['id']);

        $logData["action_type"] = "delete";
        $logData["file_type"] = "dir";
        $logData["file_id"] = $v['id'];
        $logService->addLogAction($logData);

        //构造t_dir_info数据
        $insert_dir_sql = "insert into t_dir_info ( id, dir_name, dir_path, dir_version, dir_fid,
                action_user_id, action_time, is_del, parent_dir_id, action_info,create_user_id,create_time )
                values	('%s','%s','%s','%s','%s','%s','%s','%d','%s','%s','%s','%s');";
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
        $insert_file_sql = "insert into t_file_info (
        id, file_name, file_path,  file_size, file_suffix, parent_dir_id,create_user_id,create_time,
         file_version, file_fid, action_user_id, action_time, is_del, action_info,file_code)
        values	('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s');";
        $v["id"] = $this->newId();
        $v["parent_dir_id"] = $newID;
        $v["file_version"] = $this->newId();
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
    $db = $this->db;
    $logService = new FileManagerlogService();
    $permissionService = new FileManagerPermissionService();
    if (!$params['dir_name']) {
      $logService->deleteLog($params["log_id"]);
      $msg = "文件名为空";
      return $this->failAction($msg);
    }
    if ($params["id"]) {//编辑
      //判断这个文件夹是否存在
      $dir_info = $db->query("select * from t_dir_info where id = '%s' and is_del = 0", $params["id"]);

      //保存旧id，用来修改子文件夹
      $old_dir_id = $dir_info[0]["id"];

      if (!count($dir_info)) {
        $logService->deleteLog($params["log_id"]);
        $msg = "文件夹已不存在";
        return $this->failAction($msg);
      }
      if (!$dir_info[0]["parent_dir_id"]) {
        $logService->deleteLog($params["log_id"]);
        $msg = "不能编辑根目录";
        return $this->failAction($msg);
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
      $insert_dir_sql = "insert into t_dir_info ( id, dir_name, dir_path,  dir_version, dir_fid,
                  action_user_id, action_time, is_del,create_user_id,create_time, parent_dir_id, action_info )
                  values	('%s','%s','%s','%s','%s','%s','%s','%d','%s','%s','%s','%s');";

      $dir_info[0]["id"] = $this->newId();
      $dir_info[0]["dir_name"] = $params["dir_name"];
      $dir_info[0]["dir_version"] = $this->newId();
      $dir_info[0]["parent_dir_id"] = $params["parent_dir_id"];
      $dir_info[0]["action_user_id"] = $params["login_user_id"];
      $dir_info[0]["action_time"] = Date("Y-m-d H:i:s");
      $dir_info[0]["action_info"] = $params["action_info"];

      //验证该路径下是否存在相同文件夹
      $is_dirName = $db->query("select count(*) from t_dir_info
            where parent_dir_id = '%s' and is_del = 0 and dir_name in ('%s')",
        $dir_info[0]["parent_dir_id"], $dir_info[0]["dir_name"]);
      if ($is_dirName[0]["count(*)"]) {
        $db->rollback();
        $logService->deleteLog($params["log_id"]);
        $msg = "文件夹已存在";
        return $this->failAction($msg);
      }

      $logData["action_type"] = "insert";
      $logData["file_id"] = $dir_info[0]['id'];
      $logService->addLogAction($logData);

      //构建目录
      $dir_info[0]["dir_path"] = $this->getFullPath($dir_info[0]["parent_dir_id"], $db) .
        $dir_info[0]["dir_version"] . "\\";

      $edmkinfo = $db->execute($insert_dir_sql, $dir_info[0]);
      if (!$edmkinfo) {
        $db->rollback();
        $logService->deleteLog($params["log_id"]);
        $msg = "操作失败";
        return $this->failAction($msg);
      }

      //迁移所有文件和文件夹到当前版本
      $childrenDir = $db->query("select * from t_dir_info where parent_dir_id = '%s' and is_del = 0", $old_dir_id);
      $this->moveChildrenVersion($childrenDir, $old_dir_id, $dir_info[0]["id"],
        $db, $params["login_user_id"], $params["action_info"], $logData);

      $db->commit();
      $msg = "操作成功";
      return $this->successAction($msg);

    } else {
      //开始事务
      $db->startTrans();
      //构造数据
      $data['id'] = $this->newId();
      $data['dir_name'] = $params['dir_name'];
      $data['dir_path'] = "/";
      $data['dir_version'] = $this->newId();
      $data['dir_fid'] = $this->newId();;
      $data['action_user_id'] = $params['login_user_id'];
      $data['action_time'] = Date("Y-m-d H:i:s");
      $data['is_del'] = 0;
      $data['parent_dir_id'] = $params['parent_dir_id'];
      $data['action_info'] = $params['action_info'];
      $data["create_user_id"] = $params['login_user_id'];
      $data["create_time"] = Date("Y-m-d H:i:s");

      $dir_info_sql = "insert into t_dir_info
                    (id,dir_name,dir_path,dir_version,dir_fid,action_user_id,action_time,
                    is_del,parent_dir_id,action_info,create_user_id,create_time) 
                    values ('%s','%s','%s','%s','%s','%s','%s','%d','%s','%s','%s','%s')";
      //判断是否存在上级目录，不存在就设置root为目录
      $is_parent_id = $db->query("select count(*) from t_dir_info where id = '%s' and is_del = 0",
        $params['parent_dir_id']);
      if (!$params['parent_dir_id'] || !$is_parent_id[0]["count(*)"]) {
        $rootDir = $db->query('select id from t_dir_info where parent_dir_id is null');
        $data['parent_dir_id'] = $rootDir[0]['id'];
      } else {
        //验证单文件权限
        $data["file_id"] = $params["parent_dir_id"];
        if (!$permissionService->hasPermission($data, FIdConst::WJGL_ADD_DIR)) {
          $logService->deleteLog($params["log_id"]);
          $msg = "你没有在此处创建文件夹的权限";
          return $this->failAction($msg);
        }
      }
      //验证该路径下是否存在相同文件夹
      $is_dirName = $db->query("select count(*) from t_dir_info
        where parent_dir_id = '%s' and is_del = 0 and dir_name in ('%s')", $data["parent_dir_id"], $data["dir_name"]);
      if ($is_dirName[0]["count(*)"]) {
        $db->rollback();
        $logService->deleteLog($params["log_id"]);
        $msg = "文件夹已存在";
        return $this->failAction($msg);
      }

      //构建路径
      $data["dir_path"] = $this->getFullPath($data['parent_dir_id'], $db) . $data["dir_version"] . "\\";

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
        $permissionDara["file_id"] = $data["id"];
        $permissionDara["checked"] = true;
        $permissionDara["login_d"] = $params['login_user_id'];
        $permissionService->setFileCRUDPermission($permissionDara, "dir");
        $msg = "操作成功";
        return $this->successAction($msg);
      } else {
        $db->rollback();
        $logService->deleteLog($params["log_id"]);
        $msg = "操作失败";
        return $this->failAction($msg);
      }

    }
    $logService->deleteLog($params["log_id"]);
    return $this->notPermission();
  }

  /**
   * 移动文件
   * @param $params
   * @return mixed
   */
  public function moveFiles($params)
  {
    $db = $this->db;
    $us = new UserService();
    $logService = new FileManagerlogService();
    $permissionService = new FileManagerPermissionService();
    if (!$us->hasPermission(FIdConst::WJGL_MOVE_FILE)) {
      $logService->deleteLog($params["log_id"]);
      return $this->notPermission();
    }

    //判断要移动到的是否是一个文件夹
    $is_dir = $db->query("select count(*) from t_dir_info where id = '%s' and is_del = 0", $params["dir_id"]);
    if (!$is_dir[0]["count(*)"]) {
      $logService->deleteLog($params["log_id"]);
      $msg = "不能移动到文件中";
      return $this->failAction($msg);
    }

    $data["file_id"] = $params["dir_id"];
    if (!$permissionService->hasPermission($data, FIdConst::WJGL_MOVE_DIR)) {
      $logService->deleteLog($params["log_id"]);
      $msg = "你没有权限将内容移动到这个文件夹";
      return $this->failAction($msg);
    }

    $dir_info = $db->query("select * from t_dir_info where dir_fid = '%s' and is_del = 0", $params["mid"]);
    if (count($dir_info)) {//被移动的是文件夹
      //验证单文件权限
      $data["file_id"] = $dir_info[0]["id"];
      if (!$permissionService->hasPermission($data, FIdConst::WJGL_MOVE_DIR)) {
        $logService->deleteLog($params["log_id"]);
        $msg = "你没有权限对[" . $dir_info[0]["file_name"] . "]这个文件进行移动的权限。";
        return $this->failAction($msg);
      }

      $data["id"] = $dir_info[0]["id"];
      $data["dir_name"] = $dir_info[0]["dir_name"];
      $data["parent_dir_id"] = $params["dir_id"];
      $data["login_user_id"] = $params["login_user_id"];
      $data["action_info"] = $dir_info[0]["action_info"];
      $data["log_id"] = $params["log_id"];
      return $this->createDir($data);
    }

    $file_info = $db->query("select * from t_file_info where file_fid = '%s' and is_del = 0", $params["mid"]);
    if (count($file_info)) {//被移动的是文件
      $dir_info = $db->query("select * from t_dir_info where id = '%s' and is_del = 0", $params["dir_id"])[0];
      //验证单文件权限
      $data["file_id"] = $file_info[0]["id"];
      if (!$permissionService->hasPermission($data, FIdConst::WJGL_MOVE_FILE)) {
        $logService->deleteLog($params["log_id"]);
        $msg = "你没有权限对[" . $file_info[0]["file_name"] . "]这个文件进行移动的权限。";
        return $this->failAction($msg);
      }

      //验证目录是否存在相同文件
      $is_container = $db->query("select * from t_file_info
        where parent_dir_id = '%s' and is_del = 0 and file_name in ('%s') and file_suffix in ('%s')",
        $dir_info["id"], $file_info[0]["file_name"], $file_info[0]["file_suffix"]);
      if (count($is_container)) {
        $logService->deleteLog($params["log_id"]);
        $msg = "文件夹[" . $dir_info["dir_name"] . "]中，
        已存在名为[" . $file_info[0]["file_name"] . "]的文件";
        return $this->failAction($msg);
      }

      $db->startTrans();
      $db->execute("update t_file_info set is_del = 1000 where id = '%s'", $file_info[0]["id"]);

      $logData["log_id"] = $params["log_id"];
      $logData["action_type"] = "delete";
      $logData["file_type"] = "file";
      $logData["file_id"] = $file_info[0]["id"];
      $logService->addLogAction($logData);

      $insert_sql = "insert into t_file_info
    (id, file_name, file_path, file_size, file_suffix, parent_dir_id, file_version, file_fid,
    action_user_id, action_time, is_del, create_user_id,create_time, action_info,file_code) 
    values ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s','%s','%s')";

      $old_file_path = $file_info[0]["file_path"];
      $old_file_version = $file_info[0]["file_version"];
      $file_info[0]["id"] = $this->newId();
      $file_info[0]["file_path"] = $dir_info["dir_path"];
      $file_info[0]["parent_dir_id"] = $dir_info["id"];
      $file_info[0]["file_version"] = $this->newId();
      $file_info[0]["action_user_id"] = $params["login_user_id"];
      $file_info[0]["action_time"] = Date("Y-m-d H:i:s");
      $file_info[0]["is_del"] = 0;
      $exinfo = $db->execute($insert_sql, $file_info[0]);

      $logData["action_type"] = "insert";
      $logData["file_id"] = $file_info[0]["id"];
      $logService->addLogAction($logData);

      if (!$exinfo) {
        $db->rollback();
        $msg = "插入数据失败";
        $logService->deleteLog($params["log_id"]);
        return $this->failAction($msg);
      }
      $this->createTrueDirPath($dir_info["dir_path"]);
      copy($old_file_path . $old_file_version . "." . $file_info[0]["file_suffix"],
        $dir_info["dir_path"] . $file_info[0]["file_version"] . "." . $file_info[0]["file_suffix"]);
      $db->commit();
      $msg = "操作成功";
      return $this->successAction($msg);
    }
    $msg = "数据错误";
    $logService->deleteLog($params["log_id"]);
    return $this->failAction($msg);
  }

  /**
   * 获取完整文件夹路径
   * @param $parentId
   * @param $db
   * @return string
   */
  private function getFullPath($parentId, &$db)
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
    $rs["parentDirName"] = "";
    if (!$params["id"]) {
      $msg = "加载错误，ID不存在";
      return $this->failAction($msg);
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
    $db = $this->db;
    $logService = new FileManagerlogService();
    //验证单文件权限
    $dir_info = $db->query("select * from t_dir_info where id = '%s' and parent_dir_id is not null", $params["id"]);
    if (!count($dir_info)) {
      $logService->deleteLog($params["log_id"]);
      $msg = "数据不存在";
      return $this->failAction($msg);
    }
    $db->execute("update t_dir_info set is_del = 1000 where id = '%s' ", $params["id"]);

    $logData["log_id"] = $params["log_id"];
    $logData["action_type"] = "delete";
    $logData["file_type"] = "dir";
    $logData["file_id"] = $params["id"];
    $logService->addLogAction($logData);

    $this->deleteDirChildren($dir_info[0]["id"], $db, $logData);
    $msg = "操作成功";
    return $this->successAction($msg);
  }

  /**
   * 删除文件
   * @param $params
   * @return string
   */
  public function deleteFile($params)
  {
    $db = $this->db;
    $logService = new FileManagerlogService();

    $file_info = $db->query("select * from t_file_info where id = '%s' and is_del = 0", $params["id"]);
    if (!count($file_info)) {
      $msg = "数据不存在";
      $logService->deleteLog($params["log_id"]);
      return $this->failAction($msg);
    }
    $db->execute("update t_file_info set is_del = 1000 where id = '%s' ", $params["id"]);

    $logData["log_id"] = $params["log_id"];
    $logData["action_type"] = "delete";
    $logData["file_type"] = "file";
    $logData["file_id"] = $params["id"];
    $logService->addLogAction($logData);

    unlink($file_info[0]["file_path"] . $file_info[0]["file_version"] . ".pdf");
    $msg = "操作成功";
    return $this->successAction($msg);
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
//    delete from t_file_info where id = '%s';
    $sql = "delete from t_log_action where log_id = '%s'";
    $db->execute($sql, $params["log_id"]);
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
    $dirs = $db->query("select * from t_dir_info where parent_dir_id = '%s' and is_del = 0", $parentId);
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
    $files = $db->query("select * from t_file_info where parent_dir_id = '%s' and is_del = 0", $parentId);
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
   * 上传文件
   * @param $param
   * @return mixed
   */
  public function upLoadFile($param)
  {
    $db = $this->db;
    $logService = new FileManagerlogService();
    $permissionService = new FileManagerPermissionService();
    //验证名称
    $file_info = $db->query("select * from t_file_info
    where parent_dir_id = '%s' and is_del = 0 and file_name in ('%s') and file_suffix in ('%s')",
      $param["parent_dir_id"], $param["name"], $param["suffix"]);
    $db->startTrans();
    if (count($file_info)) {//更新文件

      //验证单文件权限
      $data["file_id"] = $file_info[0]['id'];
      if (!$permissionService->hasPermission($data, FIdConst::WJGL_EDIT_FILE)) {
        $logService->deleteLog($param["log_id"]);
        return $this->notPermission();
      }

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
    action_user_id, action_time, is_del,create_user_id,create_time, action_info,file_code) 
    values ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s','%s','%s')";

      $is_commit = $db->execute($file_info_sql, $file_info[0]);
      if (!$is_commit) {
        $db->rollback();
        $msg = "更新失败";
        $logService->deleteLog($param["log_id"]);
        return $this->failAction($msg);
      }
      $db->commit();
      $logData["action_type"] = "insert";
      $logData["file_id"] = $file_id;
      $logService->addLogAction($logData);
      $remarks = "更新了文件[" . $param["name"] . "." . $param["suffix"] . "]";
      $logService->editLogRemarksById($param["log_id"], $remarks);
      $param["data"] = $file_info[0];
      $msg = "更新成功";
      return $this->successAction($msg);
    }

    $file_info_sql = "insert into t_file_info
    (id, file_name, file_path, file_size, file_suffix, parent_dir_id, file_version, file_fid,
    action_user_id, action_time, is_del,create_user_id,create_time, action_info,file_code) 
    values ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s','%s','%s')";

    $data["id"] = $this->newId();
    $data["file_name"] = $param["name"];
    $data["file_path"] = "/";
    $data["file_size"] = $param['size'];
    $data["file_suffix"] = $param["suffix"];
    $data["parent_dir_id"] = $param["parent_dir_id"];
    $data["file_version"] = $this->newId();
    $data["file_fid"] = $this->newId();
    $data["action_user_id"] = $param["login_user_id"];
    $data["action_time"] = Date("Y-m-d H:i:s");
    $data["is_del"] = 0;
    $data["create_user_id"] = $param["login_user_id"];
    $data['create_time'] = Date("Y-m-d H:i:s");
    $data["action_info"] = $param["action_info"];
    $data["file_code"] = $param["file_code"];

    //判断是否存在上级目录，不存在就设置root为目录
    $is_parent_id = $db->query("select count(*) from t_dir_info
    where id = '%s' AND is_del = 0", $param['parent_dir_id']);
    if (!$param['parent_dir_id'] || !$is_parent_id[0]["count(*)"]) {
      $db->rollback();
      $msg = "目录不存在或选择的不是一个目录";
      $logService->deleteLog($param["log_id"]);
      return $this->failAction($msg);
    } else {
      //验证单文件权限
      $data["file_id"] = $param["parent_dir_id"];
      if (!$permissionService->hasPermission($data, FIdConst::WJGL_UP_FILE)) {
        $logService->deleteLog($param["log_id"]);
        $db->rollback();
        return $this->notPermission();
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
    $permissionDara["file_id"] = $data["id"];
    $permissionDara["checked"] = true;
    $permissionDara['login_id'] = $param['login_user_id'];
    $permissionService->setFileCRUDPermission($permissionDara, "file");

    $param["data"] = $data;
    $param['id'] = $data["id"];
    $msg = "上传成功";
    copy($param['path'], $data['file_path'] . $data['file_version'] . "." . $data['file_suffix']);
    unlink($param['path']);
    $this->convertFile($param);
    return $this->successAction($msg);
  }

  /**
   * 编辑文件夹
   * @param $params
   * @param $info
   * @return mixed
   */
  public function editFile($params)
  {
    $db = $this->db;
    $fmps = new FileManagerPermissionService();
    $logService = new FileManagerlogService();
    $file_info = $db->query("select * from t_file_info 
            where id = '%s' and is_del = 0", $params["id"]);
    if (!count($file_info)) {
      $msg = "找不到数据";
      return $this->failAction($msg);
    }
    $pdata["file_id"] = $params["id"];
    if (!$fmps->hasPermission($pdata, FIdConst::WJGL_EDIT_FILE)) {
      return $this->notPermission();
    }

    $db->startTrans();
    $new_id = $this->newId();
    $old_version = $file_info[0]["file_version"];
    $db->execute("update t_file_info set is_del = 1000 where id = '%s'", $params["id"]);

    $log_data["action_type"] = "delete";
    $log_data["file_type"] = "file";
    $log_data["file_id"] = $params["id"];
    $log_data["log_id"] = $params["log_id"];
    $logService->addLogAction($log_data);

    $insert_sql = "insert into t_file_info
        (id, file_name, file_path, file_size, file_suffix, parent_dir_id,file_version,
         file_fid, action_user_id, action_time, is_del,create_user_id,create_time, action_info,file_code) 
        values ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s','%s','%s','%s')";

    if (empty($params["path"])) {//没有上传新的文件

      $file_info[0]["id"] = $new_id;
      $file_info[0]["file_version"] = $this->newId();
      $file_info[0]["action_time"] = Date("Y-m-d H:i:s");
      $file_info[0]["action_user_id"] = $params["login_user_id"];
      $file_info[0]["action_info"] = $params["action_info"];
      $file_info[0]["file_code"] = $params['file_code'];
      $rs_info = $db->execute($insert_sql, $file_info[0]);
      if (!$rs_info) {
        $db->rollback();
        $logService->deleteLog($params["log_id"]);
        $msg = "数据插入失败";
        return $this->failAction($msg);
      }

      copy($params['path'], $file_info[0]["file_path"] . $file_info[0]["file_version"] . "." . $params['ext']);

    } else {//上传了新的文件

      //验证名称
      $is_container = $db->query("select * from t_file_info
            where parent_dir_id = '%s' and is_del = 0 and file_name in ('%s') and file_suffix in ('%s')",
        $file_info[0]["parent_dir_id"], $params["name"], $params["suffix"]);
      if (count($is_container)) {
        $db->rollback();
        $logService->deleteLog($params["log_id"]);
        unlink($params['path']);
        $msg = "上传的文件与其他文件发生冲突";
        return $this->failAction($msg);
      }

      $file_info[0]["id"] = $new_id;
      $file_info[0]["file_name"] = $params["name"];
      $file_info[0]["file_size"] = $params["size"];
      $file_info[0]["file_suffix"] = $params["suffix"];
      $file_info[0]["file_version"] = $this->newId();
      $file_info[0]["action_time"] = Date("Y-m-d H:i:s");
      $file_info[0]["action_user_id"] = $params["login_user_id"];
      $file_info[0]["action_info"] = $params["action_info"];
      $file_info[0]['file_code'] = $params['file_code'];

      $rs_info = $db->execute($insert_sql, $file_info[0]);
      if (!$rs_info) {
        $db->rollback();
        $logService->deleteLog($params["log_id"]);
        $msg = "数据插入失败";
        return $this->failAction($msg);
      }

      copy($params["path"],
        $file_info[0]["file_path"] . $file_info[0]["file_version"] . "." . $params['suffix']);
      unlink($params["path"]);

      $convert_data["id"] = $file_info[0]["id"];
      $this->convertFile($convert_data);
    }

    $log_data["action_type"] = "insert";
    $log_data["file_id"] = $new_id;
    $logService->addLogAction($log_data);

    $msg = "修改成功";
    $db->commit();
    return $this->successAction($msg);
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
    $sql = "select * from t_file_info where id = '%s'";
    $suffixService = new SuffixConfigService();
    $officeType = $suffixService->getSuffixs('office');
    $imgType = $suffixService->getSuffixs('picture');
    $data = $db->query($sql, $params);
    if (!$data) {
      $msg = "文件已不存在";
      return $this->failAction($msg);
    }
    //验证单文件权限
    $permissionService = new FileManagerPermissionService();
    $data["file_id"] = $params["id"];
    if (!$permissionService->hasPermission($data, FIdConst::WJGL_YL_FILE)) {
      return $this->notPermission();
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
        $msg = "路径出错";
        return $this->failAction($msg);
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
          $msg = "转换失败";
          return $this->failAction($msg);
        }

      } catch (Exception $e) {
        $msg = "转换失败：" . $e->getMessage();
        return $this->failAction($msg);
      }

      $rs["success"] = true;
      $rs['msg'] = "转换成功";
      $rs["id"] = $data[0]["id"];
      $rs["file_suffix"] = "pdf";
      return $rs;
    }

    $msg = "暂不支持格式";
    return $this->failAction($msg);
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
