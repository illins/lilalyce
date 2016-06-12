<?php

namespace Wp {
  require_once 'blink/base/view/generic.php';
  require_once 'apps/wapo/model.php';
  
  require_once 'apps/wp/views/wapo/validate.php';
  require_once 'apps/wp/views/wapo/create.php';
  require_once 'apps/wp/views/wapo/send.php';
  
  /**
   * Base class for setting data in the pipeline.
   */
  class WpWapoFormView extends \Blink\JSONFormView {
    protected $form_class = "\Blink\Form";
    private $wapo_schema = array(
        "module" => null,
        "profile" => array(
            "profile" => null,
            "new" => array(
                "name" => "",
                "email" => "",
                "password" => "",
                "image" => ""
            )
        ),
        "marketplace" => null,
        "tangocards" => null,
        "unit_price" => 0,
        "promotion" => null,
        "delivery" => null,
        "email" => array(
            "email_list" => array(),
            "max" => 3
        ),
        "email_list" => array(
            "email_list" => array(),
            "max" => 25
        ),
        "mailchimp" => array(
            "account" => null,
            "subscription" => null,
            "email_list" => array(),
            "max" => 50
        ),
        "text" => array(
            "number_list" => array(),
            "max" => 25
        ),
        "twitter" => array(
            "account" => null,
            "follower_list" => array()
        ),
        "facebook" => array(
            "profile" => null,
            "page_list" => array(),
            "page_name_list" => array()
        ),
        "delivery_message" => "",
        "quantity" => 0,
        "payment_method" => null
    );
    protected $wapo_json = null;
    protected $wapo = null;
    
    protected $require_csrf_token = false;

    /**
     * Set the wapo session.
     */
    protected function set_wapo() {
      $this->request->session->set("wapo", $this->wapo);
    }
    
    /**
     * Convert a dictionary to an object recursively.
     * @param array $mixed
     * @return \stdClass
     */
    final protected function to_object($mixed) {
      if(is_array($mixed)) {
        $object = new \stdClass();
        foreach($mixed as $key => $value) {
          if(is_array($value)) {
            $object->{$key} = $this->to_object($value);
          } else {
            $object->{$key} = $value;
          }
        }
        return $object;
      } else {
        return null;
      }
    }
    
    final protected function get_new_wapo() {
      $this->wapo = $this->to_object($this->wapo_schema);
    }

    protected function get_wapo() {
      if(!$this->wapo_json) {
        $this->wapo_json = $this->to_object($this->wapo_schema);
      }
      
      if($this->wapo) {
        return;
      }
      
      try {
        $this->wapo = $this->request->session->find("wapo", null);
      } catch (\Exception $ex) {
        $this->wapo = null;
      }
      
      if(!$this->wapo) {
        $this->wapo = $this->wapo_json;
      }
    }

    protected function get_context_data() {
      $c = parent::get_context_data();
      
      // Set some values for email max based on authentication.
      if ($this->wapo) {
        if ($this->request->user) {
          $this->wapo->email->max = WpConfig::MAX_EMAIL_DELIVERY_COUNT_USER;
        } else {
          $this->wapo->email->max = WpConfig::MAX_EMAIL_DELIVERY_COUNT_GUEST;
        }
      }
      
      $c['wapo'] = $this->wapo;
      $this->set_wapo();
      return $c;
    }
    
    protected function post() {
      $this->get_wapo();
      return parent::post();
    }
    
    protected function get() {
      $this->get_wapo();
      return parent::get();
    }
    
    protected function form_valid() {
      return $this->get();
    }
    
    protected function form_invalid() {
      $this->response_code = \Blink\Response::BAD_REQUEST;
      return $this->get();
    }
    
    protected function is_free() {
      if($this->wapo->marketplace == "promotion") {
        if($this->wapo->promotion->price == 0) {
          return true;
        }
      }
      
      return false;
    }
    
    protected function has_checkout_id() {
      return ($this->request->session->prefix("wepay-")->find("checkout_id", null));
    }
  }
  
