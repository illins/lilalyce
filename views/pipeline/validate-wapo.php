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
   * 
   * @param mixed $cookies
   * @param \Blink\Request $request
   * @throws \Exception
   */
  function validate_cart($cookies, $request) {
    // Verify that we can send this...
    try {
      // Get the module.
      $module = null;
      $module_id = $this->request->cookie->find("module_id");
      if ($module_id) {
        $module = \Wapo\Module::get_or_null(array("id" => $module_id), "Module not found.");
      } else {
        $module = \Wapo\Module::get_or_null(array("tag" => "gift"), "Module not found.");
      }
      
      // Check if module has been found.
      if(!$module) {
        throw new \Exception("Module not found.");
      }
      
      // If this is an announcement, check it here and exit(don't continue) if everything checks out.
      if($module->tag == "announcement") {
        $at_least_one = false;// If we have one social media platform.
        $twitter_announcement = $request->cookie->find("twitter_announcement", 0);
        if($twitter_announcement) {
          if(!(new \BlinkTwitter\BlinkTwitterAPI($this->request))->isLoggedIn()) {
            throw new \Exception("Please log in to twitter to send a twitter announcement.");
          }
          $at_least_one = true;
        }
        
        if($at_least_one) {
          return array(true, "");
        }
      }
      
      // Check that promotion is set.
      $promotion = Promotion::get_or_null(array("id"=>$request->cookie->find("promotion_id")));
      
      // If promotion is not valid, this is an error.
      if (!$promotion) {
        throw new \Exception("Promotion not selected.");
      }
      
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
        if($sku->unit_price == -1) {
          $amount = $request->cookie->find("amount", 0);
          if($amount < $sku->min_price || $amount > $sku->max_price) {
            throw new \Exception("'Card' amount must be within range.");
          }
        }
      } else if($promotioncategory->name == "I Feel Goods") {
        $sku = \Wapo\IFeelGoodsRewards::get_or_null(array("sku"=>$request->cookie->find("sku")));
        if(!$sku) {
          throw new \Exception("Please select a Reward.");
        }
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
      return array(true, $ex->getMessage());
    }
    
    return array(false, "");
  }
  
}
