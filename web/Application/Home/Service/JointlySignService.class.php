<?php


namespace Home\Service;


use Home\DAO\JointlySignDAO;

class JointlySignService extends PSIBaseExService
{
  public function loadData($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $params['uid'] = $this->getLoginUserId();
    $jsd = new JointlySignDAO($this->db());
    return $jsd->loadData($params);
  }

  public function flowAdvance($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $jsd = new JointlySignDAO($this->db());
    return $jsd->flowAdvance($params);
  }

  public function jointlySignAdvance($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $jsd = new JointlySignDAO($this->db());
    return $jsd->jointlySignAdvance($params);
  }

  public function loadJointlyCount($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $jsd = new JointlySignDAO($this->db());
    return $jsd->loadJointlyCount($params);
  }

  public function pass($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $params['uid'] = $this->getLoginUserId();
    $jsd = new JointlySignDAO($this->db());
    return $jsd->pass($params);
  }

  public function fail($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $jsd = new JointlySignDAO($this->db());
    return $jsd->fail($params);
  }

}
