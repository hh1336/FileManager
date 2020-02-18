<?php

namespace Home\Service;

use Home\DAO\ReceiveFlowDAO;

class ReceiveFlowService extends PSIBaseExService
{
  public function loadData($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $params['uid'] = $this->getLoginUserId();
    $rfd = new ReceiveFlowDAO($this->db());
    return $rfd->loadData($params);
  }

  public function receive($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $params['uid'] = $this->getLoginUserId();
    $params['uname'] = $this->getLoginUserName();
    $rfd = new ReceiveFlowDAO($this->db());
    return $rfd->receive($params);
  }
}
