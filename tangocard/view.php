<?php

namespace Wp {
  require_once("blink/base/view/generic.php");
  require_once("blink/base/view/edit.php");
  require_once("blink/base/view/detail.php");
  require_once("blink/base/view/list.php");
  require_once("blink/base/view/wizard.php");
  
  require_once("apps/blink-tangocard/tangocard/tangocard.php");
  
  class TangoCardRewardsListTemplateView extends \Blink\TemplateView {
    protected function get_content_type() {
      return \Blink\View::CONTENT_JSON;
    }
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      
      $ifg = new \BlinkTangoCard\TangoCardAPI();
      $c['reward_list'] = $ifg->rewards();
      
      return $c;
    }
  }
}

