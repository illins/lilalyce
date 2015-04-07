<?php

namespace Wp {
  require_once("blink/base/view/generic.php");
  require_once("blink/base/view/edit.php");
  require_once("blink/base/view/detail.php");
  require_once("blink/base/view/list.php");
  
  require_once("apps/wp/config.php");
  require_once("apps/wapo/model.php");
  require_once("apps/wapo/helper.php");
  require_once("apps/wp/form.php");
  
  require_once("apps/blink-user/api.php");
  require_once("apps/wepay/api.php");
  
  require_once("apps/blink-user-role/api.php");
  
  require_once("apps/swiftmailer/api.php");
  
  use Wapo\PromotionCategory;
  use Wapo\Promotion;
  use Wapo\Distributor;
  use Wapo\Profile;
  use Wapo\PromotionSend;
  use Wapo\PromotionRecipient;
  use Wapo\Helper;
  
  /**
   * Validate that user has selected a promotion when they get here.
   * Give the user options of how to send a wapo.
   */
  class DeliveryMethodTemplateView extends \Blink\TemplateView {
    protected function get_template() {
      return TemplateConfig::Template("delivery-method/method.twig");
    }
    
    protected function get_context_data() {
      $context = parent::get_context_data();
      
      $context['form'] = array(
          "cancel_url"=>"/wp/marketplace/",
          "post_url"=>""
      );
      $context['delivery_method'] = $this->request->cookie->find("delivery_method", null);
      
      return $context;
    }
    
    public function get() {
      // Validate that they have selected a promotion.
      if($this->request->get->is_set("promotion_id")) {
        if(!Promotion::queryset()->filter(array("id"=>$this->request->get->get("promotion_id")))->count()) {
          \Blink\Messages::error("Invalid promotion. Please select another.");
          return \Blink\HttpResponseRedirect("/wp/marketplace/");
        }
        
        $this->request->cookie->reset("promotion_id", $this->request->get->find("promotion_id"));
      }
      
      return parent::get();
    }
  }
  
  /**
   * - Free For All Form.
   */
  class DeliveryMethodFreeForAllFormView extends \Blink\FormView {
    protected $form_class = "\Wp\GenericQuantityForm";
    protected $success_url = "/wp/profile/";
    protected $post_url = "/wp/delivery-method/ffa/";
    protected $cancel_url = "/wp/delivery-method/";


    protected function get_template() {
      return TemplateConfig::Template("delivery-method/ffa.twig");
    }
    
    protected function get_request_data() {
      return ($this->request->method == "post") ? $this->request->post : $this->request->cookie;
    }
    
    protected function get_context_data() {
      $context = parent::get_context_data();
      $context['method'] = "post";
      return $context;
    }
    
    public function form_valid() {
      $this->form->to_cookies();
      $this->request->cookie->set("delivery_method", "ffa");
      return \Blink\HttpResponseRedirect($this->get_success_url());
    }
  }
  
  /**
   * - Email delivery method.
   * - This can be individual emails or a list of emails.
   * - Individual emails assumed by default.
   */
  class DeliveryMethodEmailFormView extends \Blink\FormView {
    protected $success_url = "/wp/profile/";
    protected $cancel_url = "/wp/delivery-method/";
    
    private $promotion = null;

    protected function get_template() {
      return TemplateConfig::Template("delivery-method/email.twig");
    }
    
    protected function get_form_fields() {
      $field_list = new \Blink\FormFields();
      $form_fields = array();
      
      // Get the email count for if user is logged in or not logged in.
      $max_email_count = ($this->request->user) ? Config::$LoggedInMaxEmailCount : Config::$NotLoggedInMaxEmailCount;

      //$form_fields[] = $forms->HiddenCharField(array("name"=>"delivery_method","value"=>"email","max_length"=>20));
      // Get the number of fields we want for the name and their data if available.
      for ($i = 1; $i <= $max_email_count; $i++) {
        $blank = ($i == 1) ? false : true;
        $form_fields[] = $field_list->CharField(array("verbose_name" => "Name", "name" => "email_name$i", "min_length" => 1, "max_length" => 50, "blank" => $blank));
        $form_fields[] = $field_list->EmailField(array("verbose_name" => "Email", "name" => "email_email$i", "min_length" => 5, "max_length" => 50, "blank" => $blank));
      }
      
      return $form_fields;
    }
    
