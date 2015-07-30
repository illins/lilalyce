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
  
  require_once("apps/blink-twilio/api.php");
  
  require_once("apps/blink-tangocard/tangocard/tangocard.php");
  
  require_once 'apps/blink-bitly/bitly/bitly.php';
  

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
   * Return the list of emails used in the 'e' step.
   * @param type $request
   * @return type
   */
  function get_email_list($request) {
    // Get how many emails can current user send.
    $emails = Config::MAX_EMAIL_DELIVERY_COUNT_GUEST;
    if ($request->user) {
      $emails = Config::MAX_EMAIL_DELIVERY_COUNT_USER;
    }

    // Get the emails filled in.
    $recipient_count = 0;
    $email_list = array();
    for ($i = 1; $i <= $emails; $i++) {
      if ($request->cookie->find("email-$i")) {
        $recipient_count++;
        $email_list[] = $request->cookie->find(("email-$i"));
      }
    }
    
    return $email_list;
  }
  
  /**
   * Validate if the submitted data is enough to create a Wapo.
   * 
   * @param \Blink\Request $request
   * @return array array(boolean, string, data);
   * @throws \Exception
   */
  function validate_wapo($request) {
    try {
      // Determine what module we are on.
      $module = null;
      $module_id = $request->cookie->find("module_id");
      if ($module_id) {
        $module = \Wapo\Module::get_or_null(array("id" => $module_id));
      } else {
        $module = \Wapo\Module::get_or_null(array("tag" => "gift"));
      }
      
      // If no module, then this is an error.
      if(!$module) {
        throw new \Exception("Module error: Module not found.");
      }
      
      // Validate based on module.
      if($module->tag == "gift") {
        return validate_wapo_general($request, $module);
      } else if($module->tag == "announcement") {
        return validate_wapo_announcement($request, $module);
      }
    } catch (\Exception $ex) {
      return array(true, $ex->getMessage(), null);
    }
    
    return array(false, "", null);
  }
 
  /**
   * Validate that the data posted is enough to create a general Wapo.
   * 
   * @param \Blink\Request $request
   * @param \Wapo\Module $module
   * @return array See validate_wapo
   * @throws \Exception
   */
  function validate_wapo_general($request, $module) {
    try {
      // PROMOTION STEP.
      
      $promotion = null;
      $promotioncategory = PromotionCategory::get_or_null(array("id"=>$request->cookie->find("promotioncategory_id", null)));
      if($promotioncategory) {
        if($promotioncategory->name == "I Feel Goods") {
          $promotion = Promotion::get_or_null(array("promotioncategory"=>$promotioncategory));
        } else if($promotioncategory->name == "Tango Card") {
          $promotion = Promotion::get_or_null(array("promotioncategory"=>$promotioncategory));
        }
      } else {
        $promotion = Promotion::get_or_null(array("id"=>$request->cookie->find("promotion_id")));
        $promotioncategory = $promotion->promotioncategory;
      }
      
      // If promotion is not valid, this is an error.
      if (!$promotion) {
        throw new \Exception("Promotion not selected.");
      }
      
      // Check the promotion category.
      $promotioncategory = $promotion->promotioncategory;
      if($promotioncategory->id != $request->cookie->find("promotioncategory_id")) {
        throw new \Exception("Promotion not selected.");
      }
      
      // If this promotioncategory is 'Tango Card', do some checks.
      if($promotioncategory->name == "Tango Card") {
        // Check that the sku exists.
        $sku = \Wapo\TangoCardRewards::get_or_null(array("sku"=>$request->cookie->find("sku")));
        if(!$sku) {
          throw new \Exception("Reward Error: Please select a valid reward.");
        }
        
        // If this unit_price == -1 (i.e. custom), check that the range is within min/max.
//        if($sku->unit_price == -1) {
//          $amount = $request->cookie->find("amount", 0);
//          if($amount < $sku->min_price || $amount > $sku->max_price) {
//            throw new \Exception("'Card' amount must be within range.");
//          }
//        }
      } else if($promotioncategory->name == "I Feel Goods") {
        $sku = \Wapo\IFeelGoodsRewards::get_or_null(array("sku"=>$request->cookie->find("sku")));
        if(!$sku) {
          throw new \Exception("Reward Error: Please select a valid reward.");
        }
      }
      
      // PROFILE STEP.
      $profile = null;
      $profile_name = "";
      $profile_email = "";
      
      // If we have an email set, then this is a new profile.
      if($request->cookie->is_set("email")) {
        $profile_name = $request->cookie->find("name", "");
        $profile_email = $request->cookie->find("email", "");
        
        if($request->user) {
          $profile = \Wapo\Profile::get_or_null(array("name"=>$profile_name));
          
          // Clear the profile name/email if this is filled in.
          if($profile) {
            $profile_name = "";
            $profile_email = "";
          }
        }
      } else {
        $profile = \Wapo\Profile::get_or_null(array("id"=>$request->cookie->find("profile_id"),"wapo_distributor.user"=>$request->user));
      }
      
      // Check that at least one or the other is filled in.
      if(!$profile && !$profile_name && !$profile_email) {
        throw new \Exception("Profile error. Please select a valid profile or create a new profile.");
      }
      
      // DELIVERY METHOD STEP.
      
      // Check that delivery method is valid.
      $delivery_methods = array("ffa", "e", "mailchimp", "el", "text", "aff", "fp", "atf", "stf", "aif", "sif");
      if (!in_array($request->cookie->find('delivery'), $delivery_methods)) {
        throw new \Exception("Delivery method not found.");
      }
      
      // Check that they are logged in.
      $delivery = $request->cookie->find("delivery");
      $twitter_account = "";
      $facebook_account = "";
      if($delivery == "atf" || $delivery == "stf") {
        $twitter_account = (new \BlinkTwitter\BlinkTwitterAPI($request))->getTwitterProfile();
        if(!$twitter_account) {
          throw new \Exception("Please log in to Twitter.");
        }
      } else if($delivery == "aff" || $delivery == "fp") {
        $facebook_account = (new \BlinkFacebook\BlinkFacebookApi($request))->getUserProfile();
        if(!$facebook_account) {
          throw new \Exception("Please log in to Facebook.");
        }
      }
      
      // DELIVERY METHOD DETAILS.
      $delivery_message = $request->cookie->find("delivery_message");

      // Check that we have an expiration date.
      if (!$request->cookie->find('expiring_date')) {
        throw new \Exception("Please set the promotion expiring date.");
      }

      // Check that the date is valid and that it is greater than today.
      $expiring_date = \DateTime::createFromFormat("m/d/Y H:i A", $request->cookie->find('expiring_date'));
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
      $delivery = $request->cookie->find('delivery');
      $quantity = 0;
      $email_list = array();
      $phone_number_list = array();
      $twitter_followers = array();
      $facebook_page_id = '';
      if ($delivery == "ffa") {// FREE FOR ALL SECTION.
        $quantity = $request->cookie->find('quantity', 0);
        if ($quantity < 1) {
          throw new \Exception("Please select a quantity for Free For All Delivery.");
        }
      } else if ($delivery == "e") {// EMAIL SECTION.
        $email_list = get_email_list($request);
        $quantity = count($email_list);
        
        // Check counts and such.
        if (!$quantity) {
          throw new \Exception("Please enter at least one email for 'email' delivery.");
        }
      } else if($delivery == "mailchimp") {// MAILCHIMP SECTION.
        $query = array(
            "id"=>$id = $request->cookie->find("list_id", null)
        );
        
        // If there are emails, get the emails for validation.
        $emails = $request->cookie->find("mailchimps", null);
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
        
        // Fetch the actual emails (before it is just ids).
        $email_list = array();
        foreach($result['data']['data'] as $e) {
          $email_list[] = trim($e['email']);
        }
        
        $quantity = count($email_list);
      } else if ($delivery == "el") { // email list section.
        $email_list = explode(",", $request->cookie->find("emails", ""));
        $quantity = count($email_list);
        
        // Validate that this is an email.
        $invalid_email_list = array();
        foreach($email_list as $email) {
          $email = trim($email);
          
          if(filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $invalid_email_list[] = $email;
          }
        }
        
        // If they have exceeded the maximum emails they can send using this method.
        if($quantity > WpConfig::MAX_EL_EMAIL_COUNT) {
          throw new \Exception(sprintf("You can only send a maximum of '%s' emails using this method", WpConfig::MAX_EL_EMAIL_COUNT));
        }
        
        // If there are invalid emails, set an error.
        if(count($invalid_email_list)) {
          throw new \Exception(sprintf("There is one or more invalid emails in your list (%s).", implode(",", $invalid_email_list)));
        }
      } else if ($delivery == "aff") {
        $quantity = $request->cookie->find('quantity', 0);
        
        // Check that they have a quantity.
        if ($quantity < 1) {
          throw new \Exception("Please select a quantity for Any Facebook Friend Delivery.");
        }
      } else if ($delivery == "fp") {
        $facebook_page_id = $request->cookie->find('facebook_page_id');
        
        if (!$facebook_page_id) {
          throw new \Exception("Please select a Facebook Page.");
        }

        $quantity = $request->cookie->find('quantity', 0);
        
        // Check that they have a quantity.
        if ($quantity < 1) {
          throw new \Exception("Please select a quantity for Facebook Page Delivery.");
        }
        
      } else if ($delivery == "atf") {
        $quantity = $request->cookie->find('quantity', 0);
        if ($quantity < 1) {
          throw new \Exception("Please select a quantity for Any Twitter Follower Delivery.");
        }
      } else if ($delivery == "stf") {
        $twitter_followers = explode(",", $request->cookie->find('twitter_followers', ''));
        $quantity = count($twitter_followers);
        if ($quantity < 1) {
          throw new \Exception("Please select at least one Twitter Follower.");
        }
      } else if ($delivery == "aif") {
        if ($request->cookie->find('quantity', 0) < 1) {
          throw new \Exception("Please select a quantity for Any Instagram Follower Delivery.");
        }
      } else if ($delivery == "sif") {
        if (count(explode(",", $request->cookie->find('instagram_followers', 0))) < 1) {
          throw new \Exception("Please select at least one Instagram Follower.");
        }
      } else if($delivery == "text") {
        $phone_number_list = explode(",", $request->cookie->find("phone_numbers"));
        
        if(!count($phone_number_list)) {
          throw new \Exception("You did not enter phone numbers.");
        }
        
        foreach($phone_number_list as $number) {
          if(!is_int((int) $number)) {
            throw new \Exception("You have some invalid phone numbers.");
          }
        }
        
        $quantity = count($phone_number_list);
      }
    } catch (\Exception $ex) {
      return array(true, $ex->getMessage(), null);
    }
    
    // Calculate total price.
    $total = 0;
    if($sku instanceof \Wapo\TangoCardRewards) {
      $total = $sku->unit_price * $quantity;
    }

    $data = array(
        "module" => $module,
        "promotioncategory" => $promotioncategory,
        "promotion" => $promotion,
        "sku" => $sku,
        "delivery" => $delivery,
        "delivery_message" => $delivery_message,
        "expiring_date" => $request->cookie->get('expiring_date'),
        "quantity" => $quantity,
        "total" => $total,
        "email_list" => $email_list,
        "phone_number_list" => $phone_number_list,
        "profile" => $profile,
        "profile_name" => $profile_name,
        "profile_email" => $profile_email,
        "facebook_account" => $facebook_account,
        "twitter_account" => $twitter_account,
        "twitter_followers" => $twitter_followers,
        "facebook_page_id" => $facebook_page_id
    );
    
    return array(false, "", $data);
  }
 
  /**
   * Specialized function to validate an announcement wapo.
   * Returns data to be used to create the announcement without needing to fetch
   * it again.
   * 
   * @param type $request
   * @param \Wapo\Module $module
   * @return type
   * @throws \Exception
   */
  function validate_wapo_announcement($request, $module) {
    try {
      // PROFILE STEP
      $profile = null;
      $profile_name = "";
      $profile_email = "";
      
      // If we have an email set, then this is a new profile.
      if($request->cookie->is_set("email")) {
        $profile_name = $request->cookie->find("name", "");
        $profile_email = $request->cookie->find("email", "");
        
        if($request->user) {
          $profile = \Wapo\Profile::get_or_null(array("email"=>$profile_email));
          
          // Clear the profile name/email if this is filled in.
          if($profile) {
            $profile_name = "";
            $profile_email = "";
          }
        }
      } else {
        $profile = \Wapo\Profile::get_or_null(array("id"=>$request->cookie->find("profile_id"),"wapo_distributor.user"=>$request->user));
      }
      
      // Check that at least one or the other is filled in.
      if(!$profile && !$profile_name && !$profile_email) {
        throw new \Exception("Profile error. Please select a valid profile or create a new profile.");
      }
      
      // ANNOUNCEMENT STEP
      
      // ANNOUNCEMENT STEP ANNOUNCMENT TEXT.
      
      // Check that we have an announcement. 
      if(!$request->cookie->find("announcement")) {
        throw new \Exception("You have not entered an announcement to send.");
      }
      
      // ANNOUNCEMENT STEP TWITTER.
      $twitter_announcement = $request->cookie->find("twitter_announcement");
      $twitter_account = null;
      if($twitter_announcement) {
        $twitter_account = (new \BlinkTwitter\BlinkTwitterAPI($request))->getTwitterProfile();
        
        if(!$twitter_account) {
          throw new \Exception("You have selected Twitter Announcement but have not logged in to Twitter.");
        }
      }
      
      // ANNOUNCEMENT STEP FACEBOOK.
      $facebook_announcement = $request->cookie->find("facebook_announcement");
      $facebook_account = null;
      if($facebook_announcement) {
        $facebook_account = (new \BlinkFacebook\BlinkFacebookApi($request))->getUserProfile();
        if(!$facebook_account) {
          throw new \Exception("You have selected Facebook Announcement but have not logged in to Facebook.");
        }
      }
      
      // ANNOUNCEMENT STEP FACEBOOK PAGE.
      $facebook_page_announcement = $request->cookie->find("facebook_page_announcement");
      if($facebook_page_announcement) {
        $facebook_account = (new \BlinkFacebook\BlinkFacebookApi($request))->getUserProfile();
        if(!$facebook_account) {
          throw new \Exception("You have selected Facebook Page Announcement but have not logged in to Facebook.");
        }
        
        // Fetch the pages for validation.
        $facebook_page_id_list = explode(",", $facebook_page_announcement);
        $facebook_page_list = (new \BlinkFacebook\BlinkFacebookApi($request))->getFacebookPages();
        $fb_page_id_list = array();
        foreach($facebook_page_list as $page) {
          $fb_page_id_list[] = $page->id;
        }
        
        foreach($facebook_page_id_list as $page_id) {
          if(!in_array($page_id, $fb_page_id_list)) {
            throw new \Exception("You have selected an invalid Facebook Page.");
          }
        }
        
      }
      
      // Check that we have at least one announcemnt.
      if(!$twitter_announcement) {
        throw new \Exception("Please select at least one Announcement.");
      }
    } catch (\Exception $ex) {
      return array(true, $ex->getMessage(), array());
    }
    
    $data = array(
        "error" => false,
        "message" => "",
        "module" => $module,
        "profile" => $profile,
        "profile_name" => $profile_name,
        "profile_email" => $profile_email,
        "twitter_announcement" => $twitter_announcement,
        "twitter_account" => $twitter_account,
        "facebook_announcement" => $facebook_announcement,
        "facebook_account" => $facebook_account,
        "facebook_page_id_list" => $facebook_page_id_list
    );
    
    return array(false, '', $data);
  }
}
