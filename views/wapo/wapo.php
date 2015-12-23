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
            "id" => null,
            "new" => array(
                "name" => "",
                "email" => "",
                "password" => ""
            )
        ),
        "marketplace" => null,
        "tangocards" => array(
            "id" => null
        ),
        "delivery" => null,
        "emails" => array(),
        "twitter" => array(
            "followers" => array()
        ),
        "facebook" => array(
            "page" => null
        ),
        "delivery-message" => "",
        "quantity" => 0,
        "payment-method" => null
    );
    protected $wapo_json = null;
    protected $wapo = null;
    
    protected $require_csrf_token = false;

    protected function set_wapo() {
      $this->request->cookie->set("wapo", json_encode($this->wapo));
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
        $this->wapo = $this->wapo_json;
      }
      
      if(!$this->wapo) {
        $this->wapo = $this->wapo_json;
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
      $password = $this->request->post->find("password", "");
      $confirm_password = $this->request->post->find("confirm_password", "");
      
      if(!$name || !$email || !$password) {
        $this->set_error("Required fields missing!");
        return $this->form_invalid();
      }
      
      if($password != $confirm_password) {
        $this->set_error("Passwords do not match!");
        return $this->form_invalid();
      }
      
      $this->wapo->profile->id = $this->wapo_json->id;
      $this->wapo->profile->new->name = $name;
      $this->wapo->profile->new->email = $email;
      $this->wapo->profile->new->password = $password;
      return parent::form_valid();
    }
  }
  
  class WpSetTangoCardsFormView extends WpWapoFormView {
    protected function form_valid() {
      $tangocards = \Wapo\Profile::get_or_null(array("id"=>$this->request->post->find("tangocards_id")));
      
      if(!$tangocards) {
        $this->set_error("Invalid wapo selected!");
        return $this->form_invalid();
      }
      
      $this->wapo->marketplace = "tangocards";
      $this->wapo->tangocards->id = $tangocards->id;
      return parent::form_valid();
    }
  }
  
  class WpSetFFADeliveryFormView extends WpWapoFormView {
    protected function form_valid() {
      $this->wapo->delivery = "ffa";
      return parent::form_valid();
    }
  }
  
  class WpSetEmailDeliveryFormView extends WpWapoFormView {
    protected function form_valid() {
      $emails = $this->request->post->find("emails", "");
      
      $this->wapo->delivery = "email";
      $this->wapo->emails = explode(",", $emails);
      return parent::form_valid();
    }
  }
  
  class WpSetEmailListDeliveryFormView extends WpWapoFormView {
    protected function form_valid() {
      $emails = $this->request->post->find("emails", "");
      
      $this->wapo->delivery = "email-list";
      $this->wapo->numbers = explode(",", $emails);
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
}