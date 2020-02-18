<?php

namespace Home\Service;

use Home\DAO\StartFlowDAO;

class StartFlowService extends PSIBaseExService
{
  public function loadRunFlow($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $params['uid'] = $this->getLoginUserId();
    $sfd = new StartFlowDAO($this->db());
    return $sfd->loadRunFlow($params);
  }

  public function loadCheckFlow()
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $params['uid'] = $this->getLoginUserId();
    $sfd = new StartFlowDAO($this->db());
    return $sfd->loadCheckFlow($params);
  }

  public function previewFile($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $suffixService = new SuffixConfigService();
    $office_ext = $suffixService->getSuffixs('office');
    $imgType = $suffixService->getSuffixs('picture');

    if (in_array(strtolower($params["ext"]), $office_ext) || $params['ext'] == 'pdf') {
      $sfd = new StartFlowDAO($this->db());
      return $sfd->previewFile($params);
    } elseif (in_array(strtolower($params["ext"]), $imgType)) {
      $rs['success'] = true;
      $rs['msg'] = "/" . $params['file_path'];
      return $rs;
    }
    return $this->failAction('该后缀文件无法预览');
  }

  public function saveFlow($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }


    $sfd = new StartFlowDAO($this->db());
    return $sfd->saveFlow($params);
  }

  public function startFlow($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $params['uid'] = $this->getLoginUserId();
    $params['uname'] = $this->getLoginUserName();
    $sfd = new StartFlowDAO($this->db());
    return $sfd->startFlow($params);
  }

}
