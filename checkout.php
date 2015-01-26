<?php

namespace Wp {
  
  require_once("blink/base/view/generic.php");
  require_once("blink/base/view/edit.php");
  require_once("blink/base/view/detail.php");
  require_once("blink/base/view/list.php");
  require_once("blink/base/view/wizard.php");
  
  require_once("apps/wp/config.php");
  require_once("apps/wapo/model.php");
  require_once("apps/wapo/helper.php");
  require_once("apps/wp/form.php");
  
  use Wapo\PromotionCategory;
  use Wapo\Promotion;
  use Wapo\Distributor;
  use Wapo\Profile;
  use Wapo\PromotionSend;
  use Wapo\PromotionRecipient;
  use Wapo\Helper;
  
  /**
   * - Display currently available Wapo information. 
   * - Promotion selected, delivery method, delivery message... 
   */
  class SideBarTemplateView extends \Blink\TemplateView {
    public function get_template_name() {
      return ConfigTemplate::DefaultTemplate("pipeline/sidebar.twig");
    }
    
    public function get_context_data() {
      $context = parent::get_context_data();
      
      // Get the promotion.
      $promotion = Promotion::get_or_null(array("id"=>$this->request->cookie->find("promotion_id")));
      $sku = \Wapo\TangoCardRewards::get_or_null(array("sku"=>$this->request->cookie->find("sku")));
      
      // Get the delivery method.
      $delivery_method = $this->request->cookie->find("delivery");
      $context['delivery_message'] = $this->request->cookie->find("delivery_message");
      $context['expiring_date'] = $this->request->cookie->find("expiring_date");
      $context['quantity'] = $this->request->cookie->find("quantity", 0);
      
      if($delivery_method == "ffa") {
//        $context['quantity'] = $this->request->cookie->find("quantity", 0);
      } else if($delivery_method == "sff") {
        $context['facebook_friends'] = $this->request->cookie->find("facebook_friends");
        $context['quantity'] = count(explode(",", $context['facebook_friends']));
      } else if($delivery_method == "aff") {
//        $context['quantity'] = $this->request->cookie->find("quantity", 0);
      } else if($delivery_method == "fp") {
        $context['facebook_page_id'] = $this->request->cookie->find("facebook_page_id");
//        $context['quantity'] = $this->request->cookie->find("quantity", 0);
      } else if($delivery_method == "atf") {
//        $context['quantity'] = $this->request->cookie->find("quantity");
      } else if($delivery_method == "stf") {
        $context['twitter_followers'] = $this->request->cookie->find("twitter_followers");
        $context['quantity'] = count(explode(",", $context['twitter_followers']));
      } else if($delivery_method == "aif") {
//        $context['quantity'] = $this->request->cookie->find("quantity", 0);
      } else if($delivery_method == "sif") {
        $context['instagram_followers'] = $this->request->cookie->find("instagram_followers");
        $context['quantity'] = count(explode(",", $context['instagram_followers']));
      } else if($delivery_method == "mailchimp") {
//        $context['quantity'] = $this->request->cookie->find("quantity");
      } else if($delivery_method == "e") {
        $max = max(array(Config::$LoggedInMaxEmailDeliveryCount, Config::$NotLoggedInMaxEmailDeliveryCount));
        
        $count = 0;
        for($i = 1; $i <= $max; $i++) {
          if($this->request->cookie->is_set("email-$i")) {
            $count++;
          }
        }
        
        $context['quantity'] = $count;
      } else if($delivery_method == "el") {
        $filter = array("id"=>$this->request->cookie->find("contact_id",0),"wapo_contact.user"=>$this->request->user);
        $context['quantity'] = count(\Wapo\ContactItem::queryset()->filter($filter)->fetch());
      }
      
      $context['delivery'] = $delivery_method;
      $context['delivery_name'] = isset(Config::$DeliveryMethod[$delivery_method]) ? Config::$DeliveryMethod[$delivery_method] : "";
      $context['promotion'] = $promotion;
      $context['sku'] = $sku;
      $context['amount'] = $this->request->cookie->find("amount");
      $context['promotioncategory'] = ($promotion) ? $promotion->promotioncategory : null;
      
      return $context;
    }
  }
  
  /**
   * - Display the summary of what is about to occur on the summary page.
   */
  class CheckoutFormView extends \Blink\FormView {
    protected function get_template_name() {
      return ConfigTemplate::DefaultTemplate("checkout/checkout.twig");
    }
  }
  
  /**
   * - Fake 'WePay' checkout that creates a fake id for the purpose of quick testing.
   */
  class CheckoutFakeWePay extends \Blink\RedirectView {
    
  }
  
  
}

