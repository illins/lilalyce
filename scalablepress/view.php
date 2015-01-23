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
  
  class ScalablePressAvailabilityTemplateView extends \Blink\TemplateView {
    protected function get_content_type() {
      return \Blink\View::CONTENT_JSON;
    }
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      
      $sp = new \BlinkScalablePress\ScalablePressAPI();
      $endpoint = sprintf("/products/%s/availability", $this->request->param->param['product_id']);
      $c['availability'] = $sp->request($endpoint, array());
      
      return $c;
    }
  }
  
  class ScalablePressQuoteFormView extends \Blink\FormView {
    protected $form_class = "Wp\GarmentForm";
    private $quote = null;
    
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
    
    protected function form_valid() {
      $data = $this->form->to_array();
      
      $quote = array(
          "type" => "dtg",
          "address" => array(
              "name" => $data['name'],
              "address1" => $data['address1'],
              "city" => $data['city'],
              "state" => $data['state'],
              "zip" => $data['zip']
          ),
          "product" => array(
              array(
                  "id" => $data['product_id'],
                  "color" => $data['color'],
                  "size" => $data['size'],
                  "quantity" => $data['quantity']
              )
          ),
          "designId" => null
      );

      $sp = new \BlinkScalablePress\ScalablePressAPI();
      $this->quote = $sp->request("quote", $quote);
      
      return parent::form_valid();
    }
  }
}

