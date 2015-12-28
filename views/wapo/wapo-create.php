<?php

namespace Wp {
  require_once 'apps/wapo/model.php';
  require_once 'blink/base/validation/validator.php';
  
  function create_wapo($wpd) {
    
  }
  
  
  function validate_module($wapo, $wpd, $order_list) {
    // Check that the module is set.
    if(!$wapo->module) {
      return "Module not set!";
    }
    
    // Check that the module is valid.
    $module = \Wapo\Module::get_or_null(array("id"=>$wapo->module->id));
    if(!$module) {
      return "Module not found!";
    }
    
    return null;
  }
  
  
  function validate_profile($wapo, $wpd, $order_list) {
    if($wapo->profile->profile) {
      $profile = \Wapo\Profile::get_or_null(array("id"=>$wapo->profile->profile->id,"wapo_distributor.user"=>$wapo->request->user));
      
      if(!$profile) {
        return "Invalid profile selected!";
      }
    } else {
      if(!$wapo->profile->email) {
        return "Please enter profile email!";
      }
      
      if(validate_email($wapo->profile->email)) {
        return "Please enter a valid profile email!";
      }
    }
    
    return null;
  }
  
  function validate_tangocards($wapo, $wpd, $order_list) {
    if(!$wapo->tangocards) {
      return "Please select a reward!";
    }
    
    $tangocards = \Wapo\TangoCardRewards::get_or_null(array("id"=>$wapo->tangocards));
    if(!$tangocards) {
      return "Invalid reward selected!";
    }
    
    return null;
  }
  
  function validate_promotion($wapo, $wpd, $order_list) {
    if(!$wapo->promotion) {
      return "Please select a reward!";
    }
    
    $promotion = \Wapo\Promotion::get_or_null(array("id"=>$wapo->promotion));
    if(!$promotion) {
      return "Invalid reward selected!";
    }
    
    return null;
  }
  
  function create_delivery_email($wapo, $wpd, $order_list) {
    $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, $wpd->delivery);

    // Go through each email and crate a recipient.
    foreach ($wpd->email->email_list as $email) {
      $recipient = array(
          "wapo" => $wapo,
          "targeturl" => $targeturl,
          "contact" => $email
      );

      // Check that we still have orders.
      if (count($order_list)) {
        $recipient['extra'] = array_pop($order_list);
      }

      WapoRecipient::create_save($recipient, false);
    }
  }
  
  function create_delivery_email_list($wapo, $wpd, $order_list) {
    $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, $wpd->delivery);

    // Go through each email and crate a recipient.
    foreach ($wpd->email_list->email_list as $email) {
      $recipient = array(
          "wapo" => $wapo,
          "targeturl" => $targeturl,
          "contact" => $email
      );

      // Check that we still have orders.
      if (count($order_list)) {
        $recipient['extra'] = array_pop($order_list);
      }

      WapoRecipient::create_save($recipient, false);
    }
  }
  
  function create_delivery_mailchimp($wapo, $wpd, $order_list) {
    $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, $wpd->delivery);

    // Go through each email and crate a recipient.
    foreach ($wpd->mailchimp->email_list as $email) {
      $recipient = array(
          "wapo" => $wapo,
          "targeturl" => $targeturl,
          "contact" => $email
      );

      // Check that we still have orders.
      if (count($order_list)) {
        $recipient['extra'] = array_pop($order_list);
      }

      WapoRecipient::create_save($recipient, false);
    }
  }
  
  
  function validate_delivery_facebook_page($wapo, $wpd, $order_list) {
    $facebook_account = (new \BlinkFacebook\BlinkFacebookApi($wapo->request))->getUserProfile();
    if (!$facebook_account) {
      return "You have not logged into your Facebook account!";
    }
    
    if (!count($wapo->facebook->page_list)) {
      return "Please select at least one Facebook page!";
    }
    
    // Validate the Facebook pages.
    $facebook_page_list = (new \BlinkFacebook\BlinkFacebookApi($wapo->request))->getFacebookPages();
    $fb_page_id_list = array();
    foreach ($facebook_page_list as $page) {
      $fb_page_id_list[] = $page->id;
    }

    $error = false;
    foreach ($wapo->facebook->page_list as $page_id) {
      if (!in_array($page_id, $fb_page_id_list)) {
        $error = true;
        break;
      }
    }
    
    if($error) {
      return "You entered some invalid Facebook pages!";
    }

    return null;
  }

  function validate_delivery_facebook($wapo, $wpd, $order_list) {
    $facebook_account = (new \BlinkFacebook\BlinkFacebookApi($wapo->request))->getUserProfile();
    if (!$facebook_account) {
      return "You have not logged into your Facebook account!";
    }
    
    return null;
  }
  
  function create_twitter($wapo, $wpd, $order_list) {
    $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "t");

    // Add each of them to the database, sent is false and will be updated once it has been sent.
    for($x = 0; $x < $wpd->quantity; $x++) {
      $recipient = array(
          "wapo" => $wapo,
          "targeturl" => $targeturl,
          "contact" => "",
      );

      // Check that we still have orders.
      if (count($order_list)) {
        $recipient['extra'] = array_pop($order_list);
      }

      \Wapo\WapoRecipient::create_save($recipient, false);
    }
  }
  
  function create_select_twitter_followers($wapo, $wpd, $order_list) {
    $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "t");

    // Add each of them to the database, sent is false and will be updated once it has been sent.
    foreach ($wpd->twitter->follower_list as $sn) {
      $recipient = array(
          "wapo" => $wapo,
          "targeturl" => $targeturl,
          "contact" => trim($sn),
      );

      // Check that we still have orders.
      if (count($order_list)) {
        $recipient['extra'] = array_pop($order_list);
      }

      WapoRecipient::create_save($recipient, false);
    }
  }
  
  function validate_delivery_text($wapo, $wpd, $order_list) {
    if (!count($wapo->text->number_list)) {
      return "Please enter at least 1 phone number!";
    }

    $error = false;
    $number_list = array();
    foreach ($wapo->text->number_list as $number) {
      $number = str_replace(array("-", " ", "(", ")"), "", $number);
      if (!is_int((int) $number)) {
        $error = true;
        break;
      }
      $number_list[] = $number;
    }
    
    if($error) {
      return "You have some invalid phone numbers!";
    }
    
    $wapo->text->number_list = $number_list;

    return null;
  }

}