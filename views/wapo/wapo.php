<?php

namespace Wp {
  require_once 'blink/base/view/generic.php';
  require_once 'apps/wapo/model.php';
  
  class WpBaseView extends \Blink\TemplateView {
    protected function get_template() {
      return WpTemplateConfig::Template("wapo/base.twig");
    }
  }
    
  // JSON views.
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
        "promotion" => null,
        "delivery" => null,
        "email" => array(
            "email_list" => array(),
            "max" => 5
        ),
        "email_list" => array(
            "email_list" => array(),
            "max" => 25
        ),
        "mailchimp" => array(
            "subscription" => null,
            "email_list" => array(),
            "max" => 50
        ),
        "text" => array(
            "number_list" => array()
        ),
        "twitter" => array(
            "account" => null,
            "follower_list" => array()
        ),
        "facebook" => array(
            "account" => null,
            "page_list" => array()
        ),
        "delivery_message" => "",
        "quantity" => 0,
        "payment-method" => null
    );
    protected $wapo_json = null;
    protected $wapo = null;
    
    protected $require_csrf_token = false;

    protected function set_wapo() {
      $this->request->cookie->set("wapo", json_encode($this->wapo));
//      $this->request->cookie->delete("wapo");
    }
    
    protected function get_wapo() {
      if(!$this->wapo_json) {
        $this->wapo_json = (object) $this->wapo_schema;
      }
      
      if($this->wapo) {
        return;
      }
      
      try {
        $wapo_string = $this->request->cookie->find("wapo", null);
        $this->wapo = json_decode($wapo_string);
      } catch (\Exception $ex) {
        $this->wapo = null;
      }
      
      if(!$this->wapo) {
        $this->wapo = $this->wapo_json;
      } else {
        
//        $this->wapo = $this->wapo_json;
//        foreach($this->wapo_schema as $key => $value) {
//          $this->wapo->{$key} = $wapo->{$key};
//        }
      }
      
    }

    protected function get_context_data() {
      $c = parent::get_context_data();
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
      return $this->get();
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
      
      $this->wapo->module = $module;
      return parent::form_valid();
    }
  }
  
  class WpSetProfileFormView extends WpWapoFormView {
    protected function form_valid() {
      $profile = \Wapo\Profile::get_or_null(array("id"=>$this->request->post->find("profile_id"), "wapo_distributor.user"=>1));
      
      if(!$profile) {
        $this->set_error("Invalid profile selected!");
        return $this->form_invalid();
      }
      
      $this->wapo->profile->id = $profile->id;
      $this->wapo->profile->new = $this->wapo_json->new;
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
      
      if(isset($_FILES['image'])) {
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
      
      $this->wapo->marketplace = "tangocards";
      $this->wapo->tangocards = $tangocards;
      return parent::form_valid();
    }
  }
  
  class WpSetFreeForAllDeliveryFormView extends WpWapoFormView {
    protected function form_valid() {
      $quantity = $this->request->post->find("quantity", 0);
      if($quantity < 1) {
        $this->set_error("Quantity must be at least 1!");
        return $this->form_invalid();
      }
      
      $this->wapo->delivery = "free-for-all";
      $this->wapo->quantity = $quantity;
      $this->wapo->delivery_message = $this->request->post->find("delivery_message", "");
      return parent::form_valid();
    }
  }
  
  class WpSetEmailDeliveryFormView extends WpWapoFormView {
    protected function form_valid() {
      $emails = $this->request->post->find("emails", "");
      
      $this->wapo->delivery = "email";
      $this->wapo->email->email_list = explode(",", $emails);
      return parent::form_valid();
    }
  }
  
  class WpSetEmailListDeliveryFormView extends WpWapoFormView {
    protected function form_valid() {
      $emails = $this->request->post->find("emails", "");
      
      $this->wapo->delivery = "email-list";
      $this->wapo->email_list->email_list = explode(",", $emails);
      return parent::form_valid();
    }
  }
  
  class WpSetMailChimpDeliveryFormView extends WpWapoFormView {
    protected function form_valid() {
      $emails = $this->request->post->find("emails", "");
      
      $this->wapo->delivery = "mailchimp";
      $this->wapo->numbers = explode(",", $emails);
      return parent::form_valid();
    }
  }
  
  class WpSetTextDeliveryFormView extends WpWapoFormView {
    protected function form_valid() {
      $numbers = $this->request->post->find("numbers", "");
      
      $this->wapo->delivery = "text";
      $this->wapo->numbers = explode(",", $numbers);
      return parent::form_valid();
    }
  }
  
  class WpSetFacebookPageDeliveryFormView extends WpWapoFormView {
    protected function form_valid() {
      $page = $this->request->post->find("page", "");
      
      $this->wapo->delivery = "facebook-page";
      $this->wapo->facebook->page = $page;
      return parent::form_valid();
    }
  }
  
  class WpSetFacebookFriendsDeliveryFormView extends WpWapoFormView {
    protected function form_valid() {
      $this->wapo->delivery = "facebook-friends";
      return parent::form_valid();
    }
  }
  
  class WpSetTwitterAnyTwitterFollowersFormView extends WpWapoFormView {
    protected function form_valid() {
      $this->wapo->delivery = "any-twitter-followers";
      return parent::form_valid();
    }
  }
  
  class WpSetSelectTwitterFollowersFormView extends WpWapoFormView {
    protected function form_valid() {
      $followers = $this->request->post->find("followers", "");
      $this->wapo->delivery = "select-twitter-followers";
      $this->wapo->twitter->followers = explode(",", $followers);
      return parent::form_valid();
    }
  }
  
  class WpoCheckoutWapoFormView extends WpWapoFormView {
    protected function form_valid() {
      $amount = 0;
      $short_description = "";
      $redirect_uri = sprintf("%s/wp/create/", \Blink\SiteConfig::SITE);
      
      
      // Do validation here.
      //list($error, $message, $data) = validate_wapo($this->request);
      
      // If: tango-card - Set the 'sku' as the 'short_description'.
      // Else: wapo - Set the 'promotion category name' as the 'short_description'.
      if ($this->promotioncategory->tag == "tango-card") {
        $short_description = $data['sku']->sku;
      } else if ($this->promotioncategory->tag == "wapo") {
        $short_description = $data['promotioncategory']->name;
      }
      
      if($this->wapo->marketplace == "tangocards") {
        $short_description = $this->wapo->tangocards->sku;
      } else if($this->wapo->marketplace == "promotion") {
        $short_description = $this->wapo->promotion->name;
      }

      // Create the checkout.
      // @todo - Validate that it was created.
      $wepay = new \WePay\WepayAPI();
      $checkout = $wepay->checkout_create($data['total'], $short_description, $redirect_uri);

      // Add to context.
      $context['checkout'] = $checkout;


      return parent::form_valid();
    }
  }
  
  class WpPaymentFormView extends WpWapoFormView {
    protected function form_valid() {
      if($this->wapo->payment_method == "wepay") {
        
      }
      return parent::form_valid();
    }
  }
}