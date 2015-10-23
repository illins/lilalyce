<?php

namespace Wp {
  
  class SelectProfileTemplateView extends \Blink\TemplateView {

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
      // If the user is not logged in, go to the marketplace.
      if(!$this->request->user) {
        return \Blink\HttpResponseRedirect("/wp/marketplace/");
      }

      // Get or create their dashboard and get their profile list.
      $this->distributor = Distributor::get_or_create_save(array("user" => $this->request->user->id, "name" => ""), array(), false);
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
          $this->request->cookie->delete("profile_id");
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
      return TemplateConfig::Template("pipeline/marketplace.twig");
    }

    public function get_queryset() {
      $this->promotioncategory_list = PromotionCategory::queryset()->all();

      // Check if there is at least one promotion, if there isn't one, exit.
      if(!count($this->promotioncategory_list)) {
        \Blink\raise500("Site error.");
      }

      $queryset = parent::get_queryset();

      // Display the promotion categories.
      if($this->request->get->is_set("promotioncategory_id")) {
        $this->promotioncategory = PromotionCategory::get_or_404(array("id"=>$this->request->get->get("promotioncategory_id")));
        $queryset = Promotion::queryset()->filter(array("promotioncategory"=>$this->promotioncategory->id,"active"=>1));
      } else {
        $queryset = Promotion::queryset()->filter(array("promotioncategory"=>$this->promotioncategory_list[0]->id,"active"=>1));
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

      $context['form'] = array("post_url"=>"/wp/delivery-method/","cancel_url"=>"");

      return $context;
    }

//    public function get() {
//      // If user is logged in, check that they have a profile set and that it is theirs.
//      if($this->request->user) {
//        $profile = null;
//        if($this->request->get->is_set(("profile_id"))) {
//          try {
//            $distributor = Distributor::queryset()->get(array("user"=>$this->request->user));
//            $profile = Profile::queryset()->get(array("distributor"=>$distributor,"id"=>$this->request->get->get("profile_id")));
//          } catch (\Exception $ex) {
//            \Blink\Messages::error("Profile not found. Please select a profile.");
//            return \Blink\HttpResponseRedirect("/wp/");
//          }
//
//          $this->request->cookie->set("profile_id", $profile->id);
//        } else {
//          if(!$this->request->cookie->is_set("profile_id")) {
//            \Blink\Messages::error("Please select a profile.");
//            return \Blink\HttpResponseRedirect("/wp/");
//          }
//        }
//      }
//
//      return parent::get();
//    }
  }
  
  class CheckoutTemplateView extends \Blink\TemplateView {
    protected function get_template() {
      return TemplateConfig::Template("pipeline/checkout.twig");
    }

    protected function get_context_data() {
      $context = parent::get_context_data();

      $context['form'] = array(
          "post_url"=>"",
          "cancel_url"=>"/wp/profile/"
      );

      $context['delivery_method'] = $this->request->cookie->find("delivery_method");
      return $context;
    }
  }

  class ProfileTemplateView extends \Blink\TemplateView {
    protected function get_template() {
      return TemplateConfig::Template("pipeline/profile.twig");
    }

    protected function get() {
      if($this->request->user) {
        return \Blink\HttpResponseRedirect("/wp/profile-list/");
      }

      return parent::get();
    }
  }

  /**
   * - If user is looged in, show them thier profile list.
   */
  class ProfileListView extends \Blink\ListView {
    protected $class = "\Wapo\Profile";

    protected function get_template() {
      return TemplateConfig::Template("pipeline/profile_list.twig");
    }

    protected function get_queryset() {
      $queryset = parent::get_queryset();
      $distributor = Distributor::get_or_404(array("user"=>$this->request->user));
      $queryset->filter(array("distributor"=>$distributor));
      return $queryset;
    }

    protected function get_context_data() {
      $context = parent::get_context_data();

      $context['profile'] = null;
      try {
        $distributor = Distributor::get_or_404(array("user"=>$this->request->user));
        $context['profile'] = Profile::queryset()->get(array("distributor"=>$distributor,"id"=>$this->request->cookie->find("profile_id")));
      } catch (\Exception $ex) {
        $this->request->cookie->delete("profile_id");
      }

      $context['form'] = array(
        "post_url" => "/wp/checkout/",
        "cancel_url"=>"/wp/delivery-method/" . $this->request->cookie->find("delivery-method", "ffa") . "/"
      );

      return $context;
    }
  }


  class NewProfileFormView extends \Blink\FormView {
    protected $form_class = "\Wp\NewProfileForm";
    protected $success_url = "/wp/confirmation/ffa/";

    protected function get_template() {
      return TemplateConfig::Template("pipeline/profile_create.twig");
    }

    protected function form_valid() {
      $this->form->to_cookies();

      // Account created here if password(s) are set.


      return \Blink\HttpResponseRedirect($this->get_success_url());
    }
  }

  /**
   * - View to display the links a user can use to post to different places.
   * - Gets wapo id from cookie.
   * - If wapo is not found, error and asks them to log in.
   * - Also checks that Wapo is a Free For All.
   */
  class FreeForAllLinksTemplateView extends \Blink\TemplateView {
    protected function get_template() {
      return TemplateConfig::Template("pipeline/confirm_ffa.twig");
    }

    public function get_context_data() {
      $context = parent::get_context_data();

//      $kwargs = array(
//          "id" => $this->request->cookie->get("wapo_id"),
//          "delivery_method" => "ffa"
//      );

      //$context['wapo'] = Wapo::get_or_404($kwargs, "Wapo not found. Please log in to view your account.");


      $context['quantity'] = $this->request->cookie->find("quantity", 1);
      $context['promotion'] = Promotion::get_or_404(array("id"=>$this->request->cookie->find("promotion_id", 1)));
      $context['name'] = $this->request->cookie->find("name", 1);
      $context['email'] = $this->request->cookie->find("email", 1);
      $context['delivery_message'] = $this->request->cookie->find("delivery_message", 1);
      $context['delivery_method'] = $this->request->cookie->find("delivery_method");


      return $context;
    }
  }

  class ProfileFormView extends \Blink\FormView {
    private $form_profile_info;

    protected function get_template() {
      return TemplateConfig::Template("pipeline/profile.twig");
    }

    protected function get_request_data() {
      return ($this->request->method == "post") ? $this->request->post : $this->request->cookie;
    }

    protected function get_form_class() {
      if($this->request->param->param['delivery_method'] == "ffa") {
        return "\Wp\FreeForAllForm";
      }
    }

//    public function get_form() {
//      if($this->form) {
//        return $this->form;
//      }
//
//      if($this->request->param->param['delivery_method'] == "ffa") {
//        $post = ($this->request->method == "post") ? $this->request->post : $this->request->cookie;
//        $this->form = new FreeForAllForm($post);
//      } else if($this->request->param->param['delivery_method'] == "email") {
//        $field_list = new \Blink\FormFields();
//        $form_fields = array();
//        $data = array();
//        $max_email_count = ($this->request->user) ? Config::$LoggedInMaxEmailCount : Config::$NotLoggedInMaxEmailCount;
//
//        //$form_fields[] = $forms->HiddenCharField(array("name"=>"delivery_method","value"=>"email","max_length"=>20));
//        // Get the number of fields we want for the name and their data if available.
//        for ($i = 1; $i <= $max_email_count; $i++) {
//          $blank = ($i == 1) ? false : true;
//          $form_fields[] = $field_list->CharField(array("verbose_name" => "Name", "name" => "email_name$i", "min_length" => 1, "max_length" => 50, "blank" => $blank));
//          $form_fields[] = $field_list->EmailField(array("verbose_name" => "Email", "name" => "email_email$i", "min_length" => 5, "max_length" => 50, "blank" => $blank));
//          $data["email_name$i"]['value'] = $this->request->cookie->find("email_name$i");
//          $data["email_email$i"]['value'] = $this->request->cookie->find("email_email$i");
//        }
//
//        $post = ($this->request->method == "post") ? $this->request->post : $this->request->cookie;
//        $this->form = new \Blink\Form($post, $form_fields);
//      } else if($this->request->param->param['delivery_method'] == "email-list") {
//        $post = ($this->request->method == "post") ? $this->request->post : $this->request->cookie;
//        $this->form = new ContactForm($post);
//      }
//    }
//
//    /**
//     * - Override the post for FormView.
//     * - We have now 2 forms to check if they are valid.
//     * @return \Blink\Response
//     */
//    public function post() {
//      $this->get_form();
//
//      if($this->form_profile_info->is_valid() && $this->form->is_valid()) {
//        $this->form_profile_info->to_cookies();
//        $this->form->to_cookies();
//        $this->request->cookie->set("delivery_method", $this->request->param->param['delivery_method']);
//        return \Blink\HttpResponseRedirect("/wp/checkout/");
//      } else {
//        return $this->get();
//      }
//    }

