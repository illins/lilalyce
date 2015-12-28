<?php

namespace Wp {
  require_once 'blink/base/view/generic.php';
  require_once 'apps/wapo/model.php';
  
  class WpProfileListView extends \Blink\JSONListView {
    protected $class = "\Wapo\Profile";
    
    protected function get_queryset() {
      return parent::get_queryset()->filter(array("wapo_distributor.user"=>$this->request->user));
    }
  }
}