  /**
   * Reset the data for use later.
   */
  class WpStartOverFormView extends WpWapoFormView {
    protected function get_wapo() {
      $this->request->session->delete("wapo");
      $this->wapo = $this->get_new_wapo();
    }
  }
  
  class WpSetModuleFormView extends WpWapoFormView {
    
    public function __construct(&$request, $options = array()) {
      parent::__construct($request, $options);
    }
    protected function form_valid() {
      $module = \Wapo\Module::get_or_null(array("id"=>$this->request->post->find("module_id", null)));
      
      if(!$module) {
        $this->set_error("Invalid module selected!");
        return $this->form_invalid();
      }
      
      $this->wapo->module = (object) $module->to_plain_array();
      return parent::form_valid();
    }
  }
  
  class WpSetProfileFormView extends WpWapoFormView {
    protected function form_valid() {
      $profile = \Wapo\Profile::get_or_null(array("id"=>$this->request->post->find("profile_id"), "wapo_distributor.user"=>$this->request->user));
      
      if(!$profile) {
        $this->set_error("Invalid profile selected!");
        return $this->form_invalid();
      }
      
      $this->wapo->profile->profile = (object) $profile->to_plain_array();
      $this->wapo->profile->new = (object) array(
                  "name" => "",
                  "email" => "",
                  "password" => "",
                  "image" => ""
      );
      
      return parent::form_valid();
    }
  }
  
  // Clear the profile if the user logs out.
  class WpClearProfileFormView extends WpWapoFormView {
    protected function form_valid() {
      $this->wapo->profile->profile = null;
      return parent::form_valid();
    }
  }
  
  class WpSetNewProfileFormView extends WpWapoFormView {
    protected function form_valid() {
      $name = $this->request->post->find("name", "");
      $email = $this->request->post->find("email", "");
//      $password = $this->request->post->find("password", "");
//      $confirm_password = $this->request->post->find("confirm_password", "");
      
      if(!$name || !$email) {
        $this->set_error("Required fields missing!");
        return $this->form_invalid();
      }
      
//      if($password != $confirm_password) {
//        $this->set_error("Passwords do not match!");
//        return $this->form_invalid();
//      }
      
      // If request has come to delete this file, then delete it.
      if($this->request->post->is_set("delete") && $this->wapo->profile->new->image) {
        unlink($this->wapo->profile->new->image);
        $this->wapo->profile->new->image = "";
      }
      
      if(\Blink\Files::is_set('image')) {
        $file = new \Blink\Files("image");
        
        if(!$file->move("media/wp/tmp/profile", uniqid("profile-"))) {
          $this->set_error($file->get_last_error());
          return $this->form_invalid();
        }
        
        // Delete old file if present.
        if($this->wapo->profile->new->image) {
          unlink($this->wapo->profile->new->image);
        }
        
        // Set new file.
        $this->wapo->profile->new->image = $file->get_file_path();
      }
      
//      $this->wapo->profile->id = $this->wapo_json->id;
      $this->wapo->profile->new->name = $name;
      $this->wapo->profile->new->email = $email;
      $this->wapo->profile->profile = null;
      
//      $this->wapo->profile->new->password = $password;
      return parent::form_valid();
    }
  }
  
  class WpSetTangoCardsFormView extends WpWapoFormView {
    protected function form_valid() {
      $tangocards = \Wapo\TangoCardRewards::get_or_null(array("id"=>$this->request->post->find("tangocards_id")));
      
      if(!$tangocards) {
        $this->set_error("Invalid wapo selected!");
        return $this->form_invalid();
      }
      
      if(!$this->request->post->int("unit_price", 0)) {
        $this->set_error("Please select a unit price!");
        return $this->form_invalid();
      }
      
      $this->wapo->marketplace = "tangocards";
      $this->wapo->tangocards = (object) $tangocards->to_plain_array();
      $this->wapo->unit_price = $this->request->post->int("unit_price");
      
      return parent::form_valid();
    }
  }
  