    public function get_context_data() {
      $context = parent::get_context_data();

      // Get user's profiles if they are logged in.
      if($this->request->user) {
        $distributor = Distributor::queryset()->get(array("user"=>$this->request->user));
        $profile_list = Profile::queryset()->filter(array("distributor" => $distributor))->fetch();
        $context['profile_list'] = $profile_list;
      }

      // Rearrange the email form.
      if($this->request->param->param['delivery_method'] == "email") {
        $myform = $context['form'];

        $visible = array();
        $row = 1;
        foreach ($myform['visible'] as $field) {
          if (!isset($visible[$row])) {
            $visible[$row] = array("name" => null, "email" => null);
            $visible[$row]['name'] = $field;
          } else {
            $visible[$row]['email'] = $field;
            $row += 1;
          }
        }
        $myform['visible'] = $visible;
        $context['form'] = $myform;
      }

      $context['delivery_method'] = $this->request->param->param['delivery_method'];
      return $context;
    }
  }

  class PromotionDeliveryMethodFormView extends \Blink\FormView {
    protected function get_template() {
      return TemplateConfig::Template("frontend/promotion_delivery_method.twig");
    }

    public function get_success_url() {
      $this->success_url = "/wapo/promotion/promotion-profile/";
    }

    public function get_form() {
      if($this->request->method == "post") {
        if($this->request->post->is_set("delivery_method")) {
          switch($this->request->post->get("delivery_method")) {
            case "text":
              $this->form = new PhoneForm($this->request->post);
              break;
            case "facebook":
              $this->form = new FacebookForm($this->request->post);
              break;
            case "twitter":
              $this->form = new TwitterForm($this->request->post);
              break;
            case "email":
              $this->form = new EmailForm($this->request->post);
              break;
          }
        }
      }
    }

    public function get_context_data() {
      $context = parent::get_context_data();

      $delivery_method = "email";
      $contact_list = array();

      // If the delivery method is set, load it.
      if($this->request->cookie->is_set("delivery_method")) {
        $delivery_method = $this->request->cookiet->get("delivery_method");

        for($i = 1; $i <= 3; $i++) {
          $contact_list[] = array(
                  "name" => $this->request->cookie->get("name_$i"),
                  "contact" => $this->request->cookie->get("contact_$i")
          );
        }
      }

      $context['delivery_method'] = $delivery_method;
      $context['contact_list'] = $contact_list;
    }

    public function form_valid() {
      if(in_array($this->form->get("delivery_method"), array("email","text","facebook","twitter"))) {
        for($i = 1; $i <= 3; $i++) {
          $this->request->cookie->reset("name_$i", $this->form->get("name_$i"));
          $this->request->cookie->reset("contact_$i", $this->form->get("contact_$i"));
        }
      } else {
        \Blink\Messages::error("Delivery method not found.");
        return parent::get();
      }

      return \Blink\HttpResponseRedirect($this->success_url);
    }
  }

  class PromotionPreviewTemplateView extends \Blink\TemplateView {
    public function __construct() {
      parent::__construct();
    }
    public function __destruct() {
      parent::__destruct();
    }
    protected function get_template() {
      return TemplateConfig::Template("frontend/promotion_preview.twig");
    }

    public function get_context_data() {
      $context = parent::get_context_data();
    }

    public function get() {
      try {
        $context['promotion'] = Promotion::queryset()->get(array("id"=>$this->request->get->get("promotion_id")));
      } catch(\Exception $e) {
        return \Blink\HttpResponse("Promotion not found.", "text/plain");
      }

      return parent::get();
    }
  }

  class PromotionCheckoutTemplateView extends \Blink\TemplateView {
    private $promotion;

    protected function get_template() {
      return TemplateConfig::Template("frontend/promotion_checkout.twig");
    }

    public function get_context_data() {
      $context = parent::get_context_data();

      // Delivery method details.
      $delivery_method = $this->request->cookie->find("delivery_method");
      $contact_list = array();
      $facebook_ids = "";
      if($delivery_method == "email") {
        for($i = 1; $i <= 3; $i++) {
          if($this->request->cookie->is_set("email_email$i")) {
            $contact_list[] = array(
                    "name" => $this->request->cookie->find("email_name$i"),
                    "email" => $this->request->cookie->find("email_email$i")
            );
          }
        }
      } else if($delivery_method == "facebook") {
        $facebook_ids = $this->request->cookie->find("facebook_ids");
      }

      $context['name'] = $this->request->cookie->find("name");
      $context['promotion'] = $this->promotion;
      $context['delivery_method'] = $delivery_method;
      $context['contact_list'] = $contact_list;
      $context['facebook_ids'] = $facebook_ids;
      $context['delivery_message'] = $this->request->cookie->find("delivery_message");
      $context['delivery_date'] = $delivery_method;
      $context['progress'] = Helper::frontend_progress($this->request, 4);
      return $context;
    }

    public function get() {
//      $check = Helper::check_promotion($this->request, "profile");
//      if($check['error']) {
//        \Blink\Messages::error($check['message']);
//        return \Blink\HttpResponseRedirect($check['url']);
//      }
//
//      $this->promotion = Promotion::get_or_404(array("id"=>$this->request->cookie->get("promotion_id")));
      return parent::get();
    }
  }

  class PromotionCheckoutIdTemplateView extends \Blink\TemplateView {
    public function get() {

      $response =  new \stdClass();
      $promotion = null;

      // Check that promotion is filled in.
      try {
        $promotion = Promotion::queryset()->get(array("id"=>$this->request->cookie->find("promotion_id")));
      } catch(\Exception $e) {
        $response->result = "no";
        $response->redirect = "/wapo/marketplace/";
        return \Blink\HttpResponse($response, \Blink\View::CONTENT_JSON);
      }

      // Check that delivery method is filled in.
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
        } else {
          $count = 0;
        }
      }

      if(!$count) {
        $response->result = "no";
        $response->redirect = "/wapo/delivery-method/";
        return \Blink\HttpResponse($response, \Blink\View::CONTENT_JSON);
      }