    protected function get_request_data() {
      if($this->request->method == "post") {
        return $this->request->post;
      } else {
        // Get the emails which are stored as json in the email.
        return $this->request->cookie->json()->find("email_list");
      }
    }

    public function get_context_data() {
      $context = parent::get_context_data();
      
      $myform = $context['form'];
      
      $form = array();
      $row = 1;
      foreach($myform['visible'] as $field) {
        if(!isset($form[$row])) {
          $form[$row] = array("name"=>null,"email"=>null);
          $form[$row]['name'] = $field;
        } else {
          $form[$row]['email'] = $field;
          $row += 1;
        }
      }
      
      $context['email_form'] = $form;
      $context['promotion'] = $this->promotion;
      $context['progress'] = Helper::frontend_progress($this->request, 2);
      
      return $context;
    }

    public function form_valid() {
      // Get how many emails we can store.
      $max_email_count = ($this->request->user) ? Config::$LoggedInMaxEmailCount : Config::$NotLoggedInMaxEmailCount;

      $email_list = array();
      for ($i = 1; $i <= $max_email_count; $i++) {
        if ($this->form->get("email_email$i")) {
          $email_list["email_name$i"] = $this->form->get("email_name$i");
          $email_list["email_email$i"] = $this->form->get("email_email$i");
        }
      }

      $this->request->cookie->set("email_list", $email_list);
      $this->request->cookie->set("delivery_method", "email");

      return \Blink\HttpResponseRedirect($this->get_success_url());
    }

//    public function get() {
//      $check = Helper::check_promotion($this->request, "delivery-method");
//      if($check['error']) {
//        \Blink\Messages::error($check['message']);
//        return \Blink\HttpResponseRedirect($check['url']);
//      }
//      
//      $this->promotion = Promotion::get_or_404(array("id" => $this->request->cookie->get("promotion_id")));
//      $this->request->cookie->reset("promotion_id", $this->promotion->id);
//
//      return parent::get();
//    }

  }
  
  /**
   * - Facebook delivery method.
   * - Send to select Facebook friends.
   */
  class DeliveryMethodSelectFacebookFriendsFormView extends \Blink\FormView {
    protected $succes_url = "/wp/profile/";
    protected $form_class = "\Wp\DeliveryMethodSelectFacebookFriendsForm";
    protected $cancel_url = "/wp/delivery-method/";
    
    protected function get_template() {
      return TemplateConfig::Template("delivery-method/facebook.twig");
    }
    
    protected function form_valid() {
      // If they are logged in, allow them to send to more than 1 person.
      $facebook_id_list = explode(",", $this->request->get->get("facebook_id_list"));
      
      // Check that there is at least one person.
      if(!count($facebook_id_list)) {
        $this->error_message = "Please select Facebook at least one Facebook friend.";
        return $this->form_invalid();
      }
      
      // If they are not legged in, they can only send to 1 person since more than
      // that requires a mention (i.e. they need to enter FB place info).
      if(!$this->request->user) {
        if(count($facebook_id_list) != 1) {
          $this->error_message = "You can only send to 1 Facebook friend. Login/Register to get more options.";
          return $this->form_invalid();
        }
      }
      
      $this->request->cookie->set("facebook_id_list", $facebook_id_list);
      $this->request->cookie->set("delivery_method", "sfbf");
      
      return \Blink\HttpResponseRedirect($this->get_success_url());
    }
  }
  
