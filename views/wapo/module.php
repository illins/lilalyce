<?php

namespace Wp {
  require_once 'blink/base/view/generic.php';
  require_once 'apps/wapo/model.php';
  
  class WpModuleListView extends \Blink\JSONListView {
    protected $class = "\Wapo\Module";
    
//    protected function get_queryset() {
//      $q = parent::get_queryset();
//      return $q->filter(array());
//    }
  }
}