      $cost = $count * $promotion->price;
      try {
        $response = \WePay\Api::checkout_create("00", $cost, "YOYO", "iframe", sprintf("%s/wapo/send/", \Blink\SiteConfig::SITE));
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

  class PromotionSendRedirectView extends \Blink\RedirectView {

    public function get_redirect_url() {
      // Check that we set the promotion.
      if($this->request->get->is_set("promotion_id")) {
        $this->request->cookie->reset("promotion_id", $this->request->get->get("promotion_id"));
      }

      // Make sure there is a promotin set before we proceed.
      if(!$this->request->cookie->is_set("promotion_id")) {
        $this->response = \Blink\HttpResponseRedirect("/wapo/marketplace/");
      }

      switch($this->request->cookie->find("delivery_method")) {
        case "facebook":
          $this->redirect_url = "/wapo/send/facebook/?" . $this->request->query_string;
          break;
        case "twitter":
          $this->redirect_url = "/wapo/send/twitter/?" . $this->request->query_string;
          break;
        case "text":
          $this->redirect_url = "/wapo/send/text/?" . $this->request->query_string;
          break;
        default:
          $this->redirect_url = "/wapo/send/email/?" . $this->request->query_string;
          break;
      }
    }

  }

  class PromotionSendEmailRedirectView extends \Blink\RedirectView {

    public function get_redirect_url() {
      // Make sure there is a promotin set before we proceed.
      if(!$this->request->cookie->is_set("promotion_id")) {
        $this->response = \Blink\HttpResponseRedirect("/wapo/marketplace/");
        return;
      }

      // Get local account information.
      $account = \User\Account::queryset()->get(array("name"=>"local"));

      // Check if the email has been used before.
      $user_list = \User\User::queryset()->filter(array("email"=>$this->request->cookie->find("email")))->fetch();

      if(count($user_list)) {
        $user = $user_list[0];
      } else {
        $user = \User\User::get_or_create_save(
                    array(
                    "email" => $this->request->cookie->find("email"),
                    "account" => $account->id
                    ), array(), false);
        $user->save(false);
      }

      // Check if distributor has been created
      $distributor = Distributor::get_or_create_save(
                      array(
                      "user" => $user->id
                      ), array(), false);

      // Check if they have a profile.
      $profile_list = Profile::queryset()->filter(array("distributor" => $distributor->id))->fetch();

      $profile = null;

      if(count($profile_list)) {
        $profile = $profile_list[0];
      } else {
        // Create profile if none exists.
        $profile = Profile::get_or_create_save(
                        array(
                        "distributor" => $distributor->id,
                        "name" => $this->request->cookie->find("name", "")
                        ), array(), false);
      }

      // Get promotion.
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
                        "contact" => sprintf("%s", $this->request->cookie->get("email_email$i")),
                        //"contact" => sprintf("%s <%s>", $this->request->cookie->get("email_name$i"), $this->request->cookie->get("email_email$i"))
                );
              }
            }
          } else {/* If from list, check that the list is the users, then gather the emails */
            $contact = Contact::get_or_404(array("id" => $this->request->cookie->get("contact_id"), "profile" => $profile->id));
            $contactitem_list = ContactItem::queryset()->filter(array("contact" => $contact->id))->fetch();

            foreach($contactitem_list as $item) {
              $contact_list[] = array(
                      "name" => $item->name,
                      "contact" => sprintf("%s", $item->item),
                      //"contact" => sprintf("%s <%s>", $item->name, $item->item)
              );
            }
          }

          // Attempt to send the email to each address.
          $all_sent = true;
          foreach($contact_list as $cont) {
            $mail_rec = clone $mail;
            $mail_rec->setTo(array($cont['contact']=>$cont['name']));

            $create_recipient = array(
                    "wapo" => $wapo->id,
                    "name" => $cont['name'],
                    "contact" => $cont['contact'],
                    "code" => substr(md5(date("H:i:s") . $cont['contact']), 0, 6),
                    "confirm" => substr(md5(date("H:i:s") . $i), 0, 6),
                    "sent" => 1
            );
            //str_pad(0, 5, "0")

            // Get the email to send.
            $message = \Blink\render_get(array("profile" => $profile, "promotion" => $promotion, "wapo" => $wapo, "code" => $create_recipient['code'],"url"=>  \Blink\SiteConfig::SITE), TemplateConfig::Template("frontend/promotion_email.twig"));
            $mail_rec->setBody($message, "text/html");

            $result = \Swift\Api::Send($mail_rec);
            if($result !== true) {
              $all_sent = false;
              $create_recipient['sent'] = 0;
            }

            // Create the recipient entry with the result.
            WapoRecipient::create_save($create_recipient, false);
          }

          if(!$all_sent) {
            \Blink\Messages::info("Some wapo(s) were not sent.");
          } else {
            \Blink\Messages::success("You wapo has been sent.");
          }

          $url = sprintf("/wapo/confirm/%s/", $wapo->id);
          $this->request->session->set("wapo_id", $wapo->id);
          $this->response = \Blink\HttpResponseRedirect($url);
        }
      } else {
        $this->response = \Blink\HttpResponseRedirect(sprintf("/wapo/dashboard/profile/%s/delivery-method/", $profile->id));
      }
    }

  }

  class PromotionSendFacebookTemplateView extends \Blink\TemplateView {
    protected function get_template() {
      return TemplateConfig::Template("frontend/promotion_send_facebook.twig");
    }
    public function get_context_data() {
      $context = parent::get_context_data();

      $context['progress'] = Helper::frontend_progress($this->request, 4);
    }
  }

  class PromotionSendFacebookPostTemplateView extends \Blink\TemplateView {
    public function get() {

      // Get facebook account id.
      $account = \User\Account::queryset()->get(array("name" => "facebook"));

      // Check if facebook id has been used before.
      $user = \User\User::get_or_create_save(
                      array(
                      "email" => $this->request->cookie->find("facebook_id"),
                      "username" => $this->request->cookie->find("facebook_id"),
                      "account" => $account->id
                      ), array(), false);

      // Check if distributor has been created
      $distributor = Distributor::get_or_create_save(
                      array(
                      "user" => $user->id,
                      ), array(), false);

      // Check if they have a profile.
      $profile_list = Profile::queryset()->filter(array("distributor" => $distributor->id))->fetch();

      $profile = null;

      if(count($profile_list)) {
        $profile = $profile_list[0];
      } else {
        // Create profile if none exists.
        $profile = Profile::get_or_create_save(
                        array(
                        "distributor" => $distributor->id,
                        "name" => $this->request->cookie->find("name", "---")
                        ), array(), false);
      }

      // Get promotion.
      $promotion = Promotion::get_or_404(array("id" => $this->request->cookie->get("promotion_id")));

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
      $this->request->session->set("wapo_id", $wapo->id);

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

      $post = \Blink\render_get(array("profile" => $profile, "promotion" => $promotion, "wapo" => $wapo, "code" => $create_recipient['code'],"url"=>  \Blink\SiteConfig::SITE), TemplateConfig::Template("frontend/promotion_facebook.twig"));

      $response = array(
              "result" => "yes",
              "loggedin" => "no",
              "facebook_ids" => $this->request->cookie->get("facebook_ids"),
              "name" => $promotion->name,
              "caption" => sprintf("%s has sent you a Wp", $profile->name),
              "description" => $wapo->delivery_message,
              "picture" => sprintf("%s%s", \Blink\SiteConfig::SITE, $promotion->icon->url),
              "link" => sprintf("%s/wapo/download/?code=%s", \Blink\SiteConfig::SITE, $create_recipient['code']),
              "post" => $post,
              "url" => sprintf("/wapo/confirm/%s/", $wapo->id)
      );

      return \Blink\HttpResponse($response, \Blink\View::CONTENT_JSON);
    }
  }

  class PromotionConfirmTemplateView extends \Blink\TemplateView {
    protected function get_template() {
      return TemplateConfig::Template("frontend/promotion_confirmation.twig");
    }

    public function get_context_data() {
      $context = parent::get_context_data();

      Helper::clear_cookies($this->request->cookie);

      try {
        $wapo = Wapo::queryset()->get(array("id"=>$this->request->session->get("wapo_id"), "checkout_id"=>$this->request->session->get("checkout_id")));
      } catch(\Exception $e) {
        return \Blink\HttpResponseRedirect("/");
      }

      $checkout = \WePay\Api::checkout($wapo->checkout_id);

      $context['wapo'] = $wapo;
      $context['checkout'] = $checkout;
      $context['profile'] = $wapo->profile;
      $context['progress'] = Helper::frontend_progress($this->request, 5);
    }

    public function get() {
      // Check if there is a wapo set.
      if(!$this->request->session->is_set("wapo_id")) {
        return \Blink\HttpResponseRedirect("/");
      }

      // Check if there is a checkout_id set.
      if(!$this->request->session->is_set("checkout_id")) {
        return \Blink\HttpResponseRedirect("/");
      }

      return parent::get();
    }
  }

  // Output list of contacts the user has for that profile.
  class PromotionProfileSelectedTemplateview extends \Blink\TemplateView {
    protected function get_template() {
      return TemplateConfig::Template("frontend/promotion_profile_selected.twig");
    }

    public function get_context_data() {
      $context = parent::get_context_data();

      try {
        $contact_id = $this->request->get->get("contact_id");
        $contact = Contact::queryset()->get(array("id"=>$contact_id));
        $distributor = Distributor::queryset()->get(array("user"=>$this->request->user->id));
        $profile = Profile::queryset()->get(array("id"=>$contact->profile->id,"distributor"=>$distributor->id));
        $context['profile'] = $profile;
      } catch(\Exception $e) {
        $context['profile'] = NULL;
      }
    }

    public function get() {
      return parent::get();
    }
  }

  // Output list of contacts the user has for that profile.
  class PromotionProfileSetupTemplateview extends \Blink\TemplateView {
    public function __construct() {
      parent::__construct();
    }
    public function __destruct() {
      parent::__destruct();
    }
    protected function get_template() {
      return TemplateConfig::Template("frontend/promotion_profile.twig");
    }

    public function get_context_data() {
      $context = parent::get_context_data();

      $distributor = Distributor::queryset()->get(array("user"=>$this->request->user->id));
      $context['profile_list'] = Profile::queryset()->filter(array("distributor"=>$distributor->id))->fetch();
    }

    public function get() {
      return parent::get();
    }
  }

  // Output list of contacts the user has for that profile.
  class PromotionProfileCreateView extends \Blink\CreateView {
    public function __construct() {
      parent::__construct();
    }
    public function __destruct() {
      parent::__destruct();
    }
    public function require_login() {
      return TRUE;
    }
    public function get_class() {
      $this->class = Profile::class_name();
      parent::get_class();
    }
    protected function get_template() {
      return TemplateConfig::Template("frontend/promotion_profile_create.twig");
    }
    public function get_exclude() {
      $this->exclude = array("distributor");
      parent::get_exclude();
    }
    public function get_success_url() {
      $this->success_url = sprintf("/wapo/profile/%s/", $this->object->id);
    }
    public function get_cancel_url() {
      $this->cancel_url = "/wapo/dashboard/";
    }
    public function form_valid() {
      $distributor = Distributor::get_or_create(array("user"=>$this->request->user->id));
      $distributor->save(FALSE);

      $create = array(
              "distributor" => $distributor->id,
              "name" => $this->form->get("name"),
              "street" => $this->form->get("street"),
              "city" => $this->form->get("city"),
              "state" => $this->form->get("state"),
              "zip" => $this->form->get("zip"),
              "status" => $this->form->get("status"),
      );
      $this->object = Profile::get_or_create($create);

      if($this->object->id) {
        \Blink\Messages::info(sprintf("Profile '%s' already exists.", $this->object->name));
      } else {
        \Blink\Messages::info(sprintf("Profile '%s' added.", $this->object->name));
      }
      $this->object->save(FALSE);

      $this->get_success_url();

      return \Blink\HttpResponseRedirect($this->success_url);
    }
  }


  // Get the checkout page.
  class PromotionCheckoutContactTemplateview extends \Blink\TemplateView {
    public function __construct() {
      parent::__construct();
    }
    public function __destruct() {
      parent::__destruct();
    }
    protected function get_template() {
      return TemplateConfig::Template("frontend/promotion_checkout_contact.twig");
    }

    public function get_context_data() {
      $context = parent::get_context_data();

      try {
        $contact = Contact::queryset()->get(array("id"=>$this->request->get->get("contact_id")));
        $context['contact'] = $contact;
      } catch(\Exception $e) {
        $context['contact'] = NULL;
      }
    }

    public function get() {
      return parent::get();
    }
  }

  /**
   * View gets info on who we are sending to.
   */
  class PromotionDeliveryMethodEmailFormView extends \Blink\FormView {

    private $promotion = null;

    protected function get_template() {
      return TemplateConfig::Template("frontend/promotion_delivery_method_email.twig");
    }

    public function get_success_url() {
      $this->success_url = "/wapo/profile/";
    }

    public function get_form() {
      $forms = new \Blink\FormFields();
      $form_fields = array();
      $data = array();

      if($this->form) {
        return;
      }

      $form_fields[] = $forms->HiddenCharField(array("name"=>"delivery_method","value"=>"email","max_length"=>20));
      // Get the number of fields we want for the name and their data if available.
      for($i = 1; $i <= Config::$NotLoggedInMaxEmailCount; $i++) {
        $blank = ($i == 1) ? false : true;
        $form_fields[] = $forms->CharField(array("verbose_name" => "Name", "name" => "email_name$i", "min_length" => 1, "max_length" => 50, "blank" => $blank));
        $form_fields[] = $forms->EmailField(array("verbose_name" => "Email", "name" => "email_email$i", "min_length"=>5,"max_length" => 50, "blank" => $blank));
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
      $context = parent::get_context_data();

      $context['email_form'] = $this->form->Form();
      $context['promotion'] = $this->promotion;
      $context['progress'] = Helper::frontend_progress($this->request, 2);
    }

    public function form_invalid() {
      \Blink\Messages::error("There were form errors.");
      return parent::form_invalid();
    }

    public function form_valid() {
      // If delivery method is contact list, check that the list exists.
      if($this->form->get("delivery_method") == "contact-list") {
        $contact = Contact::get_or_404(array("id" => $this->form->get("contact_id"), "profile" => $this->profile->id));
        $this->request->cookie->reset("delivery_method", "contact-list");
        $this->request->cookie->reset("contact_id", $contact->id);
      } else {
        $this->request->cookie->set("delivery_method", "email");

        for($i = 1; $i <= Config::$NotLoggedInMaxEmailCount; $i++) {
          if($this->form->get("email_email$i")) {
            $this->request->cookie->set("email_name$i", $this->form->get("email_name$i"));
            $this->request->cookie->set("email_email$i", $this->form->get("email_email$i"));
          } else {
            $this->request->cookie->delete("email_name$i");
            $this->request->cookie->delete("email_email$i");
          }
        }
      }

      $this->get_success_url();
      return \Blink\HttpResponseRedirect($this->success_url);
    }

    public function get() {
      $check = Helper::check_promotion($this->request, "delivery-method");
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
   * View gets info on who we are sending to (text).
   */
  class PromotionDeliveryMethodTwitterFormView extends \Blink\FormView {

    private $profile = NULL;
    private $promotion = null;

    protected function get_template() {
      return TemplateConfig::Template("frontend/promotion_delivery_method_twitter.twig");
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
      $context = parent::get_context_data();

      $context['contact_list'] = Contact::queryset()->filter(array("profile" => $this->request->param->param['pk'], "type" => "p"))->fetch();
      $context['text_form'] = $this->form->Form();
      $context['profile'] = $this->profile;
      $context['promotion'] = $this->promotion;
      $context['progress'] = Helper::frontend_progress($this->request, 2);
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

      // Check if the promotion is set.
      if(!$this->request->get->is_set("promotion_id")) {
        return \Blink\HttpResponseRedirect(sprintf("/wapo/dashboard/profile/%s/marketplace/", $this->profile->id));
      }

      $this->promotion = Promotion::get_or_404(array("id" => $this->request->get->get("promotion_id")));
      $this->request->cookie->reset("promotion_id", $this->promotion->id);

      return parent::get();
    }

  }

  /**
   * View gets info on who we are sending to (text).
   */
  class PromotionDeliveryMethodTextFormView extends \Blink\FormView {

    private $promotion = null;

    protected function get_template() {
      return TemplateConfig::Template("frontend/promotion_delivery_method_text.twig");
    }

    public function get_success_url() {
      $this->success_url = "/wapo/marketplace/";
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
        $this->form = new \Blink\Form($this->request->post, $form_fields);
      } else {
        $this->form = new \Blink\Form(array(), $form_fields, $data);
      }
    }

    public function get_context_data() {
      $context = parent::get_context_data();

      $context['text_form'] = $this->form->Form();
      $context['promotion'] = $this->promotion;
      $context['progress'] = Helper::frontend_progress($this->request, 2);
    }

    public function form_invalid() {
      \Blink\Messages::error("There were form errors.");
      return parent::form_invalid();
    }

    public function form_valid() {
      $this->request->cookie->set("delivery_method", "text");

      for($i = 1; $i <= 3; $i++) {
        if($this->form->get("text_phone$i")) {
          $this->request->cookie->set("text_name$i", $this->form->get("text_name$i"));
          $this->request->cookie->set("text_phone$i", $this->form->get("text_phone$i"));
        }
      }

      $this->get_success_url();
      return \Blink\HttpResponseRedirect($this->success_url);
    }

    public function get() {
      // Check if the promotion is set.
      if(!$this->request->cookie->is_set("promotion_id")) {
        return \Blink\HttpResponseRedirect(sprintf("/wapo/dashboard/profile/%s/marketplace/", $this->profile->id));
      }

      $this->promotion = Promotion::get_or_404(array("id" => $this->request->cookie->get("promotion_id")));
      $this->request->cookie->reset("promotion_id", $this->promotion->id);

      return parent::get();
    }

  }

  /**
   * View gets info on who we are sending to.
   */
  class PromotionDeliveryMethodFacebookFormView extends \Blink\FormView {

    protected function get_template() {
      return TemplateConfig::Template("frontend/promotion_delivery_method_facebook.twig");
    }

    public function get_success_url() {
      $this->success_url = "/wapo/profile/";
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

      $form_fields[] = $forms->HiddenCharField(array("name" => "facebook_ids", "blank" => false));
      $form_fields[] = $forms->HiddenCharField(array("name" => "delivery_method", "blank" => false));

      // If post set the form.
      if($this->request->method == "post") {
        $this->form = new \Blink\Form($this->request->post, $form_fields);
      } else {
        $this->form = new \Blink\Form(array(), $form_fields, $data);
      }
    }

    public function get_context_data() {
      $context = parent::get_context_data();

      $context['facebook_form'] = $this->form->Form();
      $context['promotion'] = $this->promotion;
      $context['progress'] = Helper::frontend_progress($this->request, 2);
    }

    public function form_invalid() {
      \Blink\Messages::error("There were form errors.");
      return parent::form_invalid();
    }

    public function form_valid() {
      // If delivery method is contact list, check that the list exists.
      if($this->form->get("delivery_method") == "facebook") {
        $this->request->cookie->set("delivery_method", "facebook");

        $this->request->cookie->set("facebook_ids", $this->form->get("facebook_ids"));
      }

      $this->get_success_url();
      return \Blink\HttpResponseRedirect($this->success_url);
    }

    public function get() {
      $check = Helper::check_promotion($this->request, "delivery-method");
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
   * Determine what we want to do with the profile.
   */
  class PromotionProfileRedirectView extends \Blink\RedirectView {
    public function get_redirect_url() {
      $check = Helper::check_promotion($this->request, "profile");
      if($check['error']) {
        \Blink\Messages::error($check['message']);
        $this->response = \Blink\HttpResponseRedirect($check['url']);
        return;
      }

      $profile = $this->request->cookie->find("profile");
      $delivery_method = $this->request->cookie->find("delivery_method");

      // If a profile is created, use it.
      if($delivery_method == "facebook") {
        $this->redirect_url = "/wapo/profile/facebook/";
      } else if($profile == "twitter" || $delivery_method == "twitter") {
        $this->redirect_url = "/wapo/profile/twitter/";
      } else {
        $this->redirect_url = "/wapo/profile/email/";
      }
    }
  }

  class PromotionProfileFacebookFormView extends \Blink\FormView {
    private $promotion;

    protected function get_template() {
      return TemplateConfig::Template("frontend/promotion_profile_facebook.twig");
    }

    public function get_success_url() {
      $this->success_url = "/wapo/promotion/promotion-checkout/";
    }
    public function get_form() {
      $data = array(
              "name" => array("value" => $this->request->cookie->find("name")),
              "delivery_message" => array("value" => $this->request->cookie->find("delivery_message")),
              "expiring_date" => array("value" => $this->request->cookie->find("expiring_date")),
              "facebook_id" => array("value" => $this->request->cookie->find("facebook_id"))
      );

      if($this->request->method == "post") {
        $this->form = new PromotionProfileFacebookForm($this->request->post, array());
      } else {
        $this->form = new PromotionProfileFacebookForm(array(), array(), $data);
      }
    }
    public function get_context_data() {
      $context = parent::get_context_data();
      $context['form'] = $this->form->Form();
      $context['promotion'] = $this->promotion;
      $context['progress'] = Helper::frontend_progress($this->request, 3);
    }
    public function form_valid() {
      // Check if facebook account exists with id.
      $account = \User\Account::queryset()->get(array("name"=>"facebook"));

      try {
        $user = \User\User::queryset()->get(array("email"=>$this->form->get("facebook_id"),"account"=>$account->id));
        $message = 'An account already exists with the Facebook account you used. You can <a href="/user/login/" class="alert-link">login</a> with your Facebook to fully use the features of Wp or continue with the current task.';
        \Blink\Messages::info($message);
      } catch(\Exception $e) {
        ;
      }

      $this->request->cookie->reset("profile", "facebook");
      $this->request->cookie->reset("name", $this->form->get("name"));
      $this->request->cookie->reset("delivery_message", $this->form->get("delivery_message"));
      $this->request->cookie->reset("expiring_date", $this->form->get("expiring_date"));
      $this->request->cookie->reset("facebook_id", $this->form->get("facebook_id"));

      return \Blink\HttpResponseRedirect("/wapo/checkout/");
    }
    public function get() {
      $check = Helper::check_promotion($this->request, "profile");
      if($check['error']) {
        \Blink\Messages::error($check['message']);
        return \Blink\HttpResponseRedirect($check['url']);
      }

      $this->promotion = Promotion::get_or_404(array("id"=>$this->request->cookie->get("promotion_id")));
      return parent::get();
    }

  }

  class PromotionProfileEmailFormView extends \Blink\FormView {
    private $promotion;

    protected function get_template() {
      return TemplateConfig::Template("frontend/promotion_profile_email.twig");
    }

    public function get_success_url() {
      $this->success_url = "/wapo/promotion/promotion-checkout/";
    }

    public function get_form() {
      $data = array(
              "name" => array("value" => $this->request->cookie->find("name")),
              "email" => array("value" => $this->request->cookie->find("email")),
              "delivery_message" => array("value" => $this->request->cookie->find("delivery_message")),
              "delivery_date" => array("value" => $this->request->cookie->find("delivery_date")),
              "expiring_date" => array("value" => $this->request->cookie->find("expiring_date"))
      );

      if($this->request->method == "post") {
        $this->form = new PromotionProfileCreateForm($this->request->post, array());
      } else {
        $this->form = new PromotionProfileCreateForm(array(), array(), $data);
      }
    }
    public function get_context_data() {
      $context = parent::get_context_data();
      $context['form'] = $this->form->Form();
      $context['promotion'] = $this->promotion;
      $context['progress'] = Helper::frontend_progress($this->request, 3);
    }

    public function form_valid() {
      // Check if facebook account exists with id.
      $account = \User\Account::queryset()->get(array("name"=>"local"));

      try {
        $user = \User\User::queryset()->get(array("email"=>$this->form->get("email"),"account"=>$account->id));

        if($user->code) {
          $message = sprintf('An account already exists with the email you used. You can <a href="/user/register/confirm/send/?email=%s" class="alert-link">retrieve</a> your account to fully use the features of Wp or continue with the current task.', $user->email);
        } else {
          $message = 'An account already exists with the email you used. You can <a href="/user/login/" class="alert-link">log in</a> to your account to fully use the features of Wp or continue with the current task.';
        }

        \Blink\Messages::info($message);
      } catch(\Exception $e) {
        ;
      }

      $this->request->cookie->reset("profile", "email");
      $this->request->cookie->reset("name", $this->form->get("name"));
      $this->request->cookie->reset("email", $this->form->get("email"));
      $this->request->cookie->reset("delivery_message", $this->form->get("delivery_message"));
      $this->request->cookie->reset("delivery_date", $this->form->get("delivery_date"));
      $this->request->cookie->reset("expiring_date", $this->form->get("expiring_date"));

      return \Blink\HttpResponseRedirect("/wapo/checkout/");
    }
    public function get() {
      $check = Helper::check_promotion($this->request, "profile");
      if($check['error']) {
        \Blink\Messages::error($check['message']);
        return \Blink\HttpResponseRedirect($check['url']);
      }

      $this->promotion = Promotion::get_or_404(array("id"=>$this->request->cookie->get("promotion_id")));
      return parent::get();
    }
  }

  class PromotionFacebookSaveRedirectView extends \Blink\RedirectView {

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

      \Blink\Messages::success("You wapo has been sent.");
      $url = sprintf("/wapo/dashboard/profile/%s/receipt/%s/", $profile->id, $wapo->id);
      $this->response = \Blink\HttpResponseRedirect($url);
    }

  }

  class PromotionTwitterSendRedirectView extends \Blink\RedirectView {

    public function get_redirect_url() {
      // Make sure there is a promotin set before we proceed.
      if(!$this->request->cookie->is_set("promotion_id")) {
        $this->response = \Blink\HttpResponseRedirect("/wapo/marketplace/");
        return;
      }

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
            $mail->message = \Blink\render_get(array("profile" => $profile, "promotion" => $promotion, "wapo" => $wapo, "code" => $create_recipient['code'],"url"=>\Blink\Config::$Site), TemplateConfig::Template("frontend/dashboard/profile_promotion_email.twig"));

            if(!$mail->send()) {
              $create_recipient['sent'] = 0;
            }

            // Create the recipient entry with the result.
            WapoRecipient::create_save($create_recipient, false);
          }

          \Blink\Messages::success("You wapo has been sent.");
          $url = sprintf("/wapo/dashboard/profile/%s/receipt/%s/", $profile->id, $wapo->id);
          $this->response = \Blink\HttpResponseRedirect($url);
        }
      } else {
        $this->response = \Blink\HttpResponseRedirect(sprintf("/wapo/dashboard/profile/%s/delivery-method/", $profile->id));
      }
    }

  }

  class PromotionSendFormView extends \Blink\FormView {

    public function require_login() {
      return TRUE;
    }

    public function get_form() {
      $forms = new \Blink\FormFields();
      $field_list = array();

      $field_list[] = $forms->TextField(array("name" => "emails","help_text"=>"Enter emails to send to separated by commas ','"));
      $field_list[] = $forms->HiddenPositiveIntegerField(array("name" => "profile_id", "value" => $this->request->param->param['profile_id']));
      $field_list[] = $forms->HiddenPositiveIntegerField(array("name" => "promotion_id", "value" => $this->request->param->param['promotion_id']));

      if(!$this->form) {
        if($this->request->method == "post") {
          $this->form = new \Blink\Form($this->request->post, $field_list);
        } else {
          $this->form = new \Blink\Form(array(), $field_list);
        }
      }
    }
    public function get_cancel_url() {
      $this->cancel_url = sprintf("/wapo/profile/%s/", $this->request->param->param['profile_id']);
    }
    public function get_post_url() {
      $this->post_url = sprintf("/wapo/profile/%s/promotion/%s/send/", $this->request->param->param['profile_id'], $this->request->param->param['promotion_id']);
    }

    protected function get_template() {
      return TemplateConfig::Template("frontend/promotion_send.twig");
    }
    public function get_context_data() {
      $context['form'] = $this->form->Form($this->post_url,$this->cancel_url);
      $context = parent::get_context_data();
    }

    public function get() {
      $distributor = Distributor::queryset()->get(array("user"=>$this->request->user->id));

      $context['profile'] = Profile::get_or_404(array("wapo.profile.distributor"=>$distributor->id,"id"=>$this->request->param->param['profile_id']),"Profile not found.");

      $context['promotion'] = Promotion::get_or_404(array("id"=>$this->request->param->param['promotion_id']),"Promotion not found.");

      return parent::get();
    }

    public function get_success_url() {
      $this->success_url = sprintf("/wapo/profile/%s/",$this->request->param->param['profile_id']);
    }
    public function form_valid() {
      $distributor = Distributor::queryset()->get(array("user"=>$this->request->user->id));
      $profile = Profile::get_or_404(array("wapo.profile.distributor"=>$distributor->id,"id"=>$this->request->param->param['profile_id']),"Profile not found.");
      $promotion = Promotion::get_or_404(array("id"=>$this->request->param->param['promotion_id']),"Promotion not found.");

      $create = array(
              "profile"=>$profile->id,
              "promotion"=>$promotion->id,
              "date_created"=>date("Y-m-d H:i:s"),
              "date_sent"=>date("Y-m-d H:i:s")
      );

      $wapo = Wapo::create($create);
      $wapo->save("Promotion sent.");

      $email_list = explode(",", $this->form->get("emails"));
      $counter = 0;
      foreach($email_list as $email) {
        $code = $counter . substr(md5(date("Y-m-d H:i:s")), 0, 5);
        $confirm = $counter . substr(md5(date("Y-m-d H:i:s") . "confirm"), 0, 5);
        $send = array(
                "wapo"=>$wapo->id,
                "email"=>$email,
                "code"=>$code,
                "confirm"=>$confirm
        );

        $url = sprintf("/wapo/promotion/retrieve/?code=%s", $code);
        $mail = new \Blink\Mail();
        $mail->to = array($email);
        $mail->subject = "You received a wapo.";
        $mail->from = "founders@wapo.co";
        $mail->message = sprintf("%s has sent you a Wp. Please click on %s to retrieve it.", $profile->name, $url);
        $mail->send();

        @mail ( $mail , "You received a wapo." , sprintf("%s has sent you a Wp. Please click on %s to retrieve it.", $profile->name, $url));

        WapoRecipient::create_save($send, "");
        $counter++;
      }

      return \Blink\HttpResponseRedirect(sprintf("/wapo/profile/%s/", $profile->id));
    }
  }

