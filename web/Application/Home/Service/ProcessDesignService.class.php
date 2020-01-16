<?php

namespace Home\Service;

use Home\DAO\ProcessDesignDAO;

class ProcessDesignService extends PSIBaseExService
{
  public function loadConfig()
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $dao = new ProcessDesignDAO($this->db());

    return $dao->loadConfig();
  }

  public function saveConfig($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $dao = new ProcessDesignDAO($this->db());

    return $dao->saveConfig($params);
  }

  public function loadProcess($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $dao = new ProcessDesignDAO($this->db());

    return $dao->loadProcess($params);
  }

  public function saveFlow($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }

    $params["uid"] = $this->getLoginUserId();
    $params["uname"] = $this->getLoginUserName();

    $dao = new ProcessDesignDAO($this->db());
    return $dao->saveFlow($params);
  }

  public function disableFlow($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $dao = new ProcessDesignDAO($this->db());
    return $dao->disableFlow($params);
  }

  public function openFlow($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }
    $dao = new ProcessDesignDAO($this->db());
    return $dao->openFlow($params);
  }

  public function saveProcess($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }

    $dao = new ProcessDesignDAO($this->db());
    return $dao->saveProcess($params);
  }

  public function getNodeInfo($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }

    $dao = new ProcessDesignDAO($this->db());
    return $dao->getNodeInfo($params);
  }

  public function loadDesign($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }

    $dao = new ProcessDesignDAO($this->db());
    return $dao->loadDesign($params);
  }

  public function saveDesign($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }

    $dao = new ProcessDesignDAO($this->db());
    return $dao->saveDesign($params);
  }

  public function deleteNode($params)
  {
    if ($this->isNotOnline()) {
      return $this->emptyResult();
    }

    $dao = new ProcessDesignDAO($this->db());
    return $dao->deleteNode($params);
  }
}
