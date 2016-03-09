<?php

namespace Wp {
  require_once 'apps/wapo/model.php';
  require_once 'blink/base/validation/validator.php';

  /**
   * Validate a single email.
   * @param type $email
   * @return type
   */
  function validate_email($email) {
    $validator = \Blink\Validator::ValidateEmail();
    $field = array(
        "blank" => false,
        "null" => false,
        "verbose_name" => "email",
        "value" => $email
    );

    return $validator($field, $email, false);
  }

  /**
   * Validate that the module is set.
   * @param type $wapo
   * @return string
   */
  function validate_module($request, & $wapo) {
    // Check that the module is set.
    if (!$wapo->module) {
      return "Module not set!";
    }

    // Check that the module is valid.
    $module = \Wapo\Module::get_or_null(array("id" => $wapo->module->id));
    if (!$module) {
      return "Module not found!";
    }

    return null;
  }

  /**
   * Validate the profile data.
   * @param type $wapo
   * @return string
   */
  function validate_profile($request, & $wapo) {
    if ($wapo->profile->profile) {
      $profile = \Wapo\Profile::get_or_null(array("id" => $wapo->profile->profile->id, "distributor" => $wapo->profile->profile->distributor));

      if (!$profile) {
        return "Invalid profile selected!";
      }
    } else {
      if (!trim($wapo->profile->new->name)) {
        return "Please enter a profile name!";
      }

      if (!$wapo->profile->new->email) {
        return "Please enter profile email!";
      }

      if (validate_email($wapo->profile->new->email)) {
        return "Please enter a valid profile email!";
      }
    }

    return null;
  }

  /**
   * Validate that tangocards is set.
   * @param type $wapo
   * @return string
   */
  function validate_tangocards($request, & $wapo) {
    if (!$wapo->tangocards) {
      return "Please select a reward!";
    }

    $tangocards = \Wapo\TangoCardRewards::get_or_null(array("id" => $wapo->tangocards->id));
    if (!$tangocards) {
      return "Invalid reward selected!";
    }

    return null;
  }

  /**
   * Validate that promotion is set.
   * @param type $wapo
   * @return string
   */
  function validate_promotion($request, & $wapo) {
    if (!$wapo->promotion) {
      return "Please select a reward!";
    }

    $promotion = \Wapo\Promotion::get_or_null(array("id" => $wapo->promotion->id));
    if (!$promotion) {
      return "Invalid reward selected!";
    }

    return null;
  }
  
  function validate_delivery_ffa($request, & $wapo) {
    if($wapo->quantity < 1) {
      return "Please select a quantity of at least 1!";
    }

    return null;
  }

  /**
   * Validate email delivery method.
   * @param type $wapo
   * @return string
   */
  function validate_delivery_email($request, & $wapo) {
    // Check that the email is set.
    if (!is_array($wapo->email->email_list)) {
      return "You have not entered emails!";
    }

    // Check that we have at least one.
    if (count($wapo->email->email_list) == 0) {
      return "Please enter at least one email!";
    }

    // Check that we are within the max.
    if (count($wapo->email->email_list) > $wapo->email->max) {
      return sprintf("You can only enter a maximum of '%s' emails!", $wapo->email->max);
    }

    // Validate the emails.
    $error = false;
    foreach ($wapo->email->email_list as $email) {
      if (validate_email($email)) {
        $error = true;
      }
    }

    if ($error) {
      return "Some emails you entered did not validate!";
    }

    return null;
  }

  /**
   * Validate email list delivery method.
   * @param type $wapo
   * @return string
   */
  function validate_delivery_email_list($request, & $wapo) {
    // Check that the email is set.
    if (!is_array($wapo->email_list->email_list)) {
      return "You have not entered emails!";
    }

    // Check that we have at least one.
    if (count($wapo->email_list->email_list) == 0) {
      return "Please enter at least one email!";
    }

    // Check that we are within the max.
    if (count($wapo->email_list->email_list) > $wapo->email_list->max) {
      return sprintf("You can only enter a maximum of '%s' emails!", $wapo->email_list->max);
    }

    // Validate the emails.
    $error = false;
    foreach ($wapo->email_list->email_list as $email) {
      if (validate_email($email)) {
        $error = true;
      }
    }

    if ($error) {
      return "Some emails you entered did not validate!";
    }

    return null;
  }