  class WpSetPromotionFormView extends WpWapoFormView {
    protected function form_valid() {
      $promotion = \Wapo\Promotion::get_or_null(array("id"=>$this->request->post->find("promotion_id")));
      
      if(!$promotion) {
        $this->set_error("Invalid wapo selected!");
        return $this->form_invalid();
      }
      
      
      $this->wapo->marketplace = "promotion";
      $this->wapo->promotion = (object) $promotion->to_plain_array();
      return parent::form_valid();
    }
  }
  
  /**
   * Base class for *delivery.
   * Set the delivery message.
   */
  class WpDeliveryFormView extends WpWapoFormView {
    protected function form_valid() {
      $this->wapo->delivery_message = $this->request->post->find("delivery_message", "");
      return parent::form_valid();
    }
  }
  
  class WpSetFreeForAllDeliveryFormView extends WpDeliveryFormView {
    protected function form_valid() {
      $quantity = $this->request->post->find("quantity", 0);
      if($quantity < 1) {
        $this->set_error("Quantity must be at least 1!");
        return $this->form_invalid();
      }
      
      $this->wapo->delivery = "free-for-all";
      $this->wapo->quantity = $quantity;
      return parent::form_valid();
    }
  }
  
  /**
   * Base class for email*delivery. 
   * Cleans the emails sent from server and cleans duplicates.
   * Sets the quantity.
   */
  class WpEmailDeliveryFormView extends WpDeliveryFormView {
    protected function clean_emails($emails) {
      $email_list = array();
      foreach(explode(",", $emails) as $email) {
        $trimmed = trim($email);
        if($trimmed && !in_array($trimmed, $email_list)) {
          $email_list[] = $trimmed;
        }
      }
      
      $this->wapo->quantity = count($email_list);
      return $email_list;
    }
  }
  
  class WpSetEmailDeliveryFormView extends WpEmailDeliveryFormView {
    protected function form_valid() {
      $emails = $this->request->post->find("emails", "");
      
      $email_list = $this->clean_emails($emails);
      if(!count($email_list)) {
        $this->set_error("Please select at least one email!");
        return $this->form_invalid();
      }
      
      $this->wapo->delivery = "email";
      $this->wapo->email->email_list = $email_list;
      return parent::form_valid();
    }
  }
  
  class WpSetEmailListDeliveryFormView extends WpEmailDeliveryFormView {
    protected function form_valid() {
      $emails = $this->request->post->find("emails", "");
      
      $email_list = $this->clean_emails($emails);
      if(!count($email_list)) {
        $this->set_error("Please select at least one email!");
        return $this->form_invalid();
      }
      
      $this->wapo->delivery = "email-list";
      $this->wapo->email_list->email_list = $email_list;
      return parent::form_valid();
    }
  }
  
  class WpSetMailChimpDeliveryFormView extends WpEmailDeliveryFormView {
    protected function form_valid() {
      $emails = $this->request->post->find("emails", "");
      $subscription = $this->request->post->find("subscription", "");
      
      $email_list = $this->clean_emails($emails);
      if(!count($email_list)) {
        $this->set_error("Please select at least one email!");
        return $this->form_invalid();
      }
      
      // Get mailchimp ping.
      $account = $this->wapo->mailchimp->account;
      if(!$account) {
        $api_key = $this->request->cookie->prefix("mailchimp-")->find("apikey", "");
        $account = (new \Drewm\MailChimp($api_key))->call("helper/ping");
      }
      
      $this->wapo->delivery = "mailchimp";
      $this->wapo->mailchimp->account = $account;
      $this->wapo->mailchimp->subscription = $subscription;
      $this->wapo->mailchimp->email_list = $email_list;
      return parent::form_valid();
    }
  }
  
