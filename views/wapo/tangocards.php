<?php

namespace Wp {
  require_once 'blink/base/view/generic.php';
  require_once 'apps/wapo/model.php';
  
  class WpTangoCardRewardsListView extends \Blink\JSONListView {
    const PAGE_SIZE = 500;
    const MAX_PAGE_SIZE = 500;
    
    protected $class = "\Wapo\TangoCardRewards";
    
    protected function get_queryset() {
      return parent::get_queryset()->filter(["status"=>true]);
    }
  }
}