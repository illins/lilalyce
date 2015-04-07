<?php

namespace Wp {
  require_once("blink/base/view.php");
  require_once("blink/base/mail.php");
  require_once("apps/wp/config.php");
  require_once("apps/wp/model.php");
  require_once("apps/wp/form.php");
  
  require_once("apps/blink-user/api.php");
  require_once("apps/wepay/api.php");
  
  require_once("apps/blink-user-role/api.php");
  
  require_once("apps/swiftmailer/api.php");
  
  class StartOverRedirectView extends \Blink\RedirectView {
    public function get_redirect_url() {
      Helper::clear_cookies($this->request->cookie);
      return "/wp/marketplace/";
    }
  }
  
  class SelectProfileTemplateView extends \Blink\TemplateView {
    protected $require_login = true;

    private $distributor = NULL;
    private $profile_list = array();
    private $profile = null;

    protected function get_template() {
      return TemplateConfig::Template("pipeline/profile_list.twig");
    }

    public function get_context_data() {
      $context = parent::get_context_data();
      
      $context['distributor'] = $this->distributor;
      $context['profile'] = $this->profile;
      $context['profile_list'] = $this->profile_list;
      
      return $context;
    }

    public function get() {
      // Get or create their dashboard and get their profile list.
      $this->distributor = Distributor::get_or_create_save(array("user" => $this->request->user->id, "name" => ""), false);
      $this->profile_list = Profile::queryset()->filter(array("distributor" => $this->distributor->id,"status"=>1))->order_by(array("-name"))->fetch();

      // If they don't have a profile list, redirect them to the add profile.
      if(!count($this->profile_list)) {
        \Blink\Messages::info("Please create a profile to continue.");
        return \Blink\HttpResponseRedirect("/wapo/dashboard/profile/first/time/");
      }
      
      // If a profile is set, check that it is theirs.
      if($this->request->cookie->is_set("profile_id")) {
        try {
          $this->profile = Profile::queryset()->get(array("id"=>$this->request->cookie->get("profile_id"),"distributor"=>$this->distributor));
        } catch (\Exception $ex) {
          $this->profile = null;
        }
      }

      return parent::get();
    }

  }
  
  class MarketplaceListView extends \Blink\ListView {
    protected $class = "\Wapo\Promotion";
    
    private $promotioncategory = null;
    private $promotioncategory_list = array();
    
    protected function get_template() {
      return TemplateConfig::Template("frontend/marketplace.twig");
    }
    public function get_queryset() {
      $this->promotioncategory_list = PromotionCategory::queryset()->all();
      
      // Check if there is at least one promotion, if there isn't one, exit.
      if(!count($this->promotioncategory_list)) {
        \Blink\raise500("Site error.");
      }
      
      // Display the promotion categories.
      if($this->request->get->is_set("promotioncategory_id")) {
        $this->promotioncategory = PromotionCategory::get_or_404(array("id"=>$this->request->get->get("promotioncategory_id")));
        $this->queryset = Promotion::queryset()->filter(array("promotioncategory"=>$this->promotioncategory->id,"active"=>1));
      } else {
        $this->queryset = Promotion::queryset()->filter(array("promotioncategory"=>$this->promotioncategory_list[0]->id,"active"=>1));
        $this->promotioncategory = $this->promotioncategory_list[0];
      }
      
      return $queryset;
    }
    
    public function get_context_data() {
      $context = parent::get_context_data();
      
      $promotion = null;
      try {
        $promotion = Promotion::queryset()->get(array("id"=>$this->request->cookie->find("promotion_id")));
      } catch(\Exception $e) {
        ;
      }
      
      $context['promotioncategory'] = $this->promotioncategory;
      $context['promotioncategory_list'] = $this->promotioncategory_list;
      $context['promotion'] = $promotion;
      $context['progress'] = Helper::frontend_progress($this->request, 1);
    }
    
    public function get() {
      // If user is logged in, check that they have a profile set and that it is theirs.
      if($this->request->user) {
        $profile = null;
        if($this->request->get->is_set(("profile_id"))) {
          try {
            $distributor = Distributor::queryset()->get(array("user"=>$this->request->user));
            $profile = Profile::queryset()->get(array("distributor"=>$distributor,"id"=>$this->request->cookie->get("profile_id")));
          } catch (Exception $ex) {
            \Blink\Messages::error("Profile not found. Please select a profile.");
            return \Blink\HttpResponseRedirect("/wapo/profile/");
          }
        } else {
          \Blink\Messages::error("Please select a profile.");
          return \Blink\HttpResponseRedirect("/wapo/profile/");
        }
        
        $this->request->cookie->set("profile_id", $profile->id);
      }
      
      return parent::get();
    }
  }
  
}