  class WpSetTextDeliveryFormView extends WpDeliveryFormView {
    protected function form_valid() {
      $numbers = $this->request->post->find("numbers", "");
      
      $number_list = array();
      $error = false;
      foreach(explode(",", $numbers) as $number) {
        $cleaned = trim(str_replace(array(" ", ")", "(", "-"), "", $number));
        if(!is_int((int) $cleaned) || strlen($cleaned) != 10) {
          $error = true;
          break;
        }
        if($cleaned) {
          $number_list[] = $cleaned;
        }
      }
      
      if($error) {
        $this->set_error("You have entered some invalid numbers!");
        return $this->form_invalid();
      }
      
      if(!count($number_list)) {
        $this->set_error("Please select at least one number!");
        return $this->form_invalid();
      }
      
      $this->wapo->delivery = "text";
      $this->wapo->text->number_list = $number_list;
      $this->wapo->quantity = count($number_list);
      
      return parent::form_valid();
    }
  }
  
  class WpSetFacebookPageDeliveryFormView extends WpDeliveryFormView {
    protected function form_valid() {
      $page = $this->request->post->find("page", "");
      $name = $this->request->post->find("name", "");
      $quantity = $this->request->post->find("quantity", 0);
      
      if(!$this->wapo->facebook->profile) {
        $fbapi = new \BlinkFacebook\BlinkFacebookApi($this->request);
        $this->wapo->facebook->profile = $fbapi->getUserProfile();
        $this->wapo->facebook->picture = $fbapi->getUserPicture();
      }
      
      $this->wapo->delivery = "facebook-page";
      $this->wapo->facebook->page = $page;
      $this->wapo->facebook->page_name =$name;
      $this->wapo->quantity = $quantity;
      return parent::form_valid();
    }
  }
  
  class WpSetAnyFacebookFriendsDeliveryFormView extends WpDeliveryFormView {
    protected function form_valid() {
      $quantity = $this->request->post->find("quantity", 0);
      
      if($quantity < 1) {
        $this->set_error("Please enter a valid quantity greater than 0!");
        return $this->form_invalid();
      }
      
      if(!$this->wapo->facebook->profile) {
        $fbapi = new \BlinkFacebook\BlinkFacebookApi($this->request);
        $this->wapo->facebook->profile = $fbapi->getUserProfile();
        $this->wapo->facebook->picture = $fbapi->getUserPicture();
      }
      
      $this->wapo->delivery = "any-facebook-friends";
      $this->wapo->quantity = $quantity;
      return parent::form_valid();
    }
  }
  
  class WpSetAnyTwitterFollowersDeliveryFormView extends WpDeliveryFormView {
    protected function form_valid() {
      $quantity = $this->request->post->find("quantity", 0);
      
      if($quantity < 1) {
        $this->set_error("Please enter a valid quantity greater than 0!".$quantity);
        return $this->form_invalid();
      }
      
      if(!$this->wapo->twitter->account) {
        $tapi = new \BlinkTwitter\BlinkTwitterAPI($this->request);
        $this->wapo->twitter->account = $tapi->getProfile();
      }
      
      $this->wapo->delivery = "any-twitter-followers";
      $this->wapo->quantity = $quantity;
      return parent::form_valid();
    }
  }
  
  class WpSetSelectTwitterFollowersDeliveryFormView extends WpDeliveryFormView {
    protected function form_valid() {
      $followers = $this->request->post->find("followers", "");
      
      $follower_list = array();
      foreach(explode(",", $followers) as $follower) {
        $trimmed = trim($follower);
        if($follower) {
          $follower_list[] = $trimmed;
        }
      }
      
      if(!count($follower_list)) {
        $this->set_error("Please select at least one follower!");
        return $this->form_invalid();
      }
      
      if(!$this->wapo->twitter->account) {
        $tapi = new \BlinkTwitter\BlinkTwitterAPI($this->request);
        $this->wapo->twitter->account = $tapi->getProfile();
      }
      
      $this->wapo->delivery = "select-twitter-followers";
      $this->wapo->twitter->follower_list = explode(",", $followers);
      $this->wapo->quantity = count($follower_list);
      return parent::form_valid();
    }
  }
  
  class WpValidateFormView extends WpWapoFormView {
    private $wepay;
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      $c['client_id'] = \Blink\WePayConfig::CLIENT_ID;
      return $c;
    }
    
