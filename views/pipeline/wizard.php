<?php
//
//namespace Wp {
//  require_once("blink/base/view/generic.php");
//  require_once("blink/base/view/edit.php");
//  require_once("blink/base/view/detail.php");
//  require_once("blink/base/view/list.php");
//  require_once("blink/base/view/wizard.php");
//
//  require_once("apps/wp/config.php");
//  require_once("apps/wapo/model.php");
//  require_once("apps/wapo/helper.php");
//  require_once("apps/wp/form.php");
//
//  require_once("apps/blink-user/api.php");
//  require_once("apps/wepay/api.php");
//
//  require_once("apps/blink-user-role/api.php");
//
//  require_once("apps/swiftmailer/api.php");
//  
//  require_once("apps/blink-twilio/api.php");
//  
//  require_once("apps/blink-tangocard/tangocard/tangocard.php");
//  
//  require_once 'apps/blink-bitly/bitly/bitly.php';
//  
//  // Wapo functions.
//  require_once 'apps/wp/views/pipeline/validate-wapo.php';
//  require_once 'apps/wp/views/pipeline/create-wapo.php';
//  require_once 'apps/wp/views/pipeline/send-wapo.php';
//  
//  require_once 'apps/blink-twitter/api.php';
//
//  use Wapo\PromotionCategory;
//  use Wapo\Promotion;
//  use Wapo\Distributor;
//  use Wapo\Profile;
//  use Wapo\Wapo;
//  use Wapo\WapoRecipient;
//  use Wapo\Helper;
//  use Wapo\Contact;
//  use Wapo\ContactItem;
//  use Wapo\Member;
// 
//  /**
//   * - Process the Wapo Promotion steps.
//   * - Step 1 - Marketplace
//   *    - Display the different types of marketplace we have. 
//   *      - Scalable - (code name garments).
//   * - Step 2 - Delivery
//   *    - Display a list of delivery methods.
//   * - Step 3 - Depends on step 2.
//   *    - If ffa - Free For All, display a general quantity form.
//   *    - If email - Show fields for them to enter emaails.
//   *    - If email list - Show their lists (grouped by profile).
//   *        - If they are not logged in, force them to log in.
//   *    - If anyff - Any Facebook Friends, display a general quantity form.
//   *        - If they are not logged in, show them a link to log in.
//   *    - If sff - A form with hidden fields to capture their Friends' ids. 
//   *        - If they are not logged in, show them a link to log in.
//   *    - If fp - A form with hidden fields to capture their Page id. 
//   *        - If they are not logged in, show them a link to log in.
//   * - Step 4 - Profile
//   *    - Show them profile options. 
//   *        - If Facebook, show Facebook Profile options that they can save.
//   *        - If email and logged in, allow them to select their profile.
//   *        - If email and not logged in, show them register form or ask them to log in.
//   * - Step 5 - Checkout
//   *    - Show checkout options (redirect to checkout).
//   * - Step 6 - Confirmation
//   *    - Display confirmation based on delivery method.
//   */
//  class WpCookieWizardView extends \Blink\CookieWizardView {
//    protected $delivery;
//    
//    private $promotioncategory_list = array();
//    private $promotioncategory = null;
//    private $promotion = null;
//    
//    private $module = null;
//    /**
//     * Override certain forms. To do this, form for this step *must be a 'Blink\Form'.
//     * @return type
//     */
//    protected function get_form_fields() {
//      // For email step, we want to make a dynamic form. Use generic 'Blink\Form' class for email.
//      if($this->current_step == "e") {
//        $emails = Config::$NotLoggedInMaxEmailDeliveryCount;
//        if($this->request->user) {
//          $emails = Config::$LoggedInMaxEmailDeliveryCount;
//        }
//        
//        $form_fields = new \Blink\FormFields();
//        $field_list = array();
//        $field_list[] = $form_fields->TextField(array("name"=>"delivery_message","blank"=>true));
//        $field_list[] = $form_fields->DateTimeField(array("name"=>"expiring_date","format"=>"m/d/Y H:i A","min_value"=>date("m/d/Y H:i A")));
//        for($i = 1; $i <= $emails; $i++) {
//          $blank = ($i == 1) ? false : true;
//          $field_list[] = $form_fields->EmailField(array("verbose_name"=>"Email $i","name"=>"email-$i","blank"=>$blank));
//        }
//        
//        return $field_list;
//      }
//      
//      return array();
//    }
//    
//    /**
//     * - Dynamically define the steps of the wizard.
//     * @return type
//     */
//    protected function get_step_definition_list() {
//      list($definition, $this->promotioncategory, $this->module) = definition($this->request);
//      return $definition;
//    }
//
//    protected function process_step() {
//      $this->delivery = $this->request->cookie->find("delivery", "ffa");
//      
//      // Check that the promotion is valid.
//      if($this->current_step == "marketplace") {
////        if(!count(Promotion::queryset()->filter(array("id"=>$this->form->get("promotion_id")))->fetch())) {
////          \Blink\Messages::error("Promotion not found.");
////          return $this->form_invalid();
////        }
//        
//        $this->request->cookie->set("promotioncategory_id", $this->promotioncategory->id);
//        $this->request->cookie->set("promotion_id", $this->promotion->id);
//        
//      } else if($this->current_step == "announcement") { // ANNOUNCEMENT STEP.
//        // If we are sending a Twitter announcement.
//        $this->request->cookie->set("twitter_announcement", $this->request->post->find("twitter_announcement", 0));
//      }  else if($this->current_step == "delivery") {
//        // If this is an announcement, we check for different values.
//        if($this->module->tag == "announcement") {
//          $this->request->cookie->set("twitter_announcement", $this->request->post->is_set("twitter_announcement"));
//        } else {
//          $this->delivery = $this->form->get("delivery");
//        }
//      } else if($this->current_step == "ffa") {
//        
//      } else if($this->current_step == "mailchimp") {
//        $query = array(
//            "id" => $this->form->get("list_id")
//        );
//        
//        // If there are emails, get the emails for validation.
//        $emails = $this->form->get("emails", null);
//        if(trim($emails)) {
//          $query['emails'] = array();
//          foreach(explode(",", $emails) as $e) {
//            $query['emails'][] = array("euid"=>$e);
//          }
//        }
//        
//        // Check that id is user's.
//        /**
//         * @todo Check the arrangement of the response variable.
//         */
//        $result = \BlinkMailChimp\Api::endpoint("lists/member-info", $query);
//        
//        // If there is an error, exit.
//        if($result['error']) {
//          \Blink\Messages::error(\BlinkMailChimp\Api::error_string($result['error']));
//          return $this->form_invalid();
//        }
//        
//        // Check data error.
//        if(isset($result['data']['error'])) {
//          \Blink\Messages::error($result['data']['error']);
//          return $this->form_invalid();
//        }
//        
//        // If there is an error, display it.
//        if($result['data']['error_count']) {
//          \Blink\Messages::warning("Some subscribers were not found.");
//        }
//        
//        // If zero emails, then, then no-one to send to, exit.
//        if(!$result['data']['success_count']) {
//          \Blink\Messages::error("Could not validate email list.");
//          return $this->form_invalid();
//        }
//        
//        $this->request->cookie->set("quantity", $result['data']['success_count']);
//      } else if($this->current_step == "text") {
//        $number_list = explode(",", $this->form->get("numbers"));
//        
//        if(!count($number_list)) {
//          throw new \Exception("You have some invalid phone numbers. Please enter 10-digit phone numbers");
//        }
//        
//        foreach($number_list as $number) {
//          if(strlen($number) != 10 || !is_int((int) $number)) {
//            \Blink\Messages::error("You have some invalid phone numbers. Please enter 10-digit phone numbers");
//            return $this->form_invalid();
//          }
//        }
//        
//        $this->request->cookie->set("quantity", count($number_list));
//      } else if($this->current_step == "profile") {
//        
//      } else if($this->current_step == "el") {
//        // Check the contact list.
//        $filter = array("id"=>$this->form->get("contact_id"),"wapo_profile.wapo_distributor.user"=>$this->request->user,"type"=>"e");
//        
//        try {
//          $contact = \Wapo\Contact::queryset()->depth(2)->get($filter, "Email List not found.");
//        } catch (\Exception $ex) {
//          return $this->get();
//        }
//        
//        $this->request->cookie->set("profile_id", $contact->profile->id);
//      } else if($this->current_step == "e") {
//        $emails = Config::MAX_EMAIL_DELIVERY_COUNT_GUEST;
//        if($this->request->user) {
//          $emails = Config::MAX_EMAIL_DELIVERY_COUNT_USER;
//        }
//        
//        $quantity = 0;
//        for($i = 1; $i <= $emails; $i++) {
//          if($this->request->post->find("email-$i", null)) {
//            $quantity++;
//          }
//        }
//        $this->request->cookie->set("quantity", $quantity);
//      } else if($this->current_step == "checkout") {
//        
//        list($error, $message, $data) = validate_wapo($this->request);
//        
//        if($error) {
//          \Blink\Messages::error($message);
//          return $this->get();
//        }
//        
//        // For announcement, skip the payment step.
//        if($data['module']->tag != "announcement") {
//          // Redirect to fake pay and then come back to the create.
//          return \Blink\HttpResponseRedirect("/wp/pay/", false);
//        }
//      } else if($this->current_step == "create") {
////        list($error, $message, $wapo) = create_wapo($this->request->cookie->all(), $this->request);
//
//        } else if($this->current_step == "send") {
//        
//      }
//      
//      // Reload the step definition list to get the right next step.
//      $this->step_list = $this->get_step_list();
//      
//
//      return parent::process_step();
//    }
//    
//    // @todo Look at these...
//    protected function get_initial() {
//      // If this is the new profile step, get their current email or one they have previously submitted.
//      if($this->current_step == "new_profile") {
//        if($this->request->user) {
//          return array("email"=>$this->request->cookie->find("email", $this->request->user->email));
//        } else {
//          return array("email"=>$this->request->cookie->find("email"));
//        }
//      }
//      return parent::get_initial();
//    }
//    
//    protected function get_data() {
//      if($this->current_step == "new_profile") {
//        if($this->request->user) {
//          return array("email"=>array("value"=>$this->request->cookie->find("email", $this->request->user->email)));
//        } else {
//          return array("email"=>array("value"=>$this->request->cookie->find("email")));
//        }
//      } else if($this->current_step == "garment-pick") {
//        return array(
//            "category_id" => array(
//                "value" => "ladies-performance-shirts"
//            )
//        );
//      }
//      
//      return parent::get_data();
//    }
//
//    protected function get_context_data() {
//      $context = parent::get_context_data();
//      
//      if($this->current_step == "modules") {// MODULES STEP
//        $context['module_list'] = \Wapo\Module::queryset()->all();
//      } else if($this->current_step == "marketplace") {// MARKETPLACE STEP.
//        $promotioncategory_list = PromotionCategory::queryset()->order_by(array("name"))->all();
//        
//        // Depending on which promotioncategory we are looking at, get the api.
//        if ($this->promotioncategory->name == "Tango Card") {
//          
////          $tango = new \BlinkTangoCard\TangoCardAPI();
////          $context['tangocard'] = $tango->rewards();
//
//          $context['tangocard'] = \Wapo\TangoCardRewards::queryset()->filter(array("unit_price__gt"=>0,"currency_type"=>"USD"))->fetch();
////          \Blink\blink_log($context['tangocard']);
//          
//          
//        } else if ($this->promotioncategory->name == "I Feel Goods") {
//          $context['ifeelgoods'] = \Wapo\IFeelGoodsRewards::queryset()->all();
//        } else if ($this->promotioncategory->name == "Scalable Press") {
//          
//        } else {
//          $context['promotion_list'] = Promotion::queryset()->filter(array("promotioncategory"=>$this->promotioncategory))->fetch();
//          $context['promotion'] = Promotion::get_or_null(array("id"=>$this->request->cookie->find("promotion_id", null)));
//        }
//
//        $context['promotioncategory'] = $this->promotioncategory;
//        $context['promotioncategory_list'] = $promotioncategory_list;
//      } else if($this->current_step == "announcement") { // ANNOUNCEMENT STEP.
//        // Get the twitter info if one exists.
//        $context['twitter_announcement'] = $this->request->cookie->find("twitter_announcement", 0);
//      } else if($this->current_step == "delivery") { // DELIVERY STEP
//        
//        $context['delivery'] = $this->request->cookie->find("delivery", null);
//        
//        // Prepare the FFA form.
//        $context['ffa_form'] = (new DeliveryFFAForm())->Form();
//        
//        // Prepare the email form.
//        $emails = Config::$NotLoggedInMaxEmailDeliveryCount;
//        if($this->request->user) {
//          $emails = Config::$LoggedInMaxEmailDeliveryCount;
//        }
//        
//        $form_fields = new \Blink\FormFields();
//        $field_list = array();
//        for($i = 1; $i <= $emails; $i++) {
//          $blank = ($i == 1) ? false : true;
//          $field_list[] = $form_fields->EmailField(array("verbose_name"=>"Email $i","name"=>"email-$i","blank"=>$blank));
//        }
//        $context['e_form'] = (new \Blink\Form(array(), $field_list))->Form();
//        
//      } else if($this->current_step == "el") {
//        $contact_id = $this->request->cookie->find("contact_id");
//        $filter = array("wapo_profile.wapo_distributor.user"=>$this->request->user,"type"=>"e");
//        $contact_list = \Wapo\Contact::queryset()->depth(2)->filter($filter)->fetch();
//        $context['contact_id'] = $contact_id;
//        $context['contact_list'] = $contact_list;
//      }  else if($this->current_step == "profiles") {
//        // If they are logged in, get their profile list.
//        if($this->request->user) {
//          // Check if the user has profiles and if not, create the profile (which requires a distributor).
//          $profiles = Profile::aggregate()->count(array(array("id", "profiles")))->filter(array("wapo_distributor.user"=>$this->request->user))->get();
//          if(!$profiles->profiles) {
//            $distributor = Distributor::get_or_create(array("user"=>$this->request->user), false);
//            $context['profile'] = Profile::get_or_create_save(array("distributor"=>$distributor, "name"=>"Default Profile"), array(), false);
//          }
//          
//          // If this is an email list, just get that one profile.
//          if($this->request->cookie->find("delivery") == "el") {
//            $context['profile'] = Profile::get_or_404(array("id"=>$this->request->cookie->find("profile_id"),"wapo_distributor.user"=>$this->request->user));
//            $context['profile_list'] = array($context['profile']);
//          } else {
//            // Get the profile list.
//            $context['profile_list'] = Profile::queryset()->filter(array("wapo_distributor.user"=>$this->request->user))->fetch();
//
//            // If a profile id exists, get that profile.
//            if($this->request->cookie->is_set("profile_id") && !isset($context['profile'])) {
//              $context['profile'] = Profile::get_or_404(array("id"=>$this->request->cookie->get("profile_id"),"wapo_distributor.user"=>$this->request->user));
//            } else if(count($context['profile_list']) == 1) {
//              // If we have one profile, set it as the selected one.
//              $context['profile'] = $context['profile_list'][0];
//            }
//          }
//        }
//        
//      } else if($this->current_step == "checkout") {
//        // Get all the cookie data.
//        $cookies = $this->request->cookie->to_array();
//        
//        if($this->module->tag == "announcement") {
//          $context['announcement'] = $this->request->cookie->find("announcement");
//        } else {
//          
//          
//          // Check the promotion.
//          $context['promotion'] = Promotion::get_or_404(array("id"=>$this->request->cookie->find('promotion_id')), "Promotion you selected not found.");
//          $context['sku'] = \Wapo\TangoCardRewards::get_or_null(array("sku"=>$this->request->cookie->find("sku")));
//          $context['promotioncategory'] = $context['promotion']->promotioncategory;
//          $context['amount'] = $this->request->cookie->find("amount");
//
//          // Get the delivery method.
//          $context['delivery'] = $cookies['delivery'];
//          $context['delivery_name'] = isset(Config::$DeliveryMethod[$context['delivery']]) ? Config::$DeliveryMethod[$context['delivery']] : "";
//
//          // Get the data based on delivery method.
//          $context['quantity'] = $cookies['quantity'];
//
//          // Get delivery message and expiring date..
//          $context['delivery_message'] = $cookies['delivery_message'];
//          $context['expiring_date'] = $cookies['expiring_date'];
//
//          // Get profile informaiton.
//          // If profile is set, get it.
//          if(isset($cookies['profile_id'])) {
//            $context['profile'] = Profile::get_or_404(array("id"=>$cookies['profile_id'],"wapo_distributor.user"=>$this->request->user), "Profile you selected was not found.");
//          } else {
//            $context['name'] = (isset($cookies['name'])) ? $cookies['name'] : '';
//            $context['email'] = (isset($cookies['email'])) ? $cookies['email'] : '';
//          }
//        }
//      } else if($this->current_step == "create") {
//        $this->request->session->set("checkoutid", $this->request->get->find("checkoutid"));
//      } else if($this->current_step == "done") {
//        // Check if we still have the promotion send id. If we do, get the info.
//        $wapo = Wapo::get_or_404(array("id"=>$this->request->session->find("wapo_id", 4)));
//        $context['targeturl_list'] = \Wapo\WapoTargetUrl::queryset()->filter(array("wapo"=>$wapo))->fetch();
//        $context['wapo'] = $wapo;
//        $context['not_sent'] = WapoRecipient::queryset()->filter(array("wapo"=>$wapo,"sent"=>false))->total();
//      }
//      
//      // Get the main steps rather than the current_steps to display the form progress.
//      $context['main_step'] = "delivery";
//      if(in_array($this->current_step, array("new_profile", "profiles"))) {
//        $context['main_step'] = "profile";
//      } else if($this->current_step == "checkout") {
//        $context['main_step'] = $this->current_step;
//      } else if($this->current_step == "marketplace") {
//        $context['main_step'] = "marketplace";
//      } else if(in_array($this->current_step, array("create", "send"))) {
//        $context['main_step'] = "checkout";
//      } else if($this->current_step == "done") {
//        $context['main_step'] = "confirmation";
//      }
//      
//      /**
//       * Get data for progress bar.
//       */
//      
//      // If they are logged in.
//      $progress = array(
//          "profile" => array(
//              "profile" => null,
//              "name" => $this->request->cookie->find("name"),
//              "email" => $this->request->cookie->find("email")
//          )
//      );
//      if($this->request->user) {
//        // If profile is set.
//        if($this->request->cookie->is_set("profile_id")) {
//          $progress['profile']['profile'] = Profile::get_or_null(array("id"=>$this->request->cookie->get("profile_id")));
//        }
//      }
//      
//      $context['progress'] = $progress;
//      $context['module'] = $this->module;
//      
//      return $context;
//    }
//    
//    protected function post() {
//      $this->delivery = $this->request->cookie->find("delivery", "ffa");
//      return parent::post();
//    }
//    
//    protected function get() {
//      $this->delivery = $this->request->cookie->find("delivery", "ffa");
//      return parent::get();
//    }
//  }
//  
//}
