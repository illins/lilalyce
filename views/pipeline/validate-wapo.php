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
  
  require_once 'apps/wp/views/pipeline/definition.php';

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
      
      // Check that promotion is set.
      $promotion = Promotion::get_or_null(array("id"=>$request->cookie->find("promotion_id")));
      
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
          throw new \Exception("Please select a 'Card'.");
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
          throw new \Exception("Please select a Reward.");
        }
      }
      
      // DELIVERY METHOD STEP.
      
      // Check that delivery method is valid.
      $delivery_methods = array("ffa", "e", "el", "aff", "fp", "atf", "stf", "aif", "sif");
      if (!in_array($request->cookie->find('delivery'), $delivery_methods)) {
        throw new \Exception("Delivery method not found.");
      }

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
      if ($delivery == "ffa") {
        if (!$request->cookie->find('quantity') && $request->cookie->find('quantity', 0) < 1) {
          throw new \Exception("Please select a quantity for Free For All Delivery.");
        }
      } else if ($delivery == "e") {
        $emails = Config::$NotLoggedInMaxEmailDeliveryCount;
        if($request->user) {
          $emails = Config::$LoggedInMaxEmailDeliveryCount;
        }
        
        $recipient_count = 0;
        for($i = 1; $i <= $emails; $i++) {
          if($request->cookie->find("email-$i")) {
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
        if (!count(Contact::queryset()->depth(2)->filter(array("id" => $request->cookie->find('contact_id'), "wapo_distributor.user" => $this->request->user, "type" => "e"))->fetch())) {
          throw new \Exception("Please select a valid email list.");
        }
      } else if ($delivery == "aff") {
        if (!$request->cookie->find('facebook_id')) {
          throw new \Exception("Please log in to Facebook to continue.");
        }

        // Check that they have a quantity.
        if ($request->cookie->find('quantity', 0) < 1) {
          throw new \Exception("Please select a quantity for Any Facebook Friend Delivery.");
        }
      } else if ($delivery == "fp") {
        if (!$request->cookie->find('facebook_page_id')) {
          throw new \Exception("Please select Facebook Page.");
        }

        // Check that they have a quantity.
        if ($request->cookie->find('quantity', 0) < 1) {
          throw new \Exception("Please select a quantity for Facebook Page Delivery.");
        }
      } else if ($delivery == "atf") {
        if ($request->cookie->find('quantity', 0) < 1) {
          throw new \Exception("Please select a quantity for Any Twitter Follower Delivery.");
        }
      } else if ($delivery == "stf") {
        if (count(explode(",", $request->cookie->find('twitter_followers', ''))) < 1) {
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
      } else if($delivery == "text") {
        $number_list = explode(",", $request->cookie->find("numbers"));
        
        if(!count($number_list)) {
          throw new \Exception("You did not enter phone numbers. Please enter 10-digit phone numbers");
        }
        
        foreach($number_list as $number) {
          if(strlen($number) != 10 || !is_int((int) $number)) {
            throw new \Exception("You have some invalid phone numbers. Please enter 10-digit phone numbers");
          }
        }
      }
    } catch (\Exception $ex) {
      return array(true, $ex->getMessage(), null);
    }

    $data = array(
        "module" => $module
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
      // Check that the profile is theirs. The announcement feature is only available to people with an account.
      // @todo - add option for checking that they are a paying customer!? later.
      $profile = \Wapo\Profile::get_or_null(array("id"=>$request->cookie->find("profile_id"),"wapo_distributor.user"=>$request->user));
      if(!$profile) {
        throw new \Exception("Profile error. Please select a valid profile.");
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
      
      // Check that we have at least one announcemnt.
      if(!$twitter_announcement) {
        throw new \Exception("Please select at least one Announcement.");
      }
    } catch (\Exception $ex) {
      return array(
          "error" => true,
          "message" => $ex->getMessage(),
          null
      );
    }
    
    $data = array(
        "error" => false,
        "message" => "",
        "module" => $module,
        "profile" => $profile,
        "twitter_announcement" => $twitter_announcement,
        "twitter_account" => $twitter_account
    );
    
    return array(false, '', $data);
  }
}