  /**
   * Validate mailchimp delivery method.
   * @param type $wapo
   * @return string
   */
  function validate_delivery_mailchimp($request, & $wapo) {
    // Check that user has selected a subscription list.
    if (!$wapo->mailchimp->subscription) {
      return "You have not picked a subscription!";
    }

    // Check that we have at least one.
    if (count($wapo->mailchimp->email_list) == 0) {
      return "Please select at least one email!";
    }

    // Check that we are within the max.
    if (count($wapo->mailchimp->email_list) > $wapo->mailchimp->max) {
      return sprintf("You can only select a maximum of '%s' emails!", $wapo->mailchimp->max);
    }


    $query = array(
        "id" => $wapo->mailchimp->subscription
    );

//    // If there are emails, get the emails for validation.
//    if (trim($emails)) {
//      $query['emails'] = array();
//      foreach (explode(",", $emails) as $e) {
//        $query['emails'][] = array("euid" => $e);
//      }
//    }

    $query['emails'] = array();
    foreach($wapo->mailchimp->email_list as $email) {
      $query['emails'][] = array("email"=>$email);
    }

    // Check that id is user's.
    $result = \BlinkMailChimp\Api::endpoint("lists/member-info", $query);

    // Check data error.
    if ($result['data']['error_count']) {
      return "Emails did not validate!";
    }
    
//    // Fetch the actual emails (before it is just ids).
//    $email_list = array();
//    foreach ($result['data']['data'] as $e) {
//      $email_list[] = trim($e['email']);
//    }
//    $wapo->mailchimp->email_list = $email_list;

    return null;
  }

  /**
   * Validate facebook page method.
   * @param type $wapo
   * @return string
   */
  function validate_delivery_facebook_page($request, & $wapo) {
    $facebook_account = (new \BlinkFacebook\BlinkFacebookApi($request))->getUserProfile();
    if (!$facebook_account) {
      return "You have not logged into your Facebook account!";
    }

    if (!count($wapo->facebook->page_list)) {
      return "Please select at least one Facebook page!";
    }

    // Validate the Facebook pages.
    $facebook_page_list = (new \BlinkFacebook\BlinkFacebookApi($request))->getFacebookPages();
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

    if ($error) {
      return "You entered some invalid Facebook pages!";
    }

    return null;
  }

  function validate_delivery_facebook($request, & $wapo) {
    $facebook_account = (new \BlinkFacebook\BlinkFacebookApi($request))->getUserProfile();
    if (!$facebook_account) {
      return "You have not logged into your Facebook account!";
    }

    return null;
  }

  function validate_delivery_twitter($request, & $wapo) {
    $twitter_account = (new \BlinkTwitter\BlinkTwitterAPI($request))->getTwitterProfile();

    if (!$twitter_account) {
      return "You have not logged into your Twitter account!";
    }

    return null;
  }

//  function validate_delivery_select_twitter_followers($request, & $wapo) {
//    $twitter_account = (new \BlinkTwitter\BlinkTwitterAPI($wapo->request))->getTwitterProfile();
//
//    if (!$twitter_account) {
//      return "You have not logged into your Twitter account!";
//    }
//
//    if (!count($wapo->twitter->follower_list)) {
//      return "Please select at least one Twitter Follower!";
//    }
//
//    return null;
//  }

  function validate_delivery_text($request, & $wapo) {
    if (!count($wapo->text->number_list)) {
      return "Please enter at least 1 phone number!";
    }

    $error = false;
    $number_list = array();
    foreach ($wapo->text->number_list as $number) {
      $cleaned = trim(str_replace(array("-", " ", "(", ")"), "", $number));
      if (!is_int((int) $cleaned) || strlen($cleaned) != 10) {
        $error = true;
        break;
      }
      $number_list[] = $number;
    }

    if ($error) {
      return "You have some invalid phone numbers!";
    }

    $wapo->text->number_list = $number_list;
    
    return null;
  }

}