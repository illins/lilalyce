<?php

namespace Wp {
  require_once("1k/base/view.php");
  require_once("1k/base/mail.php");
  require_once("wp/config.php");
  require_once("wapo/model.php");
  require_once("wp/form.php");
  require_once("wp/helper.php");

  require_once("user/api.php");
  require_once("userrole/api.php");
  require_once("wepay/api.php");

  require_once("1k/base/twitter/OAuth.php");
  require_once("1k/base/twitter/twitteroauth.php");
  
  require_once("swiftmailer/api.php");

  // Present a list of profiles. If there is no at least one profile, redirect to add profile.
  class DashboardTemplateView extends \Blink\TemplateView {

    private $distributor = NULL;
    private $profile_list = array();

    public function require_login() {
      return true;
    }

    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/dashboard.twig");
    }

    public function get_context_data() {
      parent::get_context_data();
      
      $this->context['distributor'] = $this->distributor;
      $this->context['profile_list'] = $this->profile_list;
    }

    public function get() {
      // Get or create their dashboard and get their profile list.
      $this->distributor = Distributor::get_or_create_save(array("user" => $this->request->user->id, "name" => ""), false);
      $this->profile_list = Profile::queryset()->filter(array("distributor" => $this->distributor->id,"status"=>1))->order_by(array("-name"))->fetch();

      // If they don't have a profile list, redirect them to the add profile.
      if(!count($this->profile_list)) {
        return \Blink\HttpResponseRedirect("/wapo/dashboard/profile/first/time/");
      }

      return parent::get();
    }

  }
  
  /**
   * Display the first time hints for a new profile.
   */
  class DashboardFirstTimeTemplateView extends \Blink\TemplateView {
    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/profile_first_time.twig");
    }
    
