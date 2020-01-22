<?php


namespace Home\Service;


use Home\DAO\ProcessValidationDAO;

class ProcessValidationService extends PSIBaseExService
{
  public function validation($params)
  {
    $pvd = new ProcessValidationDAO($this->db());
    return $pvd->validation($params,$this->getLoginUserId());
  }

  public function isOpenValidation($vType)
  {
    $pvd = new ProcessValidationDAO($this->db());
    return $pvd->isOpenValidation($vType);
  }
}
