<?php

namespace Home\Controller;

use Home\Service\SuffixConfigService;

class SuffixConfigController extends PSIBaseController
{

  public function loadSuffix()
  {
    if (IS_POST) {

      $service = new SuffixConfigService();
      $rs = $service->loadSuffix();
      $this->ajaxReturn($rs);
    }
  }

  public function SaveSuffix()
  {
    if (IS_POST) {
      $params = [
        "office" => I("post.office"),
        "picture" => I("post.picture"),
        "other" => I("post.other")
      ];
      $service = new SuffixConfigService();
      $rs = $service->saveSuffix($params);
      $this->ajaxReturn($rs);
    }
  }
}
