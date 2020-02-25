<?php


namespace Home\Service;


use Home\DAO\ExamineDAO;

class ExamineService extends PSIBaseExService
{
  public function loadFlow($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $params['uid'] = $this->getLoginUserId();
    $ed = new ExamineDAO($this->db());
    return $ed->loadFlow($params);
  }

  public function flowAdvance($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $params['uid'] = $this->getLoginUserId();
    $ed = new ExamineDAO($this->db());
    return $ed->flowAdvance($params);
  }

  public function getFileInfoByRunId($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $ed = new ExamineDAO($this->db());
    return $ed->getFileInfoByRunId($params);
  }

  public function passFlow($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $params['uid'] = $this->getLoginUserId();
    $params['uname'] = $this->getLoginUserName();
    $ed = new ExamineDAO($this->db());
    return $ed->passFlow($params);
  }

  public function passEnd($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $params['uid'] = $this->getLoginUserId();
    $params['uname'] = $this->getLoginUserName();
    $ed = new ExamineDAO($this->db());
    return $ed->passFlow($params, true);
  }

  public function fail($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $params['uid'] = $this->getLoginUserId();
    $params['uname'] = $this->getLoginUserName();
    $ed = new ExamineDAO($this->db());
    return $ed->fail($params);
  }

  public function back($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $params['uid'] = $this->getLoginUserId();
    $ed = new ExamineDAO($this->db());
    return $ed->back($params);
  }

}
