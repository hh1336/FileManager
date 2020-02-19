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

}
