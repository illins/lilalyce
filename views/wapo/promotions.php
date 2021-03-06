<?php

namespace Wp {
  require_once 'blink/base/view/generic.php';
  require_once 'apps/wapo/model.php';
  
  class WpPromotionCategoryListView extends \Blink\CRUDListView {
    
    protected $class = "\Wapo\PromotionCategory";
    
    protected function get_queryset() {
      return parent::get_queryset()->filter(["active"=>true]);
    }
  }
  
  class WpPromotionListView extends \Blink\CRUDListView {
    protected $class = "\Wapo\Promotion";
    
    protected function get_queryset() {
      $q = parent::get_queryset()->filter(["active"=>true]);
      
      if($this->request->get->is_set("promotioncategory")) {
        $q->filter(array("promotioncategory"=>$this->request->get->get("promotioncategory"), "wapo_promotioncategory.active"=>true));
      }
      
      return $q;
    }
  }
}