  /**
   * - Facebook delivery method.
   * - Send to any friends (a max quantity is given).
   */
  class DeliveryMethodAnyFacebookFriendsFormView extends \Blink\FormView {
    protected $success_url = "/wp/profile/";
    protected $form_class = "\Wp\GenericQuantityForm";
    protected $cancel_url = "/wp/delivery-method/";
    
    protected function get_template() {
      return TemplateConfig::Template("delivery-method/facebook.twig");
    }
    
    protected function form_valid() {
      $this->form->to_cookies();
      $this->request->cookie->set("delivery_method", "anyfbf");
      return \Blink\HttpResponseRedirect($this->get_success_url());
    }
  }
  
  /**
   * - Facebook delivery method.
   * - Anyone who has liked your page.
   */
  class DeliveryMethodFacebookPageLikesFormView extends \Blink\FormView {
    protected $success_url = "/wp/profile/";
    protected $form_class = "\Wp\GenericQuantityForm";
    protected $cancel_url = "/wp/delivery-method/";
    
    protected function get_template() {
      return TemplateConfig::Template("delivery-method/facebook.twig");
    }
    
    protected function form_valid() {
      $this->form->to_cookies();
      $this->request->cookie->set("delivery_method", "anyfbf");
      return \Blink\HttpResponseRedirect($this->get_success_url());
    }
  }
  
  /**
   * - Instagram delivery method.
   * - A select few instagram followers.
   */
  class DeliveryMethodSelectInstagramFollowersFormView extends \Blink\FormView {
    protected $form_class = "\Wp\DeliveryMethodSelectInstagramFollowersForm";
    protected $success_url = "/wp/profile/";
    protected $cancel_url = "/wp/delivery-method/";
    
    protected function get_template() {
      return TemplateConfig::Template("delivery-method/instagram.twig");
    }
    
    protected function form_valid() {
      $this->form->to_cookies();
      $this->request->cookie->set("delivery_method", "sif");
      return \Blink\HttpResponseRedirect($this->get_success_url());
    }
  }
  
  /**
   * - Instagram delivery method.
   * - Any or all instagram follower(s).
   */
  class DeliveryMethodAnyInstagramFollowersFormView extends \Blink\FormView {
    protected $form_class = "\Wp\GenericQuantityForm";
    protected $success_url = "/wp/profile/";
    protected $cancel_url = "/wp/delivery-method/";
    
    protected function get_template() {
      return TemplateConfig::Template("delivery-method/instagram.twig");
    }
    
    protected function form_valid() {
      $this->form->to_cookies();
      $this->request->cookie->set("delivery_method", "anyif");
      return \Blink\HttpResponseRedirect($this->get_success_url());
    }
  }
  
  /**
   * - Twitter delivery method.
   * - Send to select twitter followers.
   */
  class DeliveryMethodSelectTwitterFollowersFormView extends \Blink\FormView {
    protected $form_class = "\Wp\GenericQuantityForm";
    protected $success_url = "/wp/profile/";
    protected $cancel_url = "/wp/delivery-method/";
    
    protected function get_template() {
      return TemplateConfig::Template("delivery-method/twitter.twig");
    }
    
    protected function form_valid() {
      $this->form->to_cookies();
      $this->request->cookie->set("delivery_method", "anytf");
      return \Blink\HttpResponseRedirect($this->get_success_url());
    }
  }
  
  /**
   * - Twitter delivery method.
   * - Send to any twitter followers
   */
  class DeliveryMethodAnyTwitterFollowersFormView extends \Blink\FormView {
    protected $form_class = "\Wp\GenericQuantityForm";
    protected $success_url = "/wp/profile/";
    protected $cancel_url = "/wp/delivery-method/";
    
    protected function get_template() {
      return TemplateConfig::Template("delivery-method/twitter.twig");
    }
    
    protected function form_valid() {
      $this->form->to_cookies();
      $this->request->cookie->set("delivery_method", "anytf");
      return \Blink\HttpResponseRedirect($this->get_success_url());
    }
  }
}