<?php

namespace Home\Service;

use Home\Service\PSIBaseExService;
use Home\DAO\SuffixConfigDAO;

class SuffixConfigService extends PSIBaseExService
{
  public function loadSuffix()
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $dao = new SuffixConfigDAO($this->db());
    return $dao->loadSuffix();
  }

  public function saveSuffix($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }

    $params["office"] = explode("-",
      str_replace(" ", "", $params["office"]));
    $params["picture"] = explode("-",
      str_replace(" ", "", $params["picture"]));
    $params["other"] = explode("-",
      str_replace(" ", "", $params["other"]));

    $dao = new SuffixConfigDAO($this->db());
    return $dao->saveSuffix($params);
  }

  public function getSuffixs($type = null){
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $dao = new SuffixConfigDAO($this->db());
    return $dao->getSuffixs($type);
  }
}
