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

  require_once("apps/blink-user/api.php");
  require_once("apps/wepay/api.php");

  require_once("apps/blink-user-role/api.php");

  require_once("apps/swiftmailer/api.php");

  use Wapo\PromotionCategory;
  use Wapo\Promotion;
  use Wapo\Distributor;
  use Wapo\Profile;
  use Wapo\Wapo;
  use Wapo\WapoRecipient;
  use Wapo\Helper;
  use Wapo\Contact;
  use Wapo\ContactItem;
  use Wapo\Member;

  /**
   * 
   * @param mixed $cookies
   * @param \Blink\Request $request
   * @throws \Exception
   */
  function validate_cart($cookies, $request) {
    // Verify that we can send this...
    try {
      // If promotion is not valid, this is an error.
      if (!isset($cookies['promotion_id']) || !count(Promotion::queryset()->filter(array("id" => $cookies['promotion_id']))->fetch())) {
        throw new \Exception("Promotion not selected.");
      }

      // Check that delivery method is valid.
      $delivery_methods = array("ffa", "e", "el", "aff", "fp", "atf", "stf", "aif", "sif");
      if (!isset($cookies['delivery']) && !in_array($cookies['delivery'], $delivery_methods)) {
        throw new \Exception("Delivery method not found.");
      }

      // Check that we have an expiration date.
      if (!isset($cookies['expiring_date'])) {
        throw new \Exception("Please set the promotion expiring date.");
      }

      // Check that the date is valid and that it is greater than today.
      $expiring_date = \DateTime::createFromFormat("m/d/Y H:i A", $cookies['expiring_date']);
      $error = \DateTime::getLastErrors();
      if ($error['error_count']) {
        throw new \Exception("Please select a valid expiring date.");
      }

      // Check that expiring date is at least one hour in the future.
      $ed = new \DateTime($expiring_date->format("Y-m-d H:i:s"));
      $now = new \DateTime(date("Y-m-d H:i:s"));
      $diff = $ed->diff($now);
      if ($diff->d < 1 && $diff->h < 1) {
        throw new \Exception("Please select a valid expiring date. Expiring date must be at least 1 hour in the future.");
      }

      // For each delivery method, check that we have all variables set.
      $delivery = $cookies['delivery'];
      if ($delivery == "ffa") {
        if (!isset($cookies['quantity']) && $cookies['quantity'] < 1) {
          throw new \Exception("Please select a quantity for Free For All Delivery.");
        }
      } else if ($delivery == "e") {
        $emails = Config::$NotLoggedInMaxEmailDeliveryCount;
        if($request->user) {
          $emails = Config::$LoggedInMaxEmailDeliveryCount;
        }
        
        $recipient_count = 0;
        for($i = 1; $i <= $emails; $i++) {
          if(isset($cookies["email-$i"])) {
            $recipient_count++;
          }
        }

        // Check counts and such.
        if (!$recipient_count) {
          throw new \Exception("Please enter at least one email for 'email' delivery.");
        }
      } else if ($delivery == "el") {
        // Check that they are logged in.
        if (!$this->request->user) {
          throw new \Exception("Please log in to use this delivery method.");
        }

        // Check that the contact is theirs and that it is an email list.
        if (!isset($cookies['contact_id']) || count(Contact::queryset()->depth(2)->filter(array("id" => $cookies['contact_id'], "wapo_distributor.user" => $this->request->user, "type" => "e"))->fetch())) {
          throw new \Exception("Please select a valid email list.");
        }
      } else if ($delivery == "aff") {
        if (!isset($cookies['facebook_id'])) {
          throw new \Exception("Please log in to Facebook to continue.");
        }

        // Check that they have a quantity.
        if (!isset($cookies['quantity']) || $cookies['quantity'] < 1) {
          throw new \Exception("Please select a quantity for Any Facebook Friend Delivery.");
        }
      } else if ($delivery == "fp") {
        if (!isset($cookies['facebook_page_id']) || !$cookies['facebook_page_id']) {
          throw new \Exception("Please select Facebook Page.");
        }

        // Check that they have a quantity.
        if (!isset($cookies['quantity']) || $cookies['quantity'] < 1) {
          throw new \Exception("Please select a quantity for Facebook Page Delivery.");
        }
      } else if ($delivery == "atf") {
        if (!isset($cookies['quantity']) && $cookies['quantity'] < 1) {
          throw new \Exception("Please select a quantity for Any Twitter Follower Delivery.");
        }
      } else if ($delivery == "stf") {
        if (!isset($cookies['twitter_followers']) && count(explode(",", $cookies['twitter_followers'])) < 1) {
          throw new \Exception("Please select at least one Twitter Follower.");
        }
      } else if ($delivery == "aif") {
        if (!isset($cookies['quantity']) && $cookies['quantity'] < 1) {
          throw new \Exception("Please select a quantity for Any Instagram Follower Delivery.");
        }
      } else if ($delivery == "sif") {
        if (!isset($cookies['instagram_followers']) && count(explode(",", $cookies['instagram_followers'])) < 1) {
          throw new \Exception("Please select at least one Instagram Follower.");
        }
      } else if($delivery == "mailchimp") {
        $query = array(
            "id"=>$id = $request->cookie->find("list_id", null)
        );
        
        // If there are emails, get the emails for validation.
        $emails = $request->cookie->find("emails", null);
        if(trim($emails)) {
          $query['emails'] = array();
          foreach(explode(",", $emails) as $e) {
            $query['emails'][] = array("euid"=>$e);
          }
        }
        
        // Check that id is user's.
        $result = \BlinkMailChimp\Api::endpoint("lists/member-info", $query);
        
        // If there is an error, display it.
        if($result['error']) {
          throw new \Exception(\BlinkMailChimp\Api::error_string($result['error']));
        }
        
        // Check data error.
        if(isset($result['data']['error'])) {
          throw new \Exception($result['data']['error']);
        }
      }
    } catch (\Exception $ex) {
      return array(true, $ex->getMessage());
    }
    
    return array(false, "");
  }
  
  /**
   * 
   * @param mixed $cookies
   * @param \Blink\Request $request
   * @throws \Exception
   */
  function create_wapo($cookies, $request) {
    try {
      // Validate the cart again.
      list($error, $message) = validate_cart($cookies, $request);
      if($error) {
        throw new \Exception($message);
      }
      
      // Check that checkoutid is set.
      if(!$request->session->is_set("checkoutid")) {
        throw new \Exception("Checkout error. Payment transaction did not complete.");
      }
      
      $profile = null;

      // If they are logged in, get or create a profile.
      if ($request->user) {
        if (isset($cookies['profile_id'])) {
          $profile = Profile::queryset()->get(array("id"=>$cookies['profile_id']));
        } else {
          // Create a new profile.
          $distributor = Distributor::get_or_create_save(array("user"=>$request->user), false);
          $profile = Profile::create_save(array("distributor" => $distributor, "name" => $cookies['name']), false);
        }
      } else {
        // Check if the user exists.
        $user_list = \User\User::queryset()->filter(array("email"=>$cookies['email']))->fetch();
        $user = null;
        if(count($user_list)) {
          $user = $user_list[0];
        } else {
          // If not logged in, means they are using email so create an account.
          list($error, $message, $user) = \User\Api::create_user(array("email"=>$cookies["email"]), true);
        }
        
        // Create their distributor and profile.
        $distributor = Distributor::get_or_create_save(array("user"=>$user), false);
        $profile = Profile::get_or_create_save(array("distributor"=>$distributor, "name"=>$cookies["name"]));
      }
      
      
      // Get the external id.
      $external = "";
      switch($cookies['delivery']) {
        case "aff":
          $external = $cookies['facebook_id'];
          break;
        case "fp":
          $external = $cookies['facebook_page_id'];
          break;
        case "aif":
          $external = $cookies['instagram_id'];
          break;
        case "sif":
          $external = $cookies['instagram_id'];
          break;
      }
      
      // If Twitter, we get the screen_name.
      if(in_array($cookies['delivery'], array("atf", "stf"))) {
        $connection = new \TwitterOAuth(\Blink\ConfigTwitter::$ConsumerKey, \Blink\ConfigTwitter::$ConsumerSecret, $request->session->nmsp("twitter")->get('oauth_token'), $request->session->nmsp("twitter")->get('oauth_token_secret'));
        $account = $connection->get('account/verify_credentials');
        $external = $account->screen_name;
      }

      // Create the wapo.
      $create = array(
          "profile" => $profile,
          "promotion" => $cookies["promotion_id"],
          "payment_method" => \Wapo\PaymentMethod::queryset()->get(array("name"=>"WePay")),
          "delivery_method_abbr" => $cookies['delivery'],
          "delivery_method" => Config::$DeliveryMethod[$cookies['delivery']],
          "external" => $external,
          "delivery_message" => $cookies["delivery_message"],
          "expiring_date" => $cookies["expiring_date"],
          "status" => "p",
          "checkoutid" => $request->session->get_delete("checkoutid"),
          "quantity" => $request->cookie->find("quantity", 0),
          "downloaded" => 0,
          "email_confirmation"=> 1// Always to true.
      );
      $wapo = Wapo::create_save($create, false);
      $request->session->set("wapo_id", $wapo->id);

      if($cookies['delivery'] == "ffa") {
        foreach(array("f", "t", "i", "gp", "g", "p") as $p) {
          $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, $p);
        }
      } else if ($cookies['delivery'] == "stf") {
        $twitter_followers = explode(",", $cookies['twitter_followers']);
        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "t");

        // Add each of them to the database, sent is false and will be updated once it has been sent.
        foreach ($twitter_followers as $tid) {
          $recipient = array(
              "targeturl" => $targeturl,
              "wapo"      => $wapo,
              "contact"   => trim($tid),
          );
          WapoRecipient::create_save($recipient, false);
        }
        
        $wapo->quantity = count($twitter_followers);
        $wapo->save(false);
      } else if ($cookies['delivery'] == "atf") {
        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "t");
      } else if ($cookies['delivery'] == "sif") {
        $instagram_followers = explode(",", $cookies['instagram_followers']);
        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "t");
        
        // Add each of them to the database, sent is false and will be updated once it has been sent.
        foreach ($instagram_followers as $iid) {
          $recipient = array(
              "targeturl" => $targeturl,
              "wapo"      => $wapo,
              "contact"   => trim($tid),
          );
          WapoRecipient::create_save($recipient, false);
        }
        $wapo->quantity = count($instagram_followers);
        $wapo->save(false);
      } else if ($cookies['delivery'] == "e") {
        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "e");
        $email_count = ($request->user) ? Config::$LoggedInMaxEmailDeliveryCount : Config::$NotLoggedInMaxEmailDeliveryCount;
        
        $recipient_count = 0;
        for($i = 1; $i <= $email_count; $i++) {
          if(isset($cookies["email-$i"])) {
            $recipient = array(
                "wapo"      => $wapo,
                "targeturl" => $targeturl,
                "contact"   => trim($cookies[sprintf("email-%s", $i)]),
            );
            WapoRecipient::create_save($recipient, false);
            $recipient_count++;
          }
        }
        
        $wapo->quantity = $recipient_count;
        $wapo->save(false);
      } else if ($cookies['delivery'] == "el") {
        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "el");
        $email_list = \Wapo\ContactItem::queryset()->select(array("item"))->filter(array("contact"=>$cookies['contact_id']));
        
        foreach($email_list as $email) {
          $recipient = array(
              "wapo"      => $wapo,
              "targeturl" => $targeturl,
              "contact"   => trim($email->email),
          );
          WapoRecipient::create_save($recipient, false);
        }
        
        $wapo->quantity = count($email_list);
        $wapo->save(false);
      } else if ($cookies['delivery'] == "aff") {
        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "f");
      } else if ($cookies['delivery'] == "fp") {
        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "f");
      } else if($cookies['delivery'] == "mailchimp") {
        $query = array(
            "id"=>$id = $request->cookie->find("list_id", null)
        );
        
        // If there are emails, get the emails for validation.
        $emails = $request->cookie->find("emails", null);
        if(trim($emails)) {
          $query['emails'] = array();
          foreach(explode(",", $emails) as $e) {
            $query['emails'][] = array("euid"=>$e);
          }
        }
        
        // Check that id is user's.
        $result = \BlinkMailChimp\Api::endpoint("lists/member-info", $query);
        
        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "m");
        $sent_emails = 0;
        
        if(!$result['error'] && !isset($result['data']['error'])) {
          foreach($result['data']['data'] as $e) {
            $recipient = array(
                "wapo" => $wapo,
                "targeturl" => $targeturl,
                "name" => trim(sprintf("%s %s", $e['merges']['FNAME'], $e['merges']['LNAME'])),
                "contact" => trim($e['email']),
            );
            WapoRecipient::create_save($recipient, false);
            $sent_emails++;
          }
        }
        
        $wapo->quantity = $sent_emails;
        $wapo->save(false);
        
      }
    } catch (\Exception $ex) {
      return array(true, $ex->getMessage(), null);
    }
    
    return array(false, "", $wapo);
  }

  /**
   * - Process the Wapo Promotion steps.
   * - Step 1 - Marketplace
   *    - Display a list of promotional items to buy. 
   * - Step 2 - Delivery
   *    - Display a list of delivery methods.
   * - Step 3 - Depends on step 2.
   *    - If ffa - Free For All, display a general quantity form.
   *    - If email - Show fields for them to enter emaails.
   *    - If email list - Show their lists (grouped by profile).
   *        - If they are not logged in, force them to log in.
   *    - If anyff - Any Facebook Friends, display a general quantity form.
   *        - If they are not logged in, show them a link to log in.
   *    - If sff - A form with hidden fields to capture their Friends' ids. 
   *        - If they are not logged in, show them a link to log in.
   *    - If fp - A form with hidden fields to capture their Page id. 
   *        - If they are not logged in, show them a link to log in.
   * - Step 4 - Profile
   *    - Show them profile options. 
   *        - If Facebook, show Facebook Profile options that they can save.
   *        - If email and logged in, allow them to select their profile.
   *        - If email and not logged in, show them register form or ask them to log in.
   * - Step 5 - Checkout
   *    - Show checkout options (redirect to checkout).
   * - Step 6 - Confirmation
   *    - Display confirmation based on delivery method.
   */
  class WpCookieWizardView extends \Blink\CookieWizardView {
    protected $delivery;
    
    /**
     * Override certain forms. To do this, form for this step *must be a 'Blink\Form'.
     * @return type
     */
    protected function get_form_fields() {
      // For email step, we want to make a dynamic form. Use generic 'Blink\Form' class for email.
      if($this->current_step == "e") {
        $emails = Config::$NotLoggedInMaxEmailDeliveryCount;
        if($this->request->user) {
          $emails = Config::$LoggedInMaxEmailDeliveryCount;
        }
        
        $form_fields = new \Blink\FormFields();
        $field_list = array();
        $field_list[] = $form_fields->TextField(array("name"=>"delivery_message","blank"=>true));
        $field_list[] = $form_fields->DateTimeField(array("name"=>"expiring_date","format"=>"m/d/Y H:i A","min_value"=>date("m/d/Y H:i A")));
        for($i = 1; $i <= $emails; $i++) {
          $blank = ($i == 1) ? false : true;
          $field_list[] = $form_fields->EmailField(array("verbose_name"=>"Email $i","name"=>"email-$i","blank"=>$blank));
        }
        
        return $field_list;
      }
      
      return array();
    }
    
    /**
     * - Dynamically define the steps of the wizard.
     * @return type
     */
    protected function get_step_definition_list() {
      // Define the initial steps which are always 'marketplace' and 'delivery' method.
      $definition = array(
          "modules" => array(
              "title" => "Modules",
              "template" => ConfigTemplate::DefaultTemplate("pipeline/modules.twig"),
              "form" => "\Blink\Form"
          ),
          "marketplace" => array(
              "title" => "Marketplace",
              "template" => ConfigTemplate::DefaultTemplate("pipeline/marketplace.twig"),
              "form" => "\Wp\MarketplaceForm"
          ),
          "delivery" => array(
              "title" => "Delivery",
              "template" => ConfigTemplate::DefaultTemplate("pipeline/delivery.twig"),
              "form" => "\Wp\DeliveryForm"
          )
      );
      
      // Define the conditional steps based on the delivery method.
      if($this->delivery == "ffa") {
        $definition["ffa"] = array(
            "title" => "Free For All",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/free_for_all.twig"),
            "form" => "\Wp\FreeForAllForm"
        );
      } else if($this->delivery == "e") {
        $definition["e"] = array(
            "title" => "Email",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/email.twig"),
            "form" => "\Blink\Form"
        );
      } else if($this->delivery == "el") {
        $definition["el"] = array(
            "title" => "Email List",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/email_list.twig"),
            "form" => "\Wp\EmailListForm"
        );
      } else if($this->delivery == "mailchimp") {
        $definition["mailchimp"] = array(
            "title" => "MailChimp",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/mailchimp.twig"),
            "form" => "\Wp\MailChimpForm"
        );
      } else if($this->delivery == "aff") {
        $definition["aff"] = array(
            "title" => "Any Facebook Friend",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/any_facebook_friends.twig"),
            "form" => "\Wp\AnyFacebookFriendsForm"
        );
      } else if($this->delivery == "sff") {
        $definition["sff"] = array(
            "title" => "Facebook Friends",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/select_facebook_friends.twig"),
            "form" => "\Wp\FacebookFriendsForm"
        );
      } else if($this->delivery == "fp") {
        $definition["fp"] = array(
            "title" => "Facebook Page Followers",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/facebook_page.twig"),
            "form" => "\Wp\FacebookPageForm"
        );
      } else if($this->delivery == "atf") {
        $definition["atf"] = array(
            "title" => "Any Twitter Followers",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/any_twitter_followers.twig"),
            "form" => "\Wp\GenericQuantityForm"
        );
      } else if($this->delivery == "stf") {
        $definition["stf"] = array(
            "title" => "Select Twitter Followers",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/select_twitter_followers.twig"),
            "form" => "\Wp\SelectTwitterFollowersForm"
        );
      } else if($this->delivery == "aif") {
        $definition["aif"] = array(
            "title" => "Any Instagram Followers",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/any_instagram_followers.twig"),
            "form" => "\Wp\GenericQuantityForm"
        );
      } else if($this->delivery == "sif") {
        $definition["sif"] = array(
            "title" => "Select Instagram Followers",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/select_instagram_followers.twig"),
            "form" => "\Wp\SelectInstagramFollowersForm"
        );
      }
      
      // Profile step.
      
      // If this is a post, check if this is the profile step.
      if($this->request->method == "post") {
        // If we are logged in and we are posting a name, then this is probably a new profile, so get the new profile step.
        // Otherwise get the profiles step.
        if($this->request->user && $this->request->post->find("name")) {
          $definition["new_profile"] = array(
              "title" => "New Profile",
              "template" => ConfigTemplate::DefaultTemplate("pipeline/new_profile.twig"),
              "form" => "\Wp\NewProfileForm"
          );
        } else if($this->request->user) {
          // If they have profiles, give them the list, otherwise they create a new one here...
          $profiles = Profile::aggregate()->count(array(array("id", "profiles")))->filter(array("wapo_distributor.user"=>$this->request->user))->get();
          if ($profiles->profiles) {
            $definition["profiles"] = array(
                "title" => "Profile",
                "template" => ConfigTemplate::DefaultTemplate("pipeline/profiles.twig"),
                "form" => "\Wp\ProfilesForm"
            );
          } else {
            $definition["new_profile"] = array(
                "title" => "New Profile",
                "template" => ConfigTemplate::DefaultTemplate("pipeline/new_profile.twig"),
                "form" => "\Wp\NewProfileForm"
            );
          }
        } else {
          // If not logged in, then they are creating a new profile.
          $definition["new_profile"] = array(
              "title" => "New Profile",
              "template" => ConfigTemplate::DefaultTemplate("pipeline/new_profile.twig"),
              "form" => "\Wp\NewProfileForm"
          );
        }
      } else {
        // If this is a get and they are logged in...
        if($this->request->user) {
          $profiles = Profile::aggregate()->count(array(array("id", "profiles")))->filter(array("wapo_distributor.user"=>$this->request->user))->get();
          if ($profiles->profiles) {
            $definition["profiles"] = array(
                "title" => "Profile",
                "template" => ConfigTemplate::DefaultTemplate("pipeline/profiles.twig"),
                "form" => "\Wp\ProfilesForm"
            );
          } else {
            $definition["new_profile"] = array(
                "title" => "New Profile",
                "template" => ConfigTemplate::DefaultTemplate("pipeline/new_profile.twig"),
                "form" => "\Wp\NewProfileForm"
            );
          }
        } else {
          // If not logged in, then they are creating a new profile.
          $definition["new_profile"] = array(
              "title" => "New Profile",
              "template" => ConfigTemplate::DefaultTemplate("pipeline/new_profile.twig"),
              "form" => "\Wp\NewProfileForm"
          );
        }
      }
      
      // Checkout (ready to go pay).
      $definition["checkout"] = array(
          "title" => "Checkout",
          "template" => ConfigTemplate::DefaultTemplate("pipeline/checkout.twig"),
          "form" => "Wp\PaymentMethodForm"
      );
      
      // Create step (once they have paid).
      $definition["create"] = array(
          "title" => "Create",
          "template" => ConfigTemplate::DefaultTemplate("pipeline/create.twig"),
          "form" => "Blink\Form"
      );
      
      $delivery = $this->request->cookie->find("delivery", "");
      if(!in_array($delivery, array("ffa"))) {
        // Send step.
        $definition["send"] = array(
            "title" => "Send",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/send.twig"),
            "form" => "Blink\Form"
        );
      }
      
      // Add the done step.
      $definition["done"] = array(
          "title" => "Confirmation",
          "template" => ConfigTemplate::DefaultTemplate("pipeline/confirmation.twig"),
          "form" => "Blink\Form"
      );

      return $definition;
    }

    protected function process_step() {
      $this->delivery = $this->request->cookie->find("delivery", "ffa");
      
      // Check that the promotion is valid.
      if($this->current_step == "marketplace") {
        if(!count(Promotion::queryset()->filter(array("id"=>$this->form->get("promotion_id")))->fetch())) {
          \Blink\Messages::error("Promotion not found.");
          return $this->form_invalid();
        }
        
      } else if($this->current_step == "delivery") {
        $this->delivery = $this->form->get("delivery");
      } else if($this->current_step == "ffa") {
        
      } else if($this->current_step == "mailchimp") {
        $query = array(
            "id" => $this->form->get("list_id")
        );
        
        // If there are emails, get the emails for validation.
        $emails = $this->form->get("emails", null);
        if(trim($emails)) {
          $query['emails'] = array();
          foreach(explode(",", $emails) as $e) {
            $query['emails'][] = array("euid"=>$e);
          }
        }
        
        // Check that id is user's.
        /**
         * @todo Check the arrangement of the response variable.
         */
        $result = \BlinkMailChimp\Api::endpoint("lists/member-info", $query);
        
        // If there is an error, exit.
        if($result['error']) {
          \Blink\Messages::error(\BlinkMailChimp\Api::error_string($result['error']));
          return $this->form_invalid();
        }
        
        // Check data error.
        if(isset($result['data']['error'])) {
          \Blink\Messages::error($result['data']['error']);
          return $this->form_invalid();
        }
        
        // If there is an error, display it.
        if($result['data']['error_count']) {
          \Blink\Messages::warning("Some subscribers were not found.");
        }
        
        // If zero emails, then, then no-one to send to, exit.
        if(!$result['data']['success_count']) {
          \Blink\Messages::error("Could not validate email list.");
          return $this->form_invalid();
        }
        
        $this->request->cookie->set("quantity", $result['data']['success_count']);
      } else if($this->current_step == "profile") {
        
      } else if($this->current_step == "el") {
        // Check the contact list.
        $filter = array("id"=>$this->form->get("contact_id"),"wapo_profile.wapo_distributor.user"=>$this->request->user,"type"=>"e");
        
        try {
          $contact = \Wapo\Contact::queryset()->depth(2)->get($filter, "Email List not found.");
        } catch (\Exception $ex) {
          return $this->get();
        }
        
        $this->request->cookie->set("profile_id", $contact->profile->id);
      } else if($this->current_step == "checkout") {
        
        list($error, $message) = validate_cart($this->request->cookie->all(), $this->request);
        
        if($error) {
          \Blink\Messages::error($message);
          return $this->get();
        }
        
        // Redirect to fake pay and then come back to the create.
        return \Blink\HttpResponseRedirect("/wp/pay/", false);
      } else if($this->current_step == "create") {
        
      } else if($this->current_step == "send") {
        
      }
      
      // Reload the step definition list to get the right next step.
      $this->step_list = $this->get_step_list();
      

      return parent::process_step();
    }
    
    // @todo Look at these...
    protected function get_initial() {
      \Blink\log($this->current_step);
      // If this is the new profile step, get their current email or one they have previously submitted.
      if($this->current_step == "new_profile") {
        if($this->request->user) {
          return array("email"=>$this->request->cookie->find("email", $this->request->user->email));
        } else {
          return array("email"=>$this->request->cookie->find("email"));
        }
      }
      return parent::get_initial();
    }
    
    protected function get_data() {
      if($this->current_step == "new_profile") {
        if($this->request->user) {
          return array("email"=>array("value"=>$this->request->cookie->find("email", $this->request->user->email)));
        } else {
          return array("email"=>array("value"=>$this->request->cookie->find("email")));
        }
      }
      
      return parent::get_data();
    }

    protected function get_context_data() {
      $context = parent::get_context_data();

      if($this->current_step == "marketplace") {
        $context['promotioncategory_list'] = PromotionCategory::queryset()->order_by(array("name"))->all();
        try {
          $context['promotioncategory'] = Promotion::queryset()->get(array("id"=>$this->request->get->find("promotioncategory_id", 1)));
        } catch (\Exception $ex) {
          $context['promotioncategory'] = (count($context['promotioncategory_list'])) ? $context['promotioncategory_list'][0] : null;
        }
        $context['promotion_list'] = Promotion::queryset()->filter(array("promotioncategory"=>$context['promotioncategory']))->fetch();
        
        // Get the promotino if any.
        if($this->request->cookie->is_set("promotion_id")) {
          try {
            $context['promotion'] = Promotion::queryset()->get(array("id"=>$this->request->cookie->get("promotion_id")));
          } catch (\Exception $ex) {
            $context['promotion'] = null;
          }
        }
      } else if($this->current_step == "delivery") {
        $context['delivery'] = $this->request->cookie->find("delivery", null);
      } else if($this->current_step == "el") {
        $contact_id = $this->request->cookie->find("contact_id");
        $filter = array("wapo_profile.wapo_distributor.user"=>$this->request->user,"type"=>"e");
        $contact_list = \Wapo\Contact::queryset()->depth(2)->filter($filter)->fetch();
        $context['contact_id'] = $contact_id;
        $context['contact_list'] = $contact_list;
      }  else if($this->current_step == "profiles") {
        // If they are logged in, get their profile list.
        if($this->request->user) {
          // Check if the user has profiles and if not, create the profile (which requires a distributor).
          $profiles = Profile::aggregate()->count(array(array("id", "profiles")))->filter(array("wapo_distributor.user"=>$this->request->user))->get();
          if(!$profiles->profiles) {
            $distributor = Distributor::get_or_create(array("user"=>$this->request->user), false);
            $context['profile'] = Profile::get_or_create_save(array("distributor"=>$distributor, "name"=>"Default Profile"), false);
          }
          
          // If this is an email list, just get that one profile.
          if($this->request->cookie->find("delivery") == "el") {
            $context['profile'] = Profile::get_or_404(array("id"=>$this->request->cookie->find("profile_id"),"wapo_distributor.user"=>$this->request->user));
            $context['profile_list'] = array($context['profile']);
          } else {
            // Get the profile list.
            $context['profile_list'] = Profile::queryset()->filter(array("wapo_distributor.user"=>$this->request->user))->fetch();

            // If a profile id exists, get that profile.
            if($this->request->cookie->is_set("profile_id") && !isset($context['profile'])) {
              $context['profile'] = Profile::get_or_404(array("id"=>$this->request->cookie->get("profile_id"),"wapo_distributor.user"=>$this->request->user));
            } else if(count($context['profile_list']) == 1) {
              // If we have one profile, set it as the selected one.
              $context['profile'] = $context['profile_list'][0];
            }
          }
        }
        
      } else if($this->current_step == "checkout") {
        // Get all the cookie data.
        $cookies = $this->request->cookie->to_array();
        
        // Check the promotion.
        $context['promotion'] = Promotion::get_or_404(array("id"=>$cookies['promotion_id']), "Promotion you selected not found.");
        
        // Get the delivery method.
        $context['delivery'] = $cookies['delivery'];
        $context['delivery_name'] = isset(Config::$DeliveryMethod[$context['delivery']]) ? Config::$DeliveryMethod[$context['delivery']] : "";
        
        // Get the data based on delivery method.
        $context['quantity'] = $cookies['quantity'];
        
        // Get delivery message and expiring date..
        $context['delivery_message'] = $cookies['delivery_message'];
        $context['expiring_date'] = $cookies['expiring_date'];
        
        // Get profile informaiton.
        // If profile is set, get it.
        if(isset($cookies['profile_id'])) {
          $context['profile'] = Profile::get_or_404(array("id"=>$cookies['profile_id'],"wapo_distributor.user"=>$this->request->user), "Profile you selected was not found.");
        } else {
          $context['name'] = $cookies['name'];
          $context['email'] = $cookies['email'];
        }
      } else if($this->current_step == "create") {
        $this->request->session->set("checkoutid", $this->request->get->find("checkoutid"));
      } else if($this->current_step == "done") {
        // Check if we still have the promotion send id. If we do, get the info.
        $wapo = Wapo::get_or_404(array("id"=>$this->request->session->find("wapo_id", 4)));
        $context['targeturl_list'] = \Wapo\WapoTargetUrl::queryset()->filter(array("wapo"=>$wapo))->fetch();
        $context['wapo'] = $wapo;
      }
      
      // Get the main steps rather than the current_steps to display the form progress.
      $context['main_step'] = "delivery";
      if(in_array($this->current_step, array("new_profile", "profiles"))) {
        $context['main_step'] = "profile";
      } else if($this->current_step == "checkout") {
        $context['main_step'] = $this->current_step;
      } else if($this->current_step == "marketplace" || $this->current_step == "modules") {
        $context['main_step'] = "marketplace";
      } else if(in_array($this->current_step, array("create", "send"))) {
        $context['main_step'] = "checkout";
      } else if($this->current_step == "done") {
        $context['main_step'] = "confirmation";
      }

      return $context;
    }
    
    protected function post() {
      $this->delivery = $this->request->cookie->find("delivery", "ffa");
      return parent::post();
    }
    
    protected function get() {
      $this->delivery = $this->request->cookie->find("delivery", "ffa");
      return parent::get();
    }
  }

  /**
   * - Clears the cookies and starts from the beginning.
   */
  class StartOverRedirectView extends \Blink\RedirectView {

    public function get_redirect_url() {
      // Clear cookies and return to the beginning of pipeline.
      Helper::clear_cookies($this->request->cookie);
      return "/wp/";
    }

  }

  /**
   * - Create a wapo in the create step of the pipeline.
   */
  class CreateWapoView extends \Blink\TemplateView {
    protected function get_content_type() {
      return \Blink\View::CONTENT_JSON;
    }
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      list($error, $message, $wapo) = create_wapo($this->request->cookie->all(), $this->request);
      $c['error'] = $error;
      $c['message'] = $message;
      $c['wapo'] = $wapo;
      return $c;
    }
  }
  
  /**
   * - Performs the send of a Wapo depending on the delivery method.
   */
  class SendWapoView extends \Blink\TemplateView {
    protected function get_content_type() {
      return \Blink\View::CONTENT_JSON;
    }
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      
      $delivery = $this->request->cookie->find("delivery");
      $site = \Blink\ConfigSite::$Site;
      
      try {
        $wapo = Wapo::queryset()->get(array("id" => $this->request->session->find("wapo_id")), "Wapo not found.");
        $delivery_message = $wapo->delivery_message;
        $message = "";
        
        if($delivery == "e" || $delivery == "el" || $delivery == "mailchimp") {
          $mail = \Swift\Api::Message();

          foreach(WapoRecipient::queryset()->filter(array("wapo" => $wapo))->fetch() as $recipient) {
  //          $mail->setSubject(sprintf("%s has contacted us.", $this->form->get("name")));
  //          $mail->setFrom(array($this->form->get("email") => $this->form->get("name")));
  //          $mail->addReplyTo($this->form->get("email"));
  //          $mail->setTo(array("livedev1@yahoo.com" => "Wapo.co"));//creationandthings@gmail.com
  //          $mail->addCc(array("creationandthings@gmail.com" => "Wapo.co"));//
  //          $message = \Blink\render_get($context, ConfigTemplate::Template("frontend/contact_us.twig"));
  //          $mail->setBody($message, "text/html");
  //          $result = \Swift\Api::Send($mail);

//            $mail->setSubject("Subject");
//            $mail->setFrom(array("swanjie3@yahoo.com" => ".."));
//            //$mail->addReplyTo($this->form->get("email"));
//            $mail->setTo(array($recipient->contact => ".."));//creationandthings@gmail.com
//            //$mail->addCc(array("creationandthings@gmail.com" => "Wapo.co"));//
//            $message = "Testing email.";
//            $result = \Swift\Api::Send($mail);
            
            if($delivery_message) {
              $message = $delivery_message . " " . sprintf("%s/%s", $site, $recipient->targeturl->code);
            } else {
              $message = sprintf("Click here '%s/%s' to download your Wapo.", $site, $recipient->targeturl->code);
            }

            $recipient->sent = @mail($recipient->contact, "You have been sent a Wapo.", $message);
            $recipient->save(false);
          }
        } else if($delivery == "aff") {
          $targeturl = \Wapo\WapoTargetUrl::queryset()->get(array("wapo"=>$wapo));
          if ($delivery_message) {
            $message = $delivery_message . sprintf("%s/%s", $site, $targeturl->code);
          } else {
            $message = sprintf("Click here '%s/%s' to download your Wapo.", $site, $targeturl->code);
          }
          $c['message'] = $message;
        } else if($delivery == "fp") {
          $targeturl = \Wapo\WapoTargetUrl::queryset()->get(array("wapo"=>$wapo));
          if ($delivery_message) {
            $message = $delivery_message . sprintf("%s/%s", $site, $targeturl->code);
          } else {
            $message = sprintf("Click here '%s/%s' to download your Wapo.", $site, $targeturl->code);
          }
          $c['message'] = $message;
          $c['facebook_page_id'] = $wapo->external;
        } else if(in_array($delivery, array("stf", "atf"))) {
          $targeturl = \Wapo\WapoTargetUrl::queryset()->get(array("wapo"=>$wapo));
          if ($delivery_message) {
            $message = $delivery_message . sprintf("%s/%s", $site, $targeturl->code);
          } else {
            $message = sprintf("Click here '%s/%s' to download your Wapo.", $site, $targeturl->code);
          }
          
          $connection = new \TwitterOAuth(\Blink\ConfigTwitter::$ConsumerKey, \Blink\ConfigTwitter::$ConsumerSecret, $this->request->session->nmsp("twitter")->get('oauth_token'), $this->request->session->nmsp("twitter")->get('oauth_token_secret'));

          $wapo = Wapo::queryset()->get(array("id"=>$this->request->session->find("wapo_id")), "Wapo not found.");
          if($wapo->delivery_method_abbr == "atf") {
            $info = array(
              "status" => $message
            );
            $tweet = $connection->post('statuses/update', $info);
          } else if($wapo->delivery_method_abbr == "stf") {
            // Get the twitter followers and send to them.
            $recipient_list = WapoRecipient::queryset()->depth(1)->filter(array("wapo"=>$wapo))->fetch();
            foreach($recipient_list as $recipient) {
              $info = array(
                  "status" => sprintf("@%s %s", $recipient->contact, $message)
              );
              $tweet = $connection->post('statuses/update', $info);
              $recipient->sent = 1;
              $recipient->save(false);
            }
          } else {
            throw new \Exception("Invalid send designation.");
          }
        }
      } catch (Exception $ex) {
        $c['error'] = true;
        $c['message'] = $ex->getMessage();
        return $c;
      }
      
      $c['error'] = false;
      $c['delivery'] = $delivery;

      return $c;
    }
  }
  
  
  
  
  
  
  ////////////////////////////////////////////// Twitter classes.
  class SearchTwitterFollowers extends \Blink\TemplateView {
    
  }
  
  class TwitterFollowersView extends \Blink\TemplateView {
    protected function get_content_type() {
      return \Blink\View::CONTENT_JSON;
    }
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      
      $connection = new \TwitterOAuth(\Blink\ConfigTwitter::$ConsumerKey, \Blink\ConfigTwitter::$ConsumerSecret, $this->request->session->nmsp("twitter")->get('oauth_token'), $this->request->session->nmsp("twitter")->get('oauth_token_secret'));
      $followers = $connection->get('followers/list');
      $c['followers'] = $followers;
      
      return $c;
    }
  }
  
  /**
   * - Post to a user's twitter account.
   */
  class TweetView extends \Blink\TemplateView {
    protected function get_content_type() {
      return \Blink\View::CONTENT_JSON;
    }
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      
      try {
        $connection = new \TwitterOAuth(\Blink\ConfigTwitter::$ConsumerKey, \Blink\ConfigTwitter::$ConsumerSecret, $this->request->session->nmsp("twitter")->get('oauth_token'), $this->request->session->nmsp("twitter")->get('oauth_token_secret'));
        
        $wapo = Wapo::queryset()->get(array("id"=>$this->request->session->find("wapo_id")), "Wapo not found.");
        if($wapo->delivery_method_abbr == "atf") {
          $info = array(
            "status" => "Promotion..."
          );
          $tweet = $connection->post('statuses/update', $info);
        } else if($wapo->delivery_method_abbr == "stf") {
          // Get the twitter followers and send to them.
          $recipient_list = WapoRecipient::queryset()->depth(1)->filter(array("wapo"=>$wapo))->fetch();
          foreach($recipient_list as $recipient) {
            $info = array(
                "status" => "Promotion..." . $recipient->contact
            );
            $tweet = $connection->post('statuses/update', $info);
            $recipient->sent = 1;
            $recipient->save(false);
          }
        } else {
          throw new \Exception("Invalid send designation.");
        }
      } catch (\Exception $ex) {
        $c['error'] = true;
        $c['message'] = $ex->getMessage();
        return $c;
      }
      
      $wapo->status = 's';
      $wapo->save(false);
      
      return $c;
    }
  }
  
  /**
   * - Get a user's instagram followers.
   */
  class InstagramFollowersView extends \Blink\TemplateView {
    protected function get_content_type() {
      return \Blink\View::CONTENT_JSON;
    }
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      
      $config = array(
          'apiKey'      => \Blink\ConfigInstagram::$AppKey,
          'apiSecret'   => \Blink\ConfigInstagram::$AppSecret,
          'apiCallback' => sprintf("%s/user/login/instagram/callback/", \Blink\ConfigSite::$Site)
      );
      $instagram = new \Instagram($config);
      $instagram->setAccessToken($this->request->session->nmsp("instagram")->find("access_token"));
      $c['followers'] = $instagram->getUserFollower();
      
      return $c;
    }
  }
  
  /**
   * - Post to a user's instagram account.
   */
  class PostToInstagram extends \Blink\TemplateView {
    protected function get_content_type() {
      return \Blink\View::CONTENT_JSON;
    }
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      
      try {
        $config = array(
            'apiKey' => \Blink\ConfigInstagram::$AppKey,
            'apiSecret' => \Blink\ConfigInstagram::$AppSecret,
            'apiCallback' => sprintf("%s/user/login/instagram/callback/", \Blink\ConfigSite::$Site)
        );
        $instagram = new \Instagram($config);
        $instagram->setAccessToken($this->request->session->nmsp("instagram")->find("access_token"));

        $wapo = Wapo::queryset()->get(array("id"=>$this->request->session->find("wapo_id")), "Wapo not found.");
        if($wapo->delivery_method_abbr == "aif") {
          $info = array(
            "status" => "Promotion..."
          );
          $post = $connection->get('statuses/update', $info);
        } else if($wapo->delivery_method_abbr == "sif") {
          // Get the instagram followers and send to them.
          $recipient_list = WapoRecipient::queryset()->depth(1)->filter(array("wapo"=>$wapo))->fetch();
          foreach($recipient_list as $recipient) {
            $info = array(
                "status" => "Promotion..." . $recipient->contact
            );
            $post = $connection->get('statuses/update', $info);
            $recipient->sent = 1;
            $recipient->save(false);
          }
        } else {
          throw new \Exception("Invalid send designation.");
        }
      } catch (\Exception $ex) {
        $c['error'] = true;
        $c['message'] = $ex->getMessage();
        return $c;
      }
      
      $wapo->status = 's';
      $wapo->save(false);
      
      return $c;
    }
  }
  
  class FakeCheckoutView extends \Blink\RedirectView {
    protected function get_redirect_url() {
      return "/wp/create/?checkoutid=" . rand(234, 2398423);
    }
  }
  
  class FacebookUpdateResourceView extends \Blink\TemplateView {
    protected function get_content_type() {
      return \Blink\View::CONTENT_JSON;
    }
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      
      try {
        $wapo = Wapo::queryset()->get(array("id"=>$this->request->session->find("wapo_id")), "Wapo not found.");
        if($wapo->delivery_method_abbr == "aff") {
          if($this->request->get->is_set("resource")) {
            $wapo->resource = $this->request->get->get("resource");
            $wapo->save(false);
          } else {
            throw new \Exception("No resource set.");
          }
        } else if($wapo->delivery_method_abbr == "fp") {
          if($this->request->get->is_set("resource")) {
            $wapo->resource = $this->request->get->get("resource");
            $wapo->save(false);
          } else {
            throw new \Exception("No resource set.");
          }
        } else {
          throw new \Exception("Invalid delivery method.");
        }
      } catch (\Exception $ex) {
        $c['error'] = true;
        $c['message'] = $ex->getMessage();
        return $c;
      }
      
      $c['error'] = false;
      
      return $c;
    }
  }
  
}