//  class PromotionSendListView extends \Blink\ListView {
//    public function __construct() {
//      parent::__construct();
//    }
//    public function __destruct() {
//      parent::__destruct();
//    }
//    public function require_login() {
//      return TRUE;
//    }
//    public function get_class() {
//      $this->class = Wapo::class_name();
//      parent::get_class();
//    }
//    public function get_queryset() {
//      $this->queryset = Wapo::queryset()->filter(array("profile"=>$this->request->param->param['profile_id']))->order_by(array("-date_created"));
//    }
//    protected function get_template() {
//      return TemplateConfig::Template("frontend/wapo_list.twig");
//    }
//
//    public function get_context_data() {
//      $context = parent::get_context_data();
//
//      $distributor = Distributor::get_or_create_save(array("user"=>$this->request->user->id,"name"=>""));
//      $context['profile'] = Profile::queryset()->get(array("id"=>$this->request->param->param['profile_id'],"distributor"=>$distributor->id));
//      $context['distributor'] = $distributor;
//    }
//  }
//
//  class PromotionSendDetailView extends \Blink\DetailView {
//    public function __construct() {
//      parent::__construct();
//    }
//    public function __destruct() {
//      parent::__destruct();
//    }
//    public function require_login() {
//      return TRUE;
//    }
//    public function get_class() {
//      $this->class = Wapo::class_name();
//      parent::get_class();
//    }
//
//    public function get_object() {
//      $distributor = Distributor::get_or_404(array("user"=>$this->request->user->id));
//      $profile = Profile::queryset()->get(array("id"=>$this->request->param->param['profile_id'],"distributor"=>$distributor->id));
//
//      $this->object = $this->queryset->get(array("profile"=>$profile->id,"id"=>$this->slug->value));
//
//      $context['distributor'] = $distributor;
//      $context['profile'] = $profile;
//    }
//    protected function get_template() {
//      return TemplateConfig::Template("frontend/wapo.twig");
//    }
//
//    public function get_context_data() {
//      $context = parent::get_context_data();
//
//      $context['promotionrecipient_list'] = WapoRecipient::queryset()->filter(array("wapo"=>$this->object->id))->fetch();
//      $context['cost'] = count($context['promotionrecipient_list']) * $this->object->promotion->price;
//    }
//  }

  class RetrievePromotionCodeTemplateView extends \Blink\TemplateView {
    public function require_login() {
      return TRUE;
    }
    protected function get_template() {
      return TemplateConfig::Template("frontend/promotion_code.twig");
    }
  }


  class RetrievePromotionFormView extends \Blink\FormView {
    public function require_login() {
      return TRUE;
    }
    protected function get_template() {
      return TemplateConfig::Template("frontend/download/promotion_confirm.twig");
    }
    public function get_form() {
      $forms = new \Blink\FormFields();
      $field_list = array();

      $field_list[] = $forms->TextField(array("name" => "email","help_text"=>"Enter your email to confirm account."));
      $field_list[] = $forms->HiddenCharField(array("name" => "code", "max_length"=>50, "value" => $this->request->get->get('code')));

      if($this->request->method == "post") {
        $this->form = new \Blink\Form($this->request->post, $field_list);
      } else {
        $this->form = new \Blink\Form(array(), $field_list);
      }
    }

    public function form_valid() {
      $wapo = NULL;

      try {
        $wapo = WapoRecipient::queryset()->get(array("code"=>$this->form->get("code"),"email"=>$this->form->get("email")));
      } catch(\Exception $e) {
        return self::get();
      }

      $url = sprintf("/wapo/promotion/retrieve/confirm/?code=%s", $wapo->code);
      return \Blink\HttpResponseRedirect($url);
    }
    public function get_post_url() {
      $this->post_url = sprintf("/wapo/promotion/retrieve/?code=%s",$this->request->get->get("code"));
    }
    public function get_cancel_url() {
      $this->cancel_url = "/wapo/";
    }
    public function get_context_data() {
      $context = parent::get_context_data();
      $context['form'] = $this->form->Form($this->post_url,$this->cancel_url);
    }

    public function get() {
      // If no code was set.
      if(!$this->request->get->is_set("code")) {
        \Blink\Messages::error("Please enter your confirmation code.");
        \Blink\HttpResponseRedirect("/wapo/promotion/retrieve/code/");
      }

      $promotionrecipient = WapoRecipient::get_or_404(array("code"=>$this->request->get->get("code")), "Code not valid.");

      $profile = Profile::queryset()->get(array("id"=>$promotionrecipient->wapo->profile));
      $promotion = Promotion::queryset()->get(array("id"=>$promotionrecipient->wapo->promotion));


      $context['promotionrecipient'] = $promotionrecipient;
      $context['profile'] = $profile;
      $context['promotion'] = $promotion;
      $context['product_list'] = Product::queryset()->all();
      return parent::get();
    }
  }

  class ConfirmPromotionTemplateView extends \Blink\TemplateView {
    public function __construct() {
      parent::__construct();
    }
    public function __destruct() {
      parent::__destruct();
    }
    public function require_login() {
      return TRUE;
    }
    protected function get_template() {
      return TemplateConfig::Template("frontend/promotion_confirm.twig");
    }
    public function get_context_data() {
      $context = parent::get_context_data();

      $forms = new \Blink\FormFields();
      $field_list = array();

      $field_list[] = $forms->TextField(array("name" => "confirm","help_text"=>"Enter confirmation code."));
      $field_list[] = $forms->HiddenCharField(array("name" => "code", "max_length"=>50, "value" => $this->request->get->get('code')));

      $this->form = new \Blink\Form(array(), $field_list);
      $context['form'] = $this->form->Form("/wapo/promotion/retrieve/download/", "/wapo/");

      $promotionrecipient = WapoRecipient::get_or_404(array("code"=>$this->request->get->get("code")), "Code not valid.");

      $profile = Profile::queryset()->get(array("id"=>$promotionrecipient->wapo->profile));
      $promotion = Promotion::queryset()->get(array("id"=>$promotionrecipient->wapo->promotion));
      $context['promotion'] = $promotion;
      $context['profile'] = $profile;
    }
  }

  class DownloadPromotionTemplateView extends \Blink\TemplateView {
    public function __construct() {
      parent::__construct();
    }
    public function __destruct() {
      parent::__destruct();
    }
    public function require_login() {
      return TRUE;
    }
    protected function get_template() {
      return TemplateConfig::Template("frontend/promotion_download.twig");
    }
    public function get_context_data() {
      $context = parent::get_context_data();

      $promotionrecipient = WapoRecipient::get_or_404(array("code"=>$this->request->get->get("code"),"confirm"=>$this->request->get->get("confirm")), "Code not valid.");

      $forms = new \Blink\FormFields();
      $field_list = array();

      $field_list[] = $forms->TextField(array("name" => "confirm","help_text"=>"Enter confirmation code."));
      $field_list[] = $forms->HiddenCharField(array("name" => "code", "max_length"=>50, "value" => $this->request->get->get('code')));

      $this->form = new \Blink\Form(array(), $field_list);
      $context['form'] = $this->form->Form("/wapo/promotion/retrieve/download", "/wapo/");

      $profile = Profile::queryset()->get(array("id"=>$promotionrecipient->wapo->profile));
      $promotion = Promotion::queryset()->get(array("id"=>$promotionrecipient->wapo->promotion));
      $context['promotion'] = $promotion;
      $context['profile'] = $profile;

      $product_list = Product::queryset()->filter(array("profile"=>$profile->id))->fetch();
      $context['product_list'] = $product_list;

      $context['sociallink_list'] = SocialLinks::queryset()->filter(array("profile"=>$profile->id))->fetch();
    }
  }

  class FeatureTemplateView extends \Blink\TemplateView {
    public function __construct() {
      parent::__construct();
    }
    public function __destruct() {
      parent::__destruct();
    }
    public function require_login() {
      return TRUE;
    }
    protected function get_template() {
      return TemplateConfig::Template("frontend/feature.twig");
    }
  }

  class ImportEmailTemplateView extends \Blink\TemplateView {
    public function __construct() {
      parent::__construct();
    }
    public function __destruct() {
      parent::__destruct();
    }
    public function require_login() {
      return TRUE;
    }
    protected function get_template() {
      return TemplateConfig::Template("frontend/import_email.twig");
    }
  }

  class FacebookConnectTemplateView extends \Blink\TemplateView {
    public function __construct() {
      parent::__construct();
    }
    public function __destruct() {
      parent::__destruct();
    }
    public function require_login() {
      return TRUE;
    }
    protected function get_template() {
      return TemplateConfig::Template("frontend/facebook_connect.twig");
    }
  }

  class TwitterConnectTemplateView extends \Blink\TemplateView {
    public function __construct() {
      parent::__construct();
    }
    public function __destruct() {
      parent::__destruct();
    }
    public function require_login() {
      return TRUE;
    }
//    protected function get_template() {
//      return TemplateConfig::Template("frontend/twitter_connect.twig");
//    }
  }

  class SocialTemplateView extends \Blink\TemplateView {
    public function __construct() {
      parent::__construct();
    }
    public function __destruct() {
      parent::__destruct();
    }
    public function require_login() {
      return TRUE;
    }
    protected function get_template() {
      return TemplateConfig::Template("frontend/social.twig");
    }
  }













  class ProfileDetailView1 extends \Blink\DetailView {
    public function get_slug_field() {
      $this->slug = new \stdClass();
      $this->slug->name = "id";
      $member = Member::queryset()->get(array("user"=>$this->request->user->id));
      $this->slug->value = $member->company->id;
    }
    public function get_class() {
      $this->class = Company::class_name();
      parent::get_class();
    }

    protected function get_template() {
      return TemplateConfig::Template("frontend/profile.twig");
    }

    public function get_context_data() {
      $context = parent::get_context_data();

      $context['member_list'] = Member::queryset()->filter(array("company"=>$this->object->id))->fetch();

      $member = Member::queryset()->get(array("user"=>$this->request->user->id));

      $package_list = CompanyPackage::queryset()->filter(array("wapo.company.company.id"=>$member->company->id))->order_by(array("-expires"))->limit(0, 1)->fetch();
      if(isset($package_list[0])) {
        $context['package'] = $package_list[0];
        $date = date("Y-m-d");
        if($date > $context['package']->expires) {
          $context['expired'] = TRUE;
        }
      } else {
        $context['package'] = NULL;
        $context['expired'] = FALSE;
      }

      // Get the product list.
      $context['product_list'] = CompanyProduct::queryset()->filter(array("company"=>$member->company->id))->fetch();

    }
  }

  class CompanyProductListView extends \Blink\ListView {
    public function __construct() {
      parent::__construct();
    }
    public function __destruct() {
      parent::__destruct();
    }
    public function get_class() {
      $this->class = CompanyProduct::class_name();
      parent::get_class();
    }
    protected function get_template() {
      return TemplateConfig::Template("frontend/companyproduct_list.twig");
    }
  }

  class PromotionSelectView extends \Blink\ListView {
    public function get_class() {
      $this->class = Promotion::class_name();
      parent::get_class();
    }

    protected function get_template() {
      return TemplateConfig::Template("frontend/promotion_select.twig");
    }

    public function get_queryset() {
      parent::get_queryset();

      $this->queryset->order_by(array("promotion"));
    }

    public function get_context_data() {
      $context = parent::get_context_data();

      $grid = array();
      $item_list = array();
      foreach($context['promotion_list'] as $key => $value) {
        if($key !== 0 && ($key % 4)) {
          $item_list[] = $value;
          $grid[] = $item_list;
          $item_list = array();
        } else {
          $item_list[] = $value;
        }
      }

      //$context['promotion_list'] = $grid;
    }
  }

  class PromotionBrandView extends \Blink\FormView {
    public function get_form() {
      $forms = new \Blink\FormFields();
      $field_list = array();

      $field_list[] = $forms->CharField(array("name"=>"title"));
      $field_list[] = $forms->TextField(array("name"=>"link"));
      $field_list[] = $forms->TextField(array("name"=>"delivery_note"));
      $field_list[] = $forms->DateTimeField(array("name"=>"delivery_date","max_length"=>20));
      $field_list[] = $forms->HiddenPositiveIntegerField(array("name"=>"promotion_id","value"=>$this->request->param->param['promotion_id']));

      if($this->request->method == "post") {
        $this->form = new \Blink\Form($this->request->post, $field_list);
      } else {
        $this->form = new \Blink\Form(array(), $field_list);
      }
    }

    public function get_post_url() {
      $this->post_url = sprintf("/wapo/promotion/brand/%s/", $this->request->param->param['promotion_id']);
    }

    public function get_cancel_url() {
      $this->cancel_url = "/wapo/promotion/select/";
    }

    protected function get_template() {
      return TemplateConfig::Template("frontend/promotion_brand.twig");
    }

    public function get_context_data() {
      $context = parent::get_context_data();

      $context['form'] = $this->form->Form($this->post_url, $this->cancel_url);
    }

    public function form_valid() {
      $member = Member::queryset()->get(array("user"=>$this->request->user->id));
      $promotion = Promotion::get_or_404(array("id"=>$this->form->get("promotion_id")));
      $status = Status::queryset()->get(array("status"=>"customize"));
      $title = $this->form->get("title");
      $link = $this->form->get("link");
      $delivery_note = $this->form->get("delivery_note");
      $delivery_date = $this->form->get("delivery_date");

      $promotion_package = PromotionPackage::create(array("company"=>$member->company->id,"promotion"=>$promotion->id,"package"=>$member->company->package,"status"=>$status->id,"title"=>$title,"link"=>$link,"delivery_note"=>$delivery_note,"delivery_date"=>$delivery_date));
      $promotion_package->save(FALSE);

      $this->success_url = sprintf("/wapo/promotion/delivery/%s/",$promotion_package->id);
      return parent::form_valid();
    }
  }

  class PromotionPackageListView extends \Blink\ListView {
    public function get_class() {
      $this->class = PromotionPackage::class_name();
      parent::get_class();
    }

    protected function get_template() {
      return TemplateConfig::Template("frontend/promotionpackage_list.twig");
    }
  }

  class PromotionPackageDetailView extends \Blink\DetailView {
    public function get_class() {
      $this->class = PromotionPackage::class_name();
      parent::get_class();
    }

    protected function get_template() {
      return TemplateConfig::Template("frontend/promotionpackage.twig");
    }
  }


  class PromotionDeliveryView extends \Blink\FormView {
    public function get_form() {
      $forms = new \Blink\FormFields();
      $field_list = array();

      $delivery_list = Delivery::queryset()->all();

      foreach($delivery_list as $delivery) {
        $field_list[] = $forms->BooleanField(array("name"=>$delivery->delivery));
      }

      if($this->request->method == "post") {
        $this->form = new \Blink\Form($this->request->post, $field_list);
      } else {
        $this->form = new \Blink\Form(array(), $field_list);
      }
    }

    public function get_post_url() {
      $this->post_url = sprintf("/wapo/promotion/delivery/%s/", $this->request->param->param['pk']);
    }

    public function get_cancel_url() {
      $this->cancel_url = "/wapo/promotionpackage/";
    }

    protected function get_template() {
      return TemplateConfig::Template("frontend/promotion_delivery.twig");
    }

    public function form_valid() {
      $member = Member::queryset()->get(array("user"=>$this->request->user->id));
      $promotion = Promotion::get_or_404(array("id"=>$this->form->get("promotion_id")));
      $status = Status::queryset()->get(array("status"=>"customize"));
      $link = $this->form->get("link");
      $delivery_note = $this->form->get("delivery_note");
      $delivery_date = $this->form->get("delivery_date");

      $promotion_package = PromotionPackage::create(array("company"=>$member->company->id,"promotion"=>$promotion->id,"package"=>$member->company->package,"status"=>$status->id,"link"=>$link,"delivery_note"=>$delivery_note,"delivery_date"=>$delivery_date));
      $promotion_package->save(FALSE);

      $this->success_url = sprintf("/wapo/promotion/delivery/%s/",$promotion_package->id);
      return parent::form_valid();
    }
  }

  class PromotionRetrieveTemplateView extends \Blink\TemplateView {

    protected function get_template() {
      return TemplateConfig::Template("frontend/promotion_retrieve.twig");
    }
    public function get_context_data() {
      if(!$this->request->get->is_set("code")) {
        \Blink\raise404("Not code has been given.");
      }

      $promotionpackage = PromotionPackage::get_or_404(array("code"=>$this->request->get->get("code")));
      $company = Company::get_or_create(array("id"=>$promotionpackage->company->id));
      $product_list = CompanyProduct::queryset()->filter(array("company"=>$company->id))->fetch();

      $delivery_list = Delivery::queryset()->all();

      $context['promotionpackage'] = $promotionpackage;
      $context['company'] = $company;
      $context['product_list'] = $product_list;
      $context['delivery_list'] = $delivery_list;
      $context = parent::get_context_data();
    }
  }
}
