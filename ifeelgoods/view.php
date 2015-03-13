<?php

namespace Wp {
  require_once("blink/base/view/generic.php");
  require_once("blink/base/view/edit.php");
  require_once("blink/base/view/detail.php");
  require_once("blink/base/view/list.php");
  require_once("blink/base/view/wizard.php");
  
  require_once("apps/blink-ifeelgoods/ifeelgoods/ifeelgoods.php");
  
  class IfeelGoodsRewardsListTemplateView extends \Blink\TemplateView {
    protected function get_content_type() {
      return \Blink\View::CONTENT_JSON;
    }
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      
      $ifg = new \BlinkIfeelGoods\IfeelGoodsAPI(array("request"=>$this->request));
//      $c['me'] = $ifg->me();
//      $c['rewards'] = $ifg->rewards();
      $available_rewards = $ifg->available_rewards(\Blink\IfeelGoodsConfig::PROMOTION_ID);
      
      $sku_list = array();
      foreach($available_rewards->data->rewards as $reward) {
        $sku_list[] = $reward->sku;
      }
      
      $c['sku_list'] = $sku_list;
      
      
      
      return $c;
    }
  }
}