//    public function get() {
//      try {
//        // Check how many profiles they have and skip if they have many.
//        $distributor = Distributor::queryset()->get(array("user"=>$this->request->user->id));
//        $profiles = Profile::queryset()->filter(array("distributor" => $distributor->id))->count();
//        
//        if($profiles) {
//          throw new \Exception;
//        }
//        
//      } catch(\Exception $e) {
//        return \Blink\HttpResponseRedirect("/wapo/dashboard/");
//      }
//      
//      return parent::get();
//    }
  }
  
  class ProfilePreviewTemplateView extends \Blink\TemplateView {
    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("/dashboard/profile_preview.twig");
    }
    
    public function get_context_data() {
      parent::get_context_data();
      
      $distributor = Distributor::get_or_create_save(array("user" => $this->request->user->id));
      $profile = Profile::get_or_404(array("id"=>$this->request->param->param['pk'], "distributor" => $distributor->id));
      $this->context['wapo_list'] = Wapo::queryset()->filter(array("profile"=>$profile->id))->order_by(array("-date_created"))->limit(0,5)->fetch();
      $sociallinks_list = SocialLinks::queryset()->filter(array("profile"=>$profile->id))->fetch();
      $this->context['product_list'] = Product::queryset()->filter(array("profile"=>$profile->id))->fetch();
      $this->context['social_links'] = Helper::social_links($sociallinks_list, $profile, true);
      $this->context['profile'] = $profile;
    }
  }
  
  /**
   * Create an empty profile and redirect to it.
   */
  class ProfileCreateEmptyTemplateView extends \Blink\TemplateView {
    public function get() {
      try {
        $distributor = Distributor::queryset()->get(array("user"=>$this->request->user->id));
        $profile = Profile::create_save(array("distributor"=>$distributor->id,"name"=>"Name / Company Name"), false);
      } catch(\Exception $e) {
        return \Blink\HttpResponseRedirect("/wapo/dashboard/");
      }
      
      \Blink\Messages::success(sprintf("Personalize you profile page for recipient download.", $profile));
      return \Blink\HttpResponseRedirect(sprintf("/wapo/dashboard/profile/%s/", $profile->id));
    }
  }
  
  /**
   * Activate or deactivate a promotion.
   */
  class ProfileChangeStatusRedirectView extends \Blink\RedirectView {
    public function require_login() {
      return true;
    }
    
    public function get_redirect_url() {
      $distributor = Distributor::get_or_404(array("user"=>$this->request->user->id));
      $profile = Profile::get_or_404(array("id"=>$this->request->param->param['pk'],"distributor"=>$distributor->id));
      
      if($profile->status) {
        $profile->status = 0;
        $profile->save(sprintf("Promotion '%s' hidden.", $profile));
      } else {
        $profile->status = 1;
        $profile->save(sprintf("Promotion '%s' activated.", $profile));
      }
      
      $this->redirect_url = "/wapo/dashboard/";
    }
  }
  
  // Display profile details.
  class ProfileDetailView extends \Blink\DetailView {
    public function require_login() {
      return true;
    }
    public function get_class() {
      $this->class = Profile::class_name();
      parent::get_class();
    }
    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/profile.twig");
    }
    
    public function get_context_data() {
      parent::get_context_data();
      $this->context['wapo_list'] = Wapo::queryset()->filter(array("profile"=>$this->object->id))->order_by(array("-date_created"))->limit(0,1)->fetch();
      $sociallinks_list = SocialLinks::queryset()->filter(array("profile"=>$this->object->id))->fetch();
      $this->context['product_list'] = Product::queryset()->filter(array("profile"=>$this->object->id))->fetch();
      $this->context['social_links'] = Helper::social_links($sociallinks_list, $this->object, true);
      $this->context['progress'] = Helper::profile_progress($this->object, $this->request, 0);
    }
  }
  
  class ProfileUpdateView extends \Blink\UpdateView {
    public function require_login() {
      return true;
    }
    public function get_class() {
      $this->class = Profile::class_name();
      parent::get_class();
    }
    public function get_exclude() {
      $this->exclude = array("distributor", "image", "street", "zip", "status","fb_loc_id", "fb_loc_name", "fb_loc_category");
      parent::get_exclude();
    }
    public function get_post_url() {
      $this->post_url = sprintf("/wapo/dashboard/profile/%s/update/", $this->object->id);
    }
    public function get_cancel_url() {
      $this->cancel_url = sprintf("/wapo/dashboard/profile/%s/", $this->object->id);
    }
    public function get_success_url() {
      $this->success_url = sprintf("/wapo/dashboard/profile/%s/update/", $this->object->id);
    }
    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/profile_update.twig");
    }
    
    public function form_valid() {
//      if(!$this->form->get("state") && !$this->form->get("city")) {
//        \Blink\Messages::error("Please enter a state or city for use in Facebook mentions.");
//        return parent::form_invalid();
//      }
      
      return parent::form_valid();
    }
  }
  
  class ProfileUpdateFacebookLocationUpdateView extends \Blink\UpdateView {
    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/profile_update_facebook.twig");
    }
    public function get_class() {
      $this->class = Profile::class_name();
      parent::get_class();
    }
    
    public function get_exclude() {
      $this->exclude = array("id", "distributor", "name", "street", "city", "state", "zip", "image", "latitude", "longitude", "status");
      parent::get_exclude();
    }
    
    public function get_object() {
      $distributor = Distributor::get_or_404(array("user"=>$this->request->user->id));
      $this->object = Profile::queryset()->get(array("distributor"=>$distributor->id,"id"=>$this->request->param->param['pk']));
    }
    
    public function get_post_url() {
      $this->post_url = sprintf("/wapo/dashboard/profile/%s/update/facebook/", $this->object->id);
    }
    
    public function get_success_url() {
      $this->success_url = sprintf("/wapo/dashboard/profile/%s/update/facebook/", $this->object->id);
    }
  }
  
  class ProfilePromotionStartOverRedirectView extends \Blink\RedirectView {
    public function get_redirect_url() {
      Helper::clear_cookies($this->request->cookie);
      $this->redirect_url = sprintf("/wapo/dashboard/profile/%s/marketplace/", $this->request->param->param['pk']);
    }
  }
  
  /**
   * View displays available promotions to be sent using the given profile.
   */
  class ProfilePromotionMarketplace extends \Blink\DetailView {

    public function require_login() {
      return true;
    }

    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/profile_promotion_marketplace.twig");
    }

    public function get_class() {
      $this->class = Profile::class_name();
      parent::get_class();
    }

    public function get_object() {
      $distributor = Distributor::get_or_create_save(array("user" => $this->request->user->id));
      $this->object = Profile::get_or_404(array($this->slug->name => $this->slug->value, "distributor" => $distributor->id));
    }

    public function get_context_data() {
      parent::get_context_data();

      $promotioncategory = NULL;
      $promotion_list = array();
      $promotioncategory_list = PromotionCategory::queryset()->order_by(array("name"))->all();
      
      // Get promotion if set.
      try {
          $promotion = Promotion::queryset()->get(array("id"=>$this->request->cookie->get("promotion_id")));
        } catch(\Exception $e) {
          $promotion = null;
        }
      
      if($this->request->get->is_set("promotioncategory_id")) {
        $promotioncategory = PromotionCategory::get_or_404(array("id" => $this->request->get->get("promotioncategory_id")));
        $promotion_list = Promotion::queryset()->filter(array("promotioncategory" => $promotioncategory->id,"active"=>1))->fetch();
      } else {
        
        $promotioncategory = $promotioncategory_list[0];
        $promotion_list = Promotion::queryset()->filter(array("promotioncategory" => $promotioncategory->id,"active"=>1))->fetch();
      }
      
      $this->context['promotioncategory'] = $promotioncategory;
      $this->context['promotioncategory_list'] = $promotioncategory_list;
      $this->context['promotion_list'] = $promotion_list;
      $this->context['promotion'] = $promotion;
      $this->context['progress'] = Helper::profile_progress($this->object, $this->request, 1);
    }

  }
  
  // Get promotion to display in the right sidebar when sending the promotion.
  class ProfilePromotionXmlTemplateView extends \Blink\TemplateView {
    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/promotion.twig");
    }
    public function get_context_data() {
      parent::get_context_data();
      
      // Check if promotion is set and is valid.
      $promotion = null;
      if($this->request->get->is_set("promotion_id")) {
        try {
          $promotion = Promotion::queryset()->get(array("id"=>$this->request->get->get("promotion_id")));
        } catch(\Exception $e) {
          $promotion = null;
        }
      }
      
      $this->context['promotion'] = $promotion;
    }
  }

  class ProfileImageUpdateFormView extends \Blink\UpdateView {

    public function get_class() {
      $this->class = Profile::class_name();
      parent::get_class();
    }
    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/profile_update_image.twig");
    }

    public function get_success_url() {
      $this->success_url = sprintf("/wapo/dashboard/profile/%s/update/image/", $this->object->id);
    }

    public function get_post_url() {
      $this->post_url = sprintf("/wapo/dashboard/profile/%s/update/image/", $this->object->id);
    }

    public function get_cancel_url() {
      $this->cancel_url = sprintf("/wapo/dashboard/profile/%s/", $this->object->id);
    }
    
    public function get_object() {
      $distributor = Distributor::get_or_404(array("user"=>$this->request->user->id));
      $this->object = Profile::queryset()->get(array("id"=>$this->request->param->param['pk'],"distributor"=>$distributor->id));
    }
    
    public function get_exclude() {
      $this->exclude = array("id", "distributor", "name", "street", "city", "state", "zip", "status");
      parent::get_exclude();
    }
    
    public function get_context_data() {
      parent::get_context_data();
    }
  }

  class ProfilePromotionDeliveryMethodRedirectView extends \Blink\RedirectView {

    public function get_redirect_url() {
      $this->request->cookie->reset("promotion_id", $this->request->get->find("promotion_id"));
      
      if($this->request->cookie->is_set("delivery-method")) {
        switch($this->request->cookie->get("delivery-method")) {
          case "facebook":
            $this->response = \Blink\HttpResponseRedirect(sprintf("/wapo/dashboard/profile/%s/delivery-method/email/", $this->request->param->param['pk']));
            break;
          case "twitter":
            $this->response = \Blink\HttpResponseRedirect(sprintf("/wapo/dashboard/profile/%s/delivery-method/email/", $this->request->param->param['pk']));
            break;
          case "text":
            $this->response = \Blink\HttpResponseRedirect(sprintf("/wapo/dashboard/profile/%s/delivery-method/text/", $this->request->param->param['pk']));
            break;
          default:
            $this->response = \Blink\HttpResponseRedirect(sprintf("/wapo/dashboard/profile/%s/delivery-method/email/", $this->request->param->param['pk']));
            break;
        }
      } else {
        $this->response = \Blink\HttpResponseRedirect(sprintf("/wapo/dashboard/profile/%s/delivery-method/email/", $this->request->param->param['pk']));
      }
    }

  }

  /**
   * View gets info on who we are sending to.
   */
  class ProfilePromotionDeliveryMethodEmailFormView extends \Blink\FormView {

    private $profile = NULL;
    private $promotion = null;

    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/profile_promotion_delivery_method_email.twig");
    }

    public function get_success_url() {
      $this->success_url = sprintf("/wapo/dashboard/profile/%s/profile/", $this->profile->id);
    }

    public function get_form() {
      $forms = new \Blink\FormFields();
      $form_fields = array();
      $data = array();

      if($this->form) {
        return;
      }

      // Get the number of fields we want for the name and their data if available.
      for($i = 1; $i <= 3; $i++) {
        $blank = ($i == 1) ? false : true;
        $form_fields[] = $forms->CharField(array("verbose_name" => "Name", "name" => "email_name$i", "min_length" => 1, "max_length" => 50, "blank" => $blank));
        $form_fields[] = $forms->EmailField(array("verbose_name" => "Email", "name" => "email_email$i", "min_length" => 5, "max_length" => 50, "blank" => $blank));
        $data["email_name$i"]['value'] = $this->request->cookie->find("email_name$i");
        $data["email_email$i"]['value'] = $this->request->cookie->find("email_email$i");
      }

      // If post set the form.
      if($this->request->method == "post") {
        if($this->request->post->is_set("delivery_method")) {
          switch($this->request->post->get("delivery_method")) {
            case "contact-list":
              $this->form = new ContactForm($this->request->post);
              break;
            default:
              $this->form = new \Blink\Form($this->request->post, $form_fields);
              break;
          }
        }
      } else {
        $this->form = new \Blink\Form(array(), $form_fields, $data);
      }
    }

    public function get_context_data() {
      parent::get_context_data();

      $this->context['contact_list'] = Contact::queryset()->filter(array("profile" => $this->request->param->param['pk'], "type" => "e"))->fetch();
      $this->context['email_form'] = $this->form->Form();
      $this->context['profile'] = $this->profile;
      $this->context['promotion'] = $this->promotion;
      $this->context['progress'] = Helper::profile_progress($this->profile, $this->request, 2);
    }

    public function form_invalid() {
      \Blink\Messages::error("There were form errors.");
      return parent::form_invalid();
    }

    public function form_valid() {
      $distributor = Distributor::get_or_create_save(array("user" => $this->request->user->id));
      $this->profile = Profile::get_or_404(array("id" => $this->request->param->param['pk'], "distributor" => $distributor->id));

      // If delivery method is contact list, check that the list exists.
      if($this->form->get("delivery_method") == "contact-list") {
        $contact = Contact::get_or_404(array("id" => $this->form->get("contact_id"), "profile" => $this->profile->id));
        $this->request->cookie->reset("delivery_method", "contact-list");
        $this->request->cookie->reset("contact_id", $contact->id);
      } else {
        $this->request->cookie->set("delivery_method", "email");

        for($i = 1; $i <= 3; $i++) {
          if($this->form->get("email_email$i")) {
            $this->request->cookie->set("email_name$i", $this->form->get("email_name$i"));
            $this->request->cookie->set("email_email$i", $this->form->get("email_email$i"));
          }
        }
      }

      $this->get_success_url();
      return \Blink\HttpResponseRedirect($this->success_url);
    }

    public function get() {
      $distributor = Distributor::get_or_create_save(array("user" => $this->request->user->id));
      $this->profile = Profile::get_or_404(array("id" => $this->request->param->param['pk'], "distributor" => $distributor->id));

      $check = Helper::check_promotion($this->request, "delivery-method", $this->request->param->param['pk']);
      if($check['error']) {
        \Blink\Messages::error($check['message']);
        return \Blink\HttpResponseRedirect($check['url']);
      }
      
      $this->promotion = Promotion::get_or_404(array("id" => $this->request->cookie->get("promotion_id")));
      $this->request->cookie->reset("promotion_id", $this->promotion->id);

      return parent::get();
    }

  }

  //https://github.com/abraham/twitteroauth
  class ProfilePromotionDeliveryMethodTwitterAuthenticateTemplateView extends \Blink\TemplateView {

    public function get_context_data() {
      parent::get_context_data();
    }

    public function get() {
      // App consumer key and consumer secret key.
      $connection = new \TwitterOAuth("4vJdBjL3D8pz9PyochlaEQ", "PPjsOpoKhgHM38geCfxh4B8GnT1kscYudad6BMB5pw");

      // Temporary credentials.
      $request_token = $connection->getRequestToken("https://converge.schuckservices.com/wapo/dashboard/profile/twitter/callback/");

      $this->request->session->set('oauth_token', $request_token['oauth_token']);
      $this->request->session->set('oauth_token_secret', $request_token['oauth_token_secret']);

      switch($connection->http_code) {
        case 200:
          /* Build authorize URL and redirect user to Twitter. */
          return \Blink\HttpResponseRedirect($connection->getAuthorizeURL($request_token['oauth_token']), FALSE);
          break;
        default:
          /* Show notification if something went wrong. */
          echo 'Could not connect to Twitter. Refresh the page or try again later.';
          exit();
      }
    }

  }

  class ProfilePromotionDeliveryMethodTwitterAuthenticateCallbackTemplateView extends \Blink\TemplateView {

    public function get_context_data() {
      parent::get_context_data();
    }

    public function get() {
      if(isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) {
        $_SESSION['oauth_status'] = 'oldtoken';
        $this->request->session->delete('oauth_token');
        $this->request->session->delete('oauth_token_secret');

        return \Blink\HttpResponseRedirect("/wapo/dashboard/profile/twitter/authenticate/");
      }

      $connection = new \TwitterOAuth("4vJdBjL3D8pz9PyochlaEQ", "PPjsOpoKhgHM38geCfxh4B8GnT1kscYudad6BMB5pw", $this->request->session->get('oauth_token'), $this->request->session->get('oauth_token_secret'));

      $access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);
      $this->request->session->set("access_token", $access_token);
      $this->request->session->delete('oauth_token');
      $this->request->session->delete('oauth_token_secret');

      if(200 == $connection->http_code) {
        $account = $connection->get('account/verify_credentials');
        $status = $connection->post('statuses/update', array('status' => 'Testing api status?'));

        var_dump($account);
        exit("We good.");
      } else {
        exit("Something went wrong.");
      }
    }

  }

  class ProfilePromotionDeliveryMethodTwitterTestTemplateView extends \Blink\TemplateView {

    public function get_context_data() {
      parent::get_context_data();
    }

    public function get() {
      $access_token = $this->request->session->get('access_token');

      $connection = new \TwitterOAuth("4vJdBjL3D8pz9PyochlaEQ", "PPjsOpoKhgHM38geCfxh4B8GnT1kscYudad6BMB5pw", $access_token['oauth_token'], $access_token['oauth_token_secret']);

//      $account = $connection->get('account/verify_credentials');
//        $status = $connection->post('statuses/update', array('status' => 'Testing api status?'));

      var_dump("followers----------------------------------");

      $followers = $connection->get("followers/ids");

      var_dump($followers);


      var_dump("friends----------------------------------");
      $friends = $connection->get("friends/list");

      var_dump($friends);

      $status = $connection->post('statuses/update', array('status' => 'Testing @Wp_co api status?'));

      exit();
    }

  }

  /**
   * View gets info on who we are sending to (text).
   */
  class ProfilePromotionDeliveryMethodTwitterFormView extends \Blink\FormView {

    private $profile = NULL;
    private $promotion = null;

    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/profile_promotion_delivery_method_twitter.twig");
    }

    public function get_success_url() {
      $this->success_url = sprintf("/wapo/dashboard/profile/%s/profile/", $this->profile->id);
    }

    public function get_form() {
      $forms = new \Blink\FormFields();
      $form_fields = array();
      $data = array();

      if($this->form) {
        return;
      }

      // Get the number of fields we want for the name and their data if available.
      for($i = 1; $i <= 3; $i++) {
        $blank = ($i == 1) ? false : true;
        $form_fields[] = $forms->CharField(array("verbose_name" => "Name", "name" => "text_name$i", "min_length" => 1, "max_length" => 50, "blank" => $blank));
        $form_fields[] = $forms->EmailField(array("verbose_name" => "Phone #", "name" => "text_phone$i", "max_length" => 50, "blank" => $blank));
        $data["text_name$i"]['value'] = $this->request->cookie->find("text_name$i");
        $data["text_phone$i"]['value'] = $this->request->cookie->find("text_phone$i");
      }

      // If post set the form.
      if($this->request->method == "post") {
        if($this->request->post->is_set("delivery_method")) {
          switch($this->request->post->get("delivery_method")) {
            case "contact-list":
              $this->form = new ContactForm($this->request->post);
              break;
            default:
              $this->form = new \Blink\Form($this->request->post, $form_fields);
              break;
          }
        }
      } else {
        $this->form = new \Blink\Form(array(), $form_fields, $data);
      }
    }

    public function get_context_data() {
      parent::get_context_data();

      $this->context['contact_list'] = Contact::queryset()->filter(array("profile" => $this->request->param->param['pk'], "type" => "p"))->fetch();
      $this->context['text_form'] = $this->form->Form();
      $this->context['profile'] = $this->profile;
      $this->context['promotion'] = $this->promotion;
    }

    public function form_invalid() {
      \Blink\Messages::error("There were form errors.");
      return parent::form_invalid();
    }

    public function form_valid() {
      $distributor = Distributor::get_or_create_save(array("user" => $this->request->user->id));
      $this->profile = Profile::get_or_404(array("id" => $this->request->param->param['pk'], "distributor" => $distributor->id));

      // If delivery method is contact list, check that the list exists.
      if($this->form->get("delivery_method") == "contact-list") {
        $contact = Contact::get_or_404(array("id" => $this->form->get("contact_id"), "profile" => $this->profile->id, "type" => "p"));
        $this->request->cookie->reset("delivery_method", "contact-list");
        $this->request->cookie->reset("contact_id", $contact->id);
      } else {
        $this->request->cookie->set("delivery_method", "text");

        for($i = 1; $i <= 3; $i++) {
          if($this->form->get("text_phone$i")) {
            $this->request->cookie->set("text_name$i", $this->form->get("text_name$i"));
            $this->request->cookie->set("text_phone$i", $this->form->get("text_phone$i"));
          }
        }
      }

      $this->get_success_url();
      return \Blink\HttpResponseRedirect($this->success_url);
    }

    public function get() {
      $distributor = Distributor::get_or_create_save(array("user" => $this->request->user->id));
      $this->profile = Profile::get_or_404(array("id" => $this->request->param->param['pk'], "distributor" => $distributor->id));

      $check = Helper::check_promotion($this->request, "delivery-method", $this->profile);
      if($check['error']) {
        \Blink\Messages::error($check['message']);
        return \Blink\HttpResponseRedirect($check['url']);
      }
      
      $this->promotion = Promotion::get_or_404(array("id" => $this->request->get->get("promotion_id")));
      $this->request->cookie->reset("promotion_id", $this->promotion->id);

      return parent::get();
    }

  }

  /**
   * View gets info on who we are sending to (text).
   */
  class ProfilePromotionDeliveryMethodTextFormView extends \Blink\FormView {

    private $profile = NULL;
    private $promotion = null;

    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/profile_promotion_delivery_method_text.twig");
    }

    public function get_success_url() {
      $this->success_url = sprintf("/wapo/dashboard/profile/%s/profile/", $this->profile->id);
    }

    public function get_form() {
      $forms = new \Blink\FormFields();
      $form_fields = array();
      $data = array();

      if($this->form) {
        return;
      }

      // Get the number of fields we want for the name and their data if available.
      for($i = 1; $i <= 3; $i++) {
        $blank = ($i == 1) ? false : true;
        $form_fields[] = $forms->CharField(array("verbose_name" => "Name", "name" => "text_name$i", "min_length" => 1, "max_length" => 50, "blank" => $blank));
        $form_fields[] = $forms->EmailField(array("verbose_name" => "Phone #", "name" => "text_phone$i", "max_length" => 50, "blank" => $blank));
        $data["text_name$i"]['value'] = $this->request->cookie->find("text_name$i");
        $data["text_phone$i"]['value'] = $this->request->cookie->find("text_phone$i");
      }

      // If post set the form.
      if($this->request->method == "post") {
        if($this->request->post->is_set("delivery_method")) {
          switch($this->request->post->get("delivery_method")) {
            case "contact-list":
              $this->form = new ContactForm($this->request->post);
              break;
            default:
              $this->form = new \Blink\Form($this->request->post, $form_fields);
              break;
          }
        }
      } else {
        $this->form = new \Blink\Form(array(), $form_fields, $data);
      }
    }

    public function get_context_data() {
      parent::get_context_data();

      $this->context['contact_list'] = Contact::queryset()->filter(array("profile" => $this->request->param->param['pk'], "type" => "p"))->fetch();
      $this->context['text_form'] = $this->form->Form();
      $this->context['profile'] = $this->profile;
      $this->context['promotion'] = $this->promotion;
    }

    public function form_invalid() {
      \Blink\Messages::error("There were form errors.");
      return parent::form_invalid();
    }

    public function form_valid() {
      $distributor = Distributor::get_or_create_save(array("user" => $this->request->user->id));
      $this->profile = Profile::get_or_404(array("id" => $this->request->param->param['pk'], "distributor" => $distributor->id));

      // If delivery method is contact list, check that the list exists.
      if($this->form->get("delivery_method") == "contact-list") {
        $contact = Contact::get_or_404(array("id" => $this->form->get("contact_id"), "profile" => $this->profile->id, "type" => "p"));
        $this->request->cookie->reset("delivery_method", "contact-list");
        $this->request->cookie->reset("contact_id", $contact->id);
      } else {
        $this->request->cookie->set("delivery_method", "text");

        for($i = 1; $i <= 3; $i++) {
          if($this->form->get("text_phone$i")) {
            $this->request->cookie->set("text_name$i", $this->form->get("text_name$i"));
            $this->request->cookie->set("text_phone$i", $this->form->get("text_phone$i"));
          }
        }
      }

      $this->get_success_url();
      return \Blink\HttpResponseRedirect($this->success_url);
    }

    public function get() {
      $distributor = Distributor::get_or_create_save(array("user" => $this->request->user->id));
      $this->profile = Profile::get_or_404(array("id" => $this->request->param->param['pk'], "distributor" => $distributor->id));

      $check = Helper::check_promotion($this->request, "delivery-method", $this->request->param->param['pk']);
      if($check['error']) {
        \Blink\Messages::error($check['message']);
        return \Blink\HttpResponseRedirect($check['url']);
      }
      
      $this->promotion = Promotion::get_or_404(array("id" => $this->request->cookie->get("promotion_id")));
      $this->request->cookie->reset("promotion_id", $this->promotion->id);

      return parent::get();
    }

  }

  /**
   * View gets info on who we are sending to.
   */
  class ProfilePromotionDeliveryMethodFacebookFormView extends \Blink\FormView {

    private $profile = NULL;

    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/profile_promotion_delivery_method_facebook.twig");
    }

    public function get_success_url() {
      $this->success_url = sprintf("/wapo/dashboard/profile/%s/profile/", $this->profile->id);
    }

    public function get_form() {
      $forms = new \Blink\FormFields();
      $form_fields = array();
      $data = array();

      if($this->form) {
        return;
      }

      $data['facebook_ids'] = array();
      $data['facebook_ids']['value'] = $this->request->cookie->find("facebook_ids", "");
      $data['delivery_method'] = array();
      $data['delivery_method']['value'] = "facebook";

      $form_fields[] = $forms->HiddenCharField(array("name" => "facebook_ids", "max_length" => 1000, "blank" => false));
      $form_fields[] = $forms->HiddenCharField(array("name" => "delivery_method", "max_length" => 1000, "blank" => false));

      // If post set the form.
      if($this->request->method == "post") {
        $this->form = new \Blink\Form($this->request->post, $form_fields);
      } else {
        $this->form = new \Blink\Form(array(), $form_fields, $data);
      }
    }

    public function get_context_data() {
      parent::get_context_data();

      $this->context['facebook_form'] = $this->form->Form();
      $this->context['profile'] = $this->profile;
      $this->context['promotion'] = $this->promotion;
      $this->context['progress'] = Helper::profile_progress($this->profile, $this->request, 2);
    }

    public function form_invalid() {
      \Blink\Messages::error("There were form errors.");
      return parent::form_invalid();
    }

    public function form_valid() {
      $distributor = Distributor::get_or_create_save(array("user" => $this->request->user->id));
      $this->profile = Profile::get_or_404(array("id" => $this->request->param->param['pk'], "distributor" => $distributor->id));

      // If delivery method is contact list, check that the list exists.
      if($this->form->get("delivery_method") == "facebook") {
        $this->request->cookie->set("delivery_method", "facebook");

        $this->request->cookie->set("facebook_ids", $this->form->get("facebook_ids"));
      }

      $this->get_success_url();
      return \Blink\HttpResponseRedirect($this->success_url);
    }

    public function get() {
      $distributor = Distributor::get_or_create_save(array("user" => $this->request->user->id));
      $this->profile = Profile::get_or_404(array("id" => $this->request->param->param['pk'], "distributor" => $distributor->id));

      $check = Helper::check_promotion($this->request, "delivery-method", $this->request->param->param['pk']);
      if($check['error']) {
        \Blink\Messages::error($check['message']);
        return \Blink\HttpResponseRedirect($check['url']);
      }
      
      $this->promotion = Promotion::get_or_404(array("id" => $this->request->cookie->get("promotion_id")));
      $this->request->cookie->reset("promotion_id", $this->promotion->id);

      return parent::get();
    }

  }

  class ProfilePromotionEmailSendRedirectView extends \Blink\RedirectView {

    public function get_redirect_url() {
      // Get the profile and promotion.
      $distributor = Distributor::get_or_create_save(array("user" => $this->request->user->id));
      $profile = Profile::get_or_404(array("id" => $this->request->param->param['pk'], "distributor" => $distributor->id));
      $promotion = Promotion::get_or_404(array("id" => $this->request->cookie->get("promotion_id")));

      if($this->request->cookie->is_set("delivery_method")) {
        // If delivery method is email or 
        if(in_array($this->request->cookie->get("delivery_method"), array("email", "contact-list"))) {
          $create_wapo = array(
                  "profile" => $profile->id,
                  "promotion" => $promotion->id,
                  "delivery_method" => "email",
                  "delivery_message" => $this->request->cookie->get("delivery_message"),
                  "delivery_date" => $this->request->cookie->get("delivery_date"),
                  "expiring_date" => $this->request->cookie->get("expiring_date"),
                  "checkout_id" => $this->request->session->get("checkout_id")
          );

          // Setup mail.
          $mail = \Swift\Api::Message();
          $mail->setSubject(sprintf("%s has sent you a Wp (Promo)", $profile));
          $mail->setFrom(array("send@wapo.co"=>"Wp.co"));

          // Create the send entry.
          $wapo = Wapo::create_save($create_wapo, false);

          $contact_list = array(); // Will hold the emails.
          // If emails from manual entry, get them.
          if($this->request->cookie->get("delivery_method") == "email") {
            for($i = 1; $i <= 3; $i++) {
              if($this->request->cookie->is_set("email_email$i")) {
                $contact_list[] = array(
                        "name" => $this->request->cookie->get("email_name$i"),
                        "contact" => $this->request->cookie->get("email_email$i")
                );
              }
            }
          } else {/* If from list, check that the list is the users, then gather the emails */
            $contact = Contact::get_or_404(array("id" => $this->request->cookie->get("contact_id"), "profile" => $profile->id));
            $contactitem_list = ContactItem::queryset()->filter(array("contact" => $contact->id))->fetch();

            foreach($contactitem_list as $item) {
              $contact_list[] = array(
                      "name" => $item->name,
                      "contact" => $item->item
              );
            }
          }

          // Attempt to send the email to each address.
          $i = 0;
          $all_sent = true;
          foreach($contact_list as $cont) {
            $mail_rec = clone $mail;
            $mail_rec->setTo(array($cont['contact']=>$cont['name']));

            $create_recipient = array(
                    "wapo" => $wapo->id,
                    "name" => $cont['name'],
                    "contact" => $cont['contact'],
                    "code" => substr(md5(date("H:i:s") . $cont['contact']), 0, 6),
                    "confirm" => substr(md5(date("H:i:s") . $i++), 0, 6),
                    "sent" => 1
            );
            //str_pad(0, 5, "0")
            
            // Get the email to send.
            $message = \Blink\render_get(array("profile" => $profile, "promotion" => $promotion, "wapo" => $wapo, "recipient" => $create_recipient,"url"=>  \Blink\ConfigSite::$Site), ConfigTemplate::Template("dashboard/profile_promotion_email.twig"));
            $mail_rec->setBody($message, "text/html");

            $result = \Swift\Api::Send($mail_rec);
            if($result !== true) {
              $all_sent = false;
              $create_recipient['sent'] = 0;
            }

            // Create the recipient entry with the result.
            $pr = WapoRecipient::create_save($create_recipient, false);
          }

          if(!$all_sent) {
            \Blink\Messages::info("Some wapo(s) were not sent. Please log in to view status.");
          } else {
            \Blink\Messages::success("Your wapo has been sent.");
          }
          
          $url = sprintf("/wapo/dashboard/profile/%s/confirm/%s/", $profile->id, $wapo->id);
          $this->response = \Blink\HttpResponseRedirect($url);
        }
      } else {
        $this->response = \Blink\HttpResponseRedirect(sprintf("/wapo/dashboard/profile/%s/delivery-method/", $profile->id));
      }
    }

  }
  
  class ProfilePromotionSendFacebookPostTemplateView extends \Blink\TemplateView {
    public function get() {
      // Check if distributor has been created.
      $distributor = Distributor::queryset()->get(array("user" => $this->request->user->id), false);

      try {
        $profile = Profile::queryset()->get(array("id"=>$this->request->param->param['pk'],"distributor"=>$distributor->id));
        $promotion = Promotion::queryset()->get(array("id" => $this->request->cookie->get("promotion_id")));
      } catch(\Exception $e) {
        return \Blink\HttpResponse(array("result"=>"no","message"=>"Profile/Promotion error."), \Blink\View::CONTENT_JSON);
      }
      
      $create_wapo = array(
              "profile" => $profile->id,
              "promotion" => $promotion->id,
              "delivery_method" => "facebook",
              "delivery_message" => $this->request->cookie->get("delivery_message"),
              "delivery_date" => date("m/d/Y"),
              "expiring_date" => $this->request->cookie->get("expiring_date"),
              "checkout_id" => $this->request->session->get("checkout_id")
      );

      $wapo = Wapo::create_save($create_wapo, false);
      
      $contact_list = explode(",", $this->request->cookie->get("facebook_ids"));
      // Attempt to send the email to each address.
      foreach($contact_list as $cont) {
        $create_recipient = array(
                "wapo" => $wapo->id,
                "contact" => trim($cont),
                "code" => substr(md5(date("H:i:s") . $cont), 0, 6),
                "confirm" => substr(md5(date("H:i:s") . $wapo->id), 0, 6),
                "sent" => 1
        );

        // Create the recipient entry with the result.
        WapoRecipient::create_save($create_recipient, false);
      }
      
      $post = \Blink\render_get(array("profile" => $profile, "promotion" => $promotion, "wapo" => $wapo, "code" => $create_recipient['code'],"url"=>\Blink\ConfigSite::$Site), ConfigTemplate::Template("promotion_facebook.twig"));
      
      $response = array(
              "result" => "yes",
              "loggedin" => "yes",
              "facebook_ids" => $this->request->cookie->get("facebook_ids"),
              "location" => $profile->fb_loc_id,
              "name" => $promotion->name,
              "caption" => sprintf("%s has sent you a Wp", $profile->name),
              "description" => $wapo->delivery_message,
              "picture" => $promotion->icon->url,
              "link" => sprintf("%s/wapo/download/?code=%s", \Blink\ConfigSite::$Site, $create_recipient['code']),
              "post" => $post,
              "url" => sprintf("/wapo/dashboard/profile/%s/confirm/%s/", $profile->id, $wapo->id)
      );
      
      return \Blink\HttpResponse($response, \Blink\View::CONTENT_JSON);
    }
  }

  class ProfilePromotionFacebookSendTemplateView extends \Blink\TemplateView {

    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/profile_promotion_facebook_send.twig");
    }

    public function get_context_data() {
      parent::get_context_data();

      $distributor = Distributor::get_or_create_save(array("user" => $this->request->user->id));
      $profile = Profile::get_or_404(array("id" => $this->request->param->param['pk'], "distributor" => $distributor->id));
      $promotion = Promotion::get_or_404(array("id" => $this->request->cookie->get("promotion_id")));

      $this->context['url'] = sprintf("/wapo/dashboard/profile/%s/send/facebook/post/", $profile->id);
      $this->context['delivery_message'] = $promotion->name . ": " . $this->request->cookie->get("delivery_message");
      $this->context['facebook_ids'] = $this->request->cookie->get("facebook_ids");
      $this->context['progress'] = Helper::profile_progress($profile, $this->request, 4);
    }

  }

  class ProfilePromotionFacebookSaveRedirectView extends \Blink\RedirectView {

    public function get_redirect_url() {
      // Get the profile and promotion.
      $distributor = Distributor::get_or_create_save(array("user" => $this->request->user->id));
      $profile = Profile::get_or_404(array("id" => $this->request->param->param['pk'], "distributor" => $distributor->id));
      $promotion = Promotion::get_or_404(array("id" => $this->request->cookie->get("promotion_id")));

      // Create the send entry.
      $create_wapo = array(
              "profile" => $profile->id,
              "promotion" => $promotion->id,
              "delivery_method" => "facebook",
              "delivery_message" => $this->request->cookie->get("delivery_message"),
              "delivery_date" => $this->request->cookie->get("delivery_date"),
                  "expiring_date" => $this->request->cookie->get("expiring_date"),
      );
      $wapo = Wapo::create_save($create_wapo, false);

      $contact_list = explode(",", $this->request->cookie->get("facebook_ids"));

      // Attempt to send the email to each address.
      foreach($contact_list as $cont) {
        $create_recipient = array(
                "wapo" => $wapo->id,
                "contact" => trim($cont),
                "code" => substr(md5(date("H:i:s") . $cont), 0, 6),
                "confirm" => substr(md5(date("H:i:s") . $wapo->id), 0, 6),
                "sent" => 1
        );

        // Create the recipient entry with the result.
        WapoRecipient::create_save($create_recipient, false);
      }

      \Blink\Messages::success("Your wapo has been sent.");
      $url = sprintf("/wapo/dashboard/profile/%s/confirm/%s/", $profile->id, $wapo->id);
      $this->response = \Blink\HttpResponseRedirect($url);
    }

  }

  class ProfilePromotionTwitterSendRedirectView extends \Blink\RedirectView {

    public function get_redirect_url() {
      // Get the profile and promotion.
      $distributor = Distributor::get_or_create_save(array("user" => $this->request->user->id));
      $profile = Profile::get_or_404(array("id" => $this->request->param->param['pk'], "distributor" => $distributor->id));
      $promotion = Promotion::get_or_404(array("id" => $this->request->cookie->get("promotion_id")));

      if($this->request->cookie->is_set("delivery_method")) {
        // If delivery method is email or 
        if(in_array($this->request->cookie->get("delivery_method"), array("email", "contact-list"))) {
          $create_wapo = array(
                  "profile" => $profile->id,
                  "promotion" => $promotion->id,
                  "delivery_method" => "email",
                  "delivery_message" => $this->request->cookie->get("delivery_message"),
                  "delivery_date" => $this->request->cookie->get("delivery_date"),
                  "expiring_date" => $this->request->cookie->get("expiring_date"),
          );

          // Get the mail object.
          $mail = new \Blink\Mail();
          $mail->subject = sprintf("%s has sent you a Wp", $profile);
          $mail->from = "Wp.co <snjoroge@schuckservices.com>";

          // Create the send entry.
          $wapo = Wapo::create_save($create_wapo, false);

          $contact_list = array(); // Will hold the emails.
          // If emails from manual entry, get them.
          if($this->request->cookie->get("delivery_method") == "email") {
            for($i = 1; $i <= 3; $i++) {
              if($this->request->cookie->is_set("email_email$i")) {
                $contact_list[] = sprintf("%s <%s>", $this->request->cookie->get("email_name$i"), $this->request->cookie->get("email_email$i"));
              }
            }
          } else {/* If from list, check that the list is the users, then gather the emails */
            $contact = Contact::get_or_404(array("id" => $this->request->cookie->get("contact_id"), "profile" => $profile->id));
            $contactitem_list = ContactItem::queryset()->filter(array("contact" => $contact->id))->fetch();

            foreach($contactitem_list as $item) {
              $contact_list[] = sprintf("%s <%s>", $item->name, $item->item);
            }
          }

          // Attempt to send the email to each address.
          foreach($contact_list as $cont) {
            $mail->to = array($cont);

            $create_recipient = array(
                    "wapo" => $wapo->id,
                    "contact" => $cont,
                    "code" => substr(md5(date("H:i:s") . $cont), 0, 6),
                    "confirm" => substr(md5(date("H:i:s") . $i), 0, 6),
                    "sent" => 1
            );

            // Get the email to send.
            $mail->message = \Blink\render_get(array("profile" => $profile, "promotion" => $promotion, "wapo" => $wapo, "code" => $create_recipient['code'],"url"=>\Blink\ConfigSite::$Site), ConfigTemplate::Template("dashboard/profile_promotion_email.twig"));

            if(!$mail->send()) {
              $create_recipient['sent'] = 0;
            }

            // Create the recipient entry with the result.
            WapoRecipient::create_save($create_recipient, false);
          }

          \Blink\Messages::success("Your wapo has been sent.");
          $url = sprintf("/wapo/dashboard/profile/%s/confirm/%s/", $profile->id, $wapo->id);
          $this->response = \Blink\HttpResponseRedirect($url);
        }
      } else {
        $this->response = \Blink\HttpResponseRedirect(sprintf("/wapo/dashboard/profile/%s/delivery-method/", $profile->id));
      }
    }

  }

  /**
   * Display page showing the profile they chose and give them the option for the delivery message.
   */
  class ProfilePromotionProfileFormView extends \Blink\FormView {

    private $profile = null;
    private $promotion = null;

    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/profile_promotion_profile.twig");
    }

    public function get_success_url() {
      $this->success_url = sprintf("/wapo/dashboard/profile/%s/checkout/", $this->profile->id);
    }

    public function get_form() {
      if($this->form) {
        return;
      }

      $fields = new \Blink\FormFields();
      $form_fields = array();
      $data = array();

      $form_fields[] = $fields->TextField(array("verbose_name" => "Delivery Message", "name" => "delivery_message", "blank" => true));
      $form_fields[] = $fields->DateField(array("name" => "delivery_date", "blank" => true, "format" => "m/d/Y"));
      $form_fields[] = $fields->DateField(array("name" => "expiring_date", "blank" => true, "format" => "m/d/Y"));
      $data["delivery_message"] = array("value"=>$this->request->cookie->find("delivery_message"));
      $data["delivery_date"] = array("value"=>$this->request->cookie->find("delivery_date"));
      $data["expiring_date"] = array("value"=>$this->request->cookie->find("expiring_date"));

      // If post set the form.
      if($this->request->method == "post") {
        $this->form = new \Blink\Form($this->request->post, $form_fields);
      } else {
        $this->form = new \Blink\Form(array(), $form_fields, $data);
      }
    }

    public function get_context_data() {
      parent::get_context_data();
      $this->context['form'] = $this->form->Form();
      $this->context['profile'] = $this->profile;
      $this->context['promotion'] = $this->promotion;
      $this->context['progress'] = Helper::profile_progress($this->profile, $this->request, 3);
    }

    public function form_valid() {
      $distributor = Distributor::get_or_create_save(array("user" => $this->request->user->id));
      $this->profile = Profile::get_or_404(array("id" => $this->request->param->param['pk'], "distributor" => $distributor->id));
      $this->request->cookie->set("delivery_message", $this->form->get("delivery_message"));
      $this->request->cookie->set("delivery_date", $this->form->get("delivery_date"));
      $this->request->cookie->set("expiring_date", $this->form->get("expiring_date"));

      $this->get_success_url();
      return \Blink\HttpResponseRedirect($this->success_url);
    }

    public function get() {
      $distributor = Distributor::get_or_create_save(array("user" => $this->request->user->id));
      $this->profile = Profile::get_or_404(array("id" => $this->request->param->param['pk'], "distributor" => $distributor->id));

      $check = Helper::check_promotion($this->request, "profile", $this->request->param->param['pk']);
      if($check['error']) {
        \Blink\Messages::error($check['message']);
        return \Blink\HttpResponseRedirect($check['url']);
      }
      
      $this->promotion = Promotion::get_or_404(array("id" => $this->request->cookie->get("promotion_id")));
      $this->request->cookie->reset("promotion_id", $this->promotion->id);
      
      return parent::get();
    }

  }

  /**
   * Display page showing the profile they chose and give them the option for the delivery message.
   */
  class ProfilePromotionCheckoutTemplateView extends \Blink\TemplateView {

    private $profile = null;
    private $promotion = null;
    
    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/profile_promotion_checkout.twig");
    }

    public function get_success_url() {
      $this->success_url = sprintf("/wapo/dashboard/profile/%s/checkout/", $this->profile->id);
    }

    public function get_context_data() {
      parent::get_context_data();
      $this->context['profile'] = $this->profile;

      $this->context['delivery_method'] = $this->request->cookie->get("delivery_method");
      $this->context['promotion'] = $this->promotion;
      
      $delivery_method = $this->request->cookie->get("delivery_method");
      $contact_list = array();
      $facebook_ids = "";
      $contact = null;
      if($delivery_method == "email") {
        for($i = 1; $i <= 3; $i++) {
          if($this->request->cookie->is_set("email_email$i")) {
            $contact_list[] = array("name" => $this->request->cookie->find("email_name$i"), "email" => $this->request->cookie->get("email_email$i"));
          }
        }
      } else if($delivery_method == "contact-list") {
        $contact = Contact::get_or_404(array("id"=>$this->request->cookie->get("contact_id")));
      } else if($delivery_method == "text") {
        for($i = 1; $i <= 3; $i++) {
          if($this->request->cookie->is_set("text_phone$i")) {
            $contact_list[] = array("name"=>$this->request->cookie->find("text_name$i"), "email" => $this->request->cookie->get("text_phone$i"));
          }
        }
      } else if($delivery_method == "facebook") {
        $facebook_ids = $this->request->cookie->get("facebook_ids");
      } else if($delivery_method == "twitter") {
        
      }
      
      $this->context["delivery_message"] = $this->request->cookie->find("delivery_message");
      $this->context["delivery_date"] = $this->request->cookie->find("delivery_date");
      $this->context["expiring_date"] = $this->request->cookie->find("expiring_date");
      $this->context["contact_list"] = $contact_list;
      $this->context["facebook_ids"] = $facebook_ids;
      $this->context['progress'] = Helper::profile_progress($this->profile, $this->request, 4);
    }

    public function get() {
      $distributor = Distributor::get_or_create_save(array("user" => $this->request->user->id));
      $this->profile = Profile::get_or_404(array("id" => $this->request->param->param['pk'], "distributor" => $distributor->id));

      $check = Helper::check_promotion($this->request, "checkout", $this->request->param->param['pk']);
      if($check['error']) {
        \Blink\Messages::error($check['message']);
        return \Blink\HttpResponseRedirect($check['url']);
      }
      
      $this->promotion = Promotion::get_or_404(array("id" => $this->request->cookie->get("promotion_id")));
      $this->request->cookie->reset("promotion_id", $this->promotion->id);

      return parent::get();
    }

  }
  
  class ProfilePromotionCheckoutIdTemplateView extends \Blink\TemplateView {
    
    public function get() {
      $distributor = Distributor::get_or_create_save(array("user" => $this->request->user->id));
      $profile = Profile::get_or_404(array("id" => $this->request->param->param['pk'], "distributor" => $distributor->id));
      
      $response = new \stdClass();
      $promotion = null;
      
      // Get the promotion price.
      try {
        $promotion = Promotion::queryset()->get(array("id"=>$this->request->cookie->find("promotion_id")));
      } catch(\Exception $e) {
        $response->result = "no";
        $response->redirect = sprintf("/wapo/dashboard/profile/%s/marketplace/", $profile->id);
        return \Blink\HttpResponse($response, \Blink\View::CONTENT_JSON);
      }
      
      $count = 0;
      if($this->request->cookie->get("delivery_method") == "email") {
        for($i = 0; $i < 3; $i++) {
          if($this->request->cookie->find("email_email$i")) {
            $count++;
          }
        }
      } else if($this->request->cookie->get("delivery_method") == "facebook") {
        if($this->request->cookie->find("facebook_ids")) {
          $count = count(explode(",", $this->request->cookie->find("facebook_ids")));
        }
      }
      
      if(!$count) {
        $response->result = "no";
        $response->redirect = sprintf("/wapo/dashboard/profile/%s/delivery-method/", $profile->id);
        return \Blink\HttpResponse($response, \Blink\View::CONTENT_JSON);
      }
      
      $cost = $count * $promotion->price;
      try {
        $response = \WePay\Api::checkout_create("00", $cost, "YOYO", "iframe", sprintf("%s/wapo/dashboard/profile/%s/send/", \Blink\ConfigSite::$Site, $profile->id));
        $response->result = "yes";
      } catch(\Exception $e) {
        $response->result = "no";
      }
      
      if($response->checkout_id) {
        $this->request->session->set("checkout_id", $response->checkout_id);
      }
      
      return \Blink\HttpResponse($response, \Blink\View::CONTENT_JSON);
    }
  }
  
  class ProfilePromotionSendRedirectView extends \Blink\RedirectView {

    public function get_redirect_url() {
      $distributor = Distributor::get_or_create_save(array("user" => $this->request->user->id));
      $profile = Profile::get_or_404(array("id" => $this->request->param->param['pk'], "distributor" => $distributor->id));
      
      // Check that we set the promotion.
      if($this->request->get->is_set("promotion_id")) {
        $this->request->cookie->reset("promotion_id", $this->request->get->get("promotion_id"));
      }
      
      switch($this->request->cookie->find("delivery_method")) {
        case "facebook":
          
          $this->redirect_url = sprintf("/wapo/dashboard/profile/%s/send/facebook/?%s", $profile->id, $this->request->query_string);
          break;
        case "twitter":
          $this->redirect_url = sprintf("/wapo/dashboard/profile/%s/send/twitter/?%s", $profile->id, $this->request->query_string);
          break;
        case "text":
          $this->redirect_url = sprintf("/wapo/dashboard/profile/%s/send/text/?%s", $profile->id, $this->request->query_string);
          break;
        default:
          $this->redirect_url = sprintf("/wapo/dashboard/profile/%s/send/email/?%s", $profile->id, $this->request->query_string);
          break;
      }
    }

  }

  /**
   * Display page showing the receipt.
   */
  class ProfilePromotionConfirmTemplateView extends \Blink\TemplateView {

    private $profile = null;
    private $wapo = null;

    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/profile_promotion_confirmation.twig");
    }

    public function get_context_data() {
      parent::get_context_data();
      
      $checkout = \WePay\Api::checkout($this->wapo->checkout_id);
      
      Helper::clear_cookies($this->request->cookie);
      
      $this->context['wapo'] = $this->wapo;
      $this->context['profile'] = $this->profile;
      $this->context['recipient_list'] = WapoRecipient::queryset()->filter(array("wapo" => $this->wapo->id));
      
      $this->context['checkout'] = $checkout;
      $this->context['progress'] = Helper::profile_progress($this->profile, $this->request, 5);
    }

    public function get() {
      // Check that the promotion send is theirs.
      $distributor = Distributor::get_or_create_save(array("user" => $this->request->user->id));
      $this->profile = Profile::get_or_404(array("id" => $this->request->param->param['pk'], "distributor" => $distributor->id));
      $this->wapo = Wapo::get_or_404(array("id" => $this->request->param->param['wapo_id'],"profile"=>$this->profile->id));
      
      return parent::get();
    }

  }
  
  class SocialLinksListView extends \Blink\ListView {
    
    public function get_class() {
      $this->class = SocialLinks::class_name();
      parent::get_class();
    }
    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/sociallinks_list.twig");;
    }
    public function get_context_data() {
      parent::get_context_data();
      
      $distributor = Distributor::queryset()->get(array("user"=>$this->request->user->id));
      $profile = Profile::queryset()->get(array("id"=>$this->request->param->param['profile_id'], "distributor"=>$distributor->id));
      
      // Add icons.
      for($i = 0; $i < count($this->context['sociallinks_list']); $i++) {
        if(strpos($this->context['sociallinks_list'][$i]->link, "twitter") !== false) {
          $this->context['sociallinks_list'][$i]->class = "fa fa-twitter fa-2x";
        } else if(strpos($this->context['sociallinks_list'][$i]->link, "facebook") !== false) {
          $this->context['sociallinks_list'][$i]->class = "fa fa-facebook fa-2x";
        } else if(strpos($this->context['sociallinks_list'][$i]->link, "pintrest") !== false) {
          $this->context['sociallinks_list'][$i]->class = "fa fa-pintrest fa-2x";
        } else if(strpos($this->context['sociallinks_list'][$i]->link, "tumblr") !== false) {
          $this->context['sociallinks_list'][$i]->class = "fa fa-tumblr fa-2x";
        } else if(strpos($this->context['sociallinks_list'][$i]->link, "flickr") !== false) {
          $this->context['sociallinks_list'][$i]->class = "fa fa-flickr fa-2x";
        } else if(strpos($this->context['sociallinks_list'][$i]->link, "instagram") !== false) {
          $this->context['sociallinks_list'][$i]->class = "fa fa-instagram fa-2x";
        } else if(strpos($this->context['sociallinks_list'][$i]->link, "youtube") !== false) {
          $this->context['sociallinks_list'][$i]->class = "fa fa-youtube fa-2x";
        } else if(strpos($this->context['sociallinks_list'][$i]->link, "vimeo") !== false) {
          $this->context['sociallinks_list'][$i]->class = "fa fa-vimeo-square fa-2x";
        } else {
          $this->context['sociallinks_list'][$i]->class = "fa fa-square fa-2x";
        }
      }
      
      $this->context['profile'] = $profile;
    }
  }
  
  class SocialLinksCreateView extends \Blink\CreateView {
    private $profile = NULL;
      
    public function get_class() {
      $this->class = SocialLinks::class_name();
      parent::get_class();
    }
    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/sociallinks_create.twig");
    }
    public function get_exclude() {
      $this->exclude = array("profile");
      parent::get_exclude();
    }
    public function get_post_url() {
      $this->post_url = sprintf("/wapo/dashboard/profile/%s/social/add/", $this->request->param->param['profile_id']);
    }
    public function get_cancel_url() {
      $this->cancel_url = sprintf("/wapo/dashboard/profile/%s/social/", $this->request->param->param['profile_id']);
    }
    public function get_success_url() {
      $this->success_url = sprintf("/wapo/dashboard/profile/%s/social/", $this->request->param->param['profile_id']);
    }
    public function form_valid() {
      $create = array(
              "profile" => $this->request->param->param["profile_id"],
              "name" => $this->form->get("name"),
              "link" => $this->form->get("link")
      );
      $this->object = SocialLinks::get_or_create($create);
      $this->object->save();
            
      $this->get_success_url();
      
      return \Blink\HttpResponseRedirect($this->success_url);
    }
    public function get_context_data() {
      parent::get_context_data();
      $this->context['profile'] = $this->profile;
    }
    
    public function get() {
      $distributor = Distributor::get_or_404(array("user"=>$this->request->user->id));
      $this->profile = Profile::get_or_404(array("id"=>$this->request->param->param['profile_id'],"distributor"=>$distributor->id));
      
      return parent::get();
    }
  }
  
  class SocialLinksDeleteView extends \Blink\DeleteView {
    private $profile = null;
    
    public function get_class() {
      $this->class = SocialLinks::class_name();
      parent::get_class();
    }
    public function get_object() {
      $distributor = Distributor::get_or_404(array("user"=>$this->request->user->id));
      $this->profile = Profile::get_or_404(array("id"=>$this->request->param->param['profile_id'],"distributor"=>$distributor->id));
      $this->object = SocialLinks::get_or_404(array("id"=>$this->request->param->param['pk'],"profile"=>$this->profile->id));
    }
    
    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/sociallinks_delete.twig");
    }
    public function get_success_url() {
      $this->success_url = sprintf("/wapo/dashboard/profile/%s/social/", $this->object->profile->id);
    }
    public function get_cancel_url() {
      $this->cancel_url = sprintf("/wapo/dashboard/profile/%s/social/", $this->object->profile->id);
    }
    
    public function get_context_data() {
      parent::get_context_data();
      $this->context['profile'] = $this->profile;
    }
  }
  
  /*Products*/
  class ProductCreateView extends \Blink\CreateView {
    private $profile = NULL;
      
    public function get_class() {
      $this->class = Product::class_name();
      parent::get_class();
    }
    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/product_create.twig");
    }
    public function get_exclude() {
      $this->exclude = array("profile", "description", "photo_url",  "status");
      parent::get_exclude();
    }
    public function get_post_url() {
      $this->post_url = sprintf("/wapo/dashboard/profile/%s/product/add/", $this->request->param->param['profile_id']);
    }
    public function get_cancel_url() {
      $this->cancel_url = sprintf("/wapo/dashboard/profile/%s/product/", $this->request->param->param['profile_id']);
    }
    public function get_success_url() {
      $this->success_url = sprintf("/wapo/dashboard/profile/%s/product/", $this->request->param->param['profile_id']);
    }
    public function form_valid() {
      Profile::get_or_404(array("id"=>$this->request->param->param["profile_id"]));
      
      $create = array(
              "profile" => $this->request->param->param['profile_id'],
              "name" => $this->form->get("name"),
              "description" => "",
              "url" => $this->form->get("url"),
              "photo_url" => "",
              "photo" => $this->form->get("photo"),
              "status" => 1
      );
      $this->object = Product::get_or_create($create);
      $this->object->save();
            
      $this->get_success_url();
      
      return \Blink\HttpResponseRedirect($this->success_url);
    }
    public function get_context_data() {
      parent::get_context_data();
      $this->context['profile'] = $this->profile;
    }
    
    public function get() {
      $distributor = Distributor::get_or_404(array("user"=>$this->request->user->id));
      $this->profile = Profile::get_or_404(array("id"=>$this->request->param->param['profile_id'],"distributor"=>$distributor->id));
      
      return parent::get();
    }
  }
  
  class ProductListView extends \Blink\ListView {
   private $profile = null;
   
    public function require_login() {
      return TRUE;
    }
    public function get_class() {
      $this->class = Product::class_name();
      parent::get_class();
    }
    public function get_object_list() {
      $distributor = Distributor::get_or_404(array("user"=>$this->request->user->id));
      $this->profile = Profile::get_or_404(array("id"=>$this->request->param->param['profile_id'],"distributor"=>$distributor->id));
      $this->object_list = Product::queryset()->filter(array("profile"=>$this->profile->id))->order_by(array("name"))->fetch();
    }
    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/product_list.twig");
    }
    public function get_context_data() {
      parent::get_context_data();
      $this->context['profile'] = $this->profile;
    }
  }
  
  class ProductUpdateView extends \Blink\UpdateView {
   private $profile = null;
   
    public function require_login() {
      return true;
    }
    public function get_class() {
      $this->class = Product::class_name();
      parent::get_class();
    }
    public function get_exclude() {
      $this->exclude = array("profile", "description", "photo_url", "status");
    }
    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/product_update.twig");
    }
    public function get_post_url() {
      $this->post_url = sprintf("/wapo/dashboard/profile/%s/product/%s/update/", $this->request->param->param['profile_id'], $this->object->id);
    }
    public function get_success_url() {
      $this->success_url = sprintf("/wapo/dashboard/profile/%s/product/", $this->request->param->param['profile_id']);
    }
    public function get_cancel_url() {
      $this->cancel_url = sprintf("/wapo/dashboard/profile/%s/product/", $this->request->param->param['profile_id']);
    }
    public function get_object() {
      $distributor = Distributor::get_or_404(array("user"=>$this->request->user->id));
      $this->profile = Profile::get_or_404(array("id"=>$this->request->param->param['profile_id'],"distributor"=>$distributor->id));
      $this->object = Product::queryset()->get(array("id"=>$this->request->param->param['pk'],"profile"=>$this->profile->id));
    }
    public function get_context_data() {
      parent::get_context_data();
      $this->context['profile'] = $this->profile;
    }
  }
  
  class ProfileLocation extends \Blink\TemplateView {
    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/profile_location.twig");
    }
    public function get_context_data() {
      parent::get_context_data();
      $profile = null;
      $latitude = 41.676353;
      $longitude = -86.251991;
      
      try {
        $profile = Profile::queryset()->get(array("id"=>$this->request->param->param['profile_id']));
        $latitude = $profile->latitude;
        $longitude = $profile->longitude;
      } catch (Exception $ex) {
        
      }
      
      $this->context['latitude'] = $latitude;
      $this->context['longitude'] = $longitude;
      $this->context['profile'] = $profile;
      $this->context['site'] = \Blink\ConfigSite::$Site;
    }
  }
  
  class ProductDetailView extends \Blink\UpdateView {
   private $profile = null;
   
    public function require_login() {
      return true;
    }
    public function get_class() {
      $this->class = Product::class_name();
      parent::get_class();
    }
    public function get_object() {
      $distributor = Distributor::get_or_404(array("user"=>$this->request->user->id));
      $this->profile = Profile::get_or_404(array("id"=>$this->request->param->param['profile_id'],"distributor"=>$distributor->id));
      $this->object = Product::queryset()->get(array("id"=>$this->request->param->param['pk'],"profile"=>$this->profile->id));
    }
    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/product_details.twig");
    }
    public function get_context_data() {
      parent::get_context_data();
      $this->context['profile'] = $this->profile;
    }
  }
  
  class ProductDeleteView extends \Blink\DeleteView {
    private $profile = null;
    
    public function get_class() {
      $this->class = Product::class_name();
      parent::get_class();
    }
    public function get_object() {
      $distributor = Distributor::get_or_404(array("user"=>$this->request->user->id));
      $this->profile = Profile::get_or_404(array("id"=>$this->request->param->param['profile_id'],"distributor"=>$distributor->id));
      $this->object = Product::get_or_404(array("id"=>$this->request->param->param['pk'],"profile"=>$this->profile->id));
    }
    
    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/product_delete.twig");
    }
    public function get_success_url() {
      $this->success_url = sprintf("/wapo/dashboard/profile/%s/product/", $this->object->profile->id);
    }
    public function get_cancel_url() {
      $this->cancel_url = sprintf("/wapo/dashboard/profile/%s/product/", $this->object->profile->id);
    }
    
    public function get_context_data() {
      parent::get_context_data();
      $this->context['profile'] = $this->profile;
    }
  }
  
  class PromotionSendListView extends \Blink\ListView {
    private $profile = null;
    
    public function get_class() {
      $this->class = Wapo::class_name();
      parent::get_class();
    }
    public function get_queryset() {
      $distributor = Distributor::get_or_404(array("user"=>$this->request->user->id));
      $this->profile = Profile::get_or_404(array("id"=>$this->request->param->param['profile_id'],"distributor"=>$distributor->id));
      $this->queryset = Wapo::queryset()->order_by(array("-date_sent"))->filter(array("profile"=>$this->profile->id));
    }
    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/promotion_send_list.twig");
    }
    public function get_context_data() {
      parent::get_context_data();
      $this->context['profile'] = $this->profile;
    }
  }
  
  class PromotionSendDetailView extends \Blink\DetailView {
    private $profile = null;
    
    public function get_class() {
      $this->class = Wapo::class_name();
      parent::get_class();
    }
    public function get_object() {
      $distributor = Distributor::get_or_404(array("user"=>$this->request->user->id));
      $this->profile = Profile::get_or_404(array("id"=>$this->request->param->param['profile_id'],"distributor"=>$distributor->id));
      $this->object = Wapo::queryset()->get(array("id"=>$this->request->param->param['pk'],"profile"=>$this->profile->id));
    }
    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/promotion_send_details.twig");
    }
    public function get_context_data() {
      parent::get_context_data();
      $this->context['profile'] = $this->profile;
      $this->context['promotionrecipient_list'] = WapoRecipient::queryset()->filter(array("wapo"=>$this->object->id))->fetch();
 
      $this->context['checkout'] = \WePay\Api::checkout($this->object->checkout_id);
    }
    
  }
  
  class ProfileContactListView extends \Blink\ListView {
    public function get_class() {
      $this->class = Contact::class_name();
      parent::get_class();
    }
    public function get_object_list() {
      $distributor = Distributor::get_or_404(array("user"=>$this->request->user->id));
      $profile = Profile::get_or_404(array("id"=>$this->request->param->param['pk'],"dashboard"=>$distributor->id));
      $this->object_list = Contact::queryset()->filter(array("profile"=>$profile->id))->fetch();
    }
    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/contact_list.twig");
    }
  }
  
  class ProfileContactCreateView extends \Blink\CreateView {
    private $profile = null;
    
    public function get_class() {
      $this->class = Contact::class_name();
      parent::get_class();
    }
    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/contact_create.twig");
    }
    public function get_context_data() {
      parent::get_context_data();
      $this->context['profile'] = $this->profile;
    }
    
    public function get() {
      $distributor = Distributor::get_or_404(array("user"=>$this->request->user->id));
      $this->profile = Profile::get_or_404(array("id"=>$this->request->param->param['pk'],"dashboard"=>$distributor->id));
      return parent::get();
    }
  }
  
  class ProfileContactDetailView extends \Blink\DetailView {
    public function get_class() {
      $this->class = Contact::class_name();
      parent::get_class();
    }
    public function get_object() {
      $distributor = Distributor::get_or_404(array("user"=>$this->request->user->id));
      $profile = Profile::get_or_404(array("id"=>$this->request->param->param['profile_id'],"dashboard"=>$distributor->id));
      $this->object = Contact::get_or_404(array("id"=>$this->request->param->param['pk'],"profile"=>$profile->id));
    }
    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/contact_detail.twig");
    }
    public function get_context_data() {
      parent::get_context_data();
      
      $this->context['contact_item_list'] = ContactItem::queryset()->filter(array("contact"=>$this->object->id));
    }
  }
  
  class ProfileContactItemCreateView extends \Blink\CreateView {
    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/contact_item_create.twig");
    }
    
    public function form_valid() {
      $distributor = Distributor::get_or_404(array("user"=>$this->request->user->id));
      $profile = Profile::get_or_404(array("id"=>$this->request->param->param['profile_id'],"dashboard"=>$distributor->id));
      $contact = Contact::get_or_404(array("id"=>$this->request->param->param['pk'],"profile"=>$profile->id));
      
      $contact_item_create = array(
              "contact"=>$contact->id
      );
      
      
      parent::form_valid();
    }
  }
  
  class ProfileContactItemImportFormView extends \Blink\FormView {
    public function get_template_name() {
      $this->template_name = ConfigTemplate::Template("dashboard/contact_item_import.twig");
    }
  }
  
  

}
?>
