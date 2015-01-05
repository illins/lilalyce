<?php

namespace Wp {
  require_once("blink/base/view/generic.php");
  require_once("blink/base/view/edit.php");
  require_once("blink/base/view/detail.php");
  require_once("blink/base/view/list.php");
  require_once("blink/base/view/wizard.php");
  
  require_once("apps/blink-scalablepress/scalablepress/scalablepress.php");
  
  class ScalablePressCategoryListTemplateView extends \Blink\TemplateView {
    protected function get_content_type() {
      return \Blink\View::CONTENT_JSON;
    }
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      
      $sp = new \BlinkScalablePress\ScalablePressAPI();
      $c['category_list'] = $sp->request("categories", array());
      
      return $c;
    }
  }
  
  class ScalablePressProductListTemplateView extends \Blink\TemplateView {
    protected function get_content_type() {
      return \Blink\View::CONTENT_JSON;
    }
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      
      $sp = new \BlinkScalablePress\ScalablePressAPI();
      $endpoint = "categories/".$this->request->param->param['category_id'];
      $c['category'] = $sp->request($endpoint, array());
      
      return $c;
    }
  }
  
  class ScalablePressProductDetailTemplateView extends \Blink\TemplateView {
    protected function get_content_type() {
      return \Blink\View::CONTENT_JSON;
    }
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      
      $sp = new \BlinkScalablePress\ScalablePressAPI();
      $endpoint = "products/".$this->request->param->param['product_id'];
      $c['product'] = $sp->request($endpoint, array());
      
      return $c;
    }
  }
}