    protected function form_valid() {
      // Clear these session keys for future use.
      $this->request->session->delete("wapo_id");
      $this->request->session->prefix("wepay-")->delete("checkout_id");
      
      $delivery_methods = array("email", "email-list", "text");
      $payment_methods = array("wepay", "free");
      $payment_method = $this->request->post->find("payment_method", "free");
      
      $message = validate_module($this->request, $this->wapo);
      
      if($message) {
        $this->set_error($message);
        return $this->form_invalid();
      }
      
      $profile = validate_profile($this->request, $this->wapo);
      if($profile) {
        $this->set_error($profile);
        return $this->form_invalid();
      }
      
      if(!in_array($this->wapo->marketplace, array("tangocards", "promotion"))) {
        $this->set_error("Please select a reward!");
        return $this->form_invalid();
      }
      
      if($this->wapo->marketplace == "tangocards") {
        $tangocards = validate_tangocards($this->request, $this->wapo);
        if($tangocards) {
          $this->set_error($tangocards);
          return $this->form_invalid();
        }
      } else if($this->wapo->marketplace == "promotion") {
        $promotion = validate_promotion($this->request, $this->wapo);
        if($promotion) {
          $this->set_error($promotion);
          return $this->form_invalid();
        }
      }
      
      // Validate which delivery methods are allowed for now.
      if(!in_array($this->wapo->delivery, $delivery_methods)) {
        $this->set_error("Delivery method not allowed!");
        return $this->form_invalid();
      }
      
      // Validate delivery method.
      if($this->wapo->delivery == "free-for-all") {
        $message = validate_delivery_ffa($this->request, $this->wapo);
      } else if($this->wapo->delivery == "email") {
        $message = validate_delivery_email($this->request, $this->wapo);
      } else if($this->wapo->delivery == "email-list") {
        $message = validate_delivery_email_list($this->request, $this->wapo);
      } else if($this->wapo->delivery == "mailchimp") {
        $message = validate_delivery_mailchimp($this->request, $this->wapo);
      } else if($this->wapo->delivery == "facebook-page") {
        $message = validate_delivery_facebook_page($this->request, $this->wapo);
      } else if($this->wapo->delivery == "any-facebook-friends") {
        $message = validate_delivery_facebook($this->request, $this->wapo);
      } else if($this->wapo->delivery == "any-twitter-followers") {
        $message = validate_delivery_twitter($this->request, $this->wapo);
      } else if($this->wapo->delivery == "select-twitter-followers") {
        $message = validate_delivery_twitter($this->request, $this->wapo);
      } else if($this->wapo->delivery == "text") {
        $message = validate_delivery_text($this->request, $this->wapo);
      } else {
        $message = "Invalid delivery method!";
      }
      
      if($message) {
        $this->set_error($message);
        return $this->form_invalid();
      }
      
      return parent::form_valid();
    }
  }
  
  class WpCheckoutCreateFormView extends WpWapoFormView {
    private $wepay;
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      $c['wepay'] = $this->wepay;
      return $c;
    }
    
    protected function form_valid() {
      $payment_method = "wepay";
      $credit_card_id = $this->request->post->find("credit_card_id", null);
      
      // Calculate the checkout stuff.
      $amount = 0;
      $short_description = "";
      
      if($this->wapo->marketplace == "tangocards") {
        $short_description = $this->wapo->tangocards->sku;
        $amount += ($this->wapo->quantity * ($this->wapo->unit_price / 100));
      } else if($this->wapo->marketplace == "promotion") {
        $short_description = $this->wapo->promotion->name;
        $amount += ($this->wapo->quantity * $this->wapo->promotion->price);
      }
      
      if($amount == 0) {
        $payment_method = "free";
      }
      
      // Create the checkout.
      if($payment_method == "wepay") {
        if (!$credit_card_id) {
          $this->set_error("Invalid card used!");
          return $this->form_invalid();
        }
        
        try {
          $wepay = new \WePay\WepayAPI();
          $this->wepay = $wepay->checkout_create_tokenized($amount, $short_description, $credit_card_id);
          
          if(!$this->wepay->checkout_id) {
            throw new Exception("Could not charge card!");
          }
          
          //      $this->request->session->set("checkoutid", 760173062);760173062
          $this->request->session->prefix("wepay-")->set("checkout_id", $this->wepay->checkout_id);
        } catch (\Exception $ex) {
          $this->set_error("Could not initialize payment method!");
          return $this->form_invalid();
        }
        
      } else if($payment_method == "free") {
        ;
      } else {
        $this->set_error("Invalid payment method!");
        return $this->form_invalid();
      }
      $this->wapo->payment_method = $payment_method;
      
      return parent::form_valid();
    }
  }
  
  class WpPaymentFormView extends WpWapoFormView {
    protected $verified = false;
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      $c['verified'] = $this->verified;
      return $c;
    }
    protected function form_valid() {
      $checkout_id = $this->request->session->prefix("wepay-")->find("checkout_id", null);
      
      if(!$checkout_id) {
        $this->set_error("Could not verify payment or payment did not complete!");
        return $this->form_invalid();
      }
      
      $checkout = (new \WePay\WepayAPI())->checkout($checkout_id);
      
      if($checkout->state == "captured") {
        $this->verified = true;
      } else {
        $this->set_error("Could not verify payment or payment did not complete!");
        return $this->form_invalid();
      }
      
      return parent::form_valid();
    }
  }
  
  class WpFreeFormView extends WpWapoFormView {
    protected $verified = false;
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      $c['verified'] = $this->verified;
      return $c;
    }
    
    protected function form_valid() {
      if($this->is_free()) {
        $this->verified = true;
      } else {
        $this->set_error("Could not verify promotion!");
        return $this->form_invalid();
      }
      
      $this->wapo->payment_method = "free";
      
      return parent::form_valid();
    }
  }
  
  /**
   * Form view for post checkout.
   * Delete the wapo from the context.
   */
  class WpCheckoutFormView extends WpWapoFormView {
    protected $wp;
    
    protected function get_wapo() {
      $this->wapo = null;
    }
    
    protected function set_wapo() {
//      $this->wapo = null;
    }
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      unset($c['wapo']);
      return $c;
    }
  }
  
  class WpCreateFormView extends WpWapoFormView {
    protected $wapo_id;
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      $c['wapo_id'] = $this->wapo_id;
      return $c;
    }
    
    protected function form_valid() {
      // If we have created a wapo already, return it. Don't let it be created again.
      $this->wapo_id = $this->request->session->find("wapo_id", null);
      if($this->wapo_id) {
        return parent::form_valid();
      }
      
      list($wapo, $error) = create_wapo($this->request, $this->wapo);
      
      if($error) {
        $this->set_error($error);
        return $this->form_invalid();
      }
      
      $this->wapo_id = $wapo->id;
      $this->request->session->set("wapo_id", $wapo->id);
      
      return parent::form_valid();
    }
  }
  
  class WpSendFormView extends WpCheckoutFormView {
    protected function form_valid() {
      // Check that the wapo to send matches the wapo in the session.
      if($this->request->session->find("wapo_id") != $this->request->post->find("wapo_id")) {
        $this->set_error("Invalid wapo id!");
        return $this->form_invalid();
      }
      
      // Get the wapo.
      $wapo = \Wapo\Wapo::get_or_null(array("id"=>$this->request->session->find("wapo_id")));
      if(!$wapo) {
        $this->set_error("Wapo not found!");
        return $this->form_invalid();
      }
      
      // Check that the wapo can be sent.
      if($wapo->status != \Wapo\Wapo::PAID) {
        $this->set_error("Wapo cannot be sent at this time!");
        return $this->form_invalid();
      }
      
      // Send the wapo.
      $result = send_wapo($this->request, $wapo);
      if($result) {
        $this->set_error($result);
        return $this->form_invalid();
      }
      
      return parent::form_valid();
    }
  }
}