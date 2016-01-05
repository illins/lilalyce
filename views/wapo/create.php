<?php

namespace Wp {
  require_once 'apps/wapo/model.php';
  require_once 'blink/base/validation/validator.php';

  function create_wapo($request, $wpd) {
    try {
      // Create empty wapo shell.
      $wapo = new \Wapo\Wapo();

      // Set the module.
      $wapo->module = $wpd->module->id;

      // Set the profile.
      if ($wpd->profile->profile) {
        $wapo->profile = \Wapo\Profile::get_or_404(array("id"=>$wpd->profile->profile->id), "Profile not found!");
      } else {
        $user_list = \User\User::queryset()->filter(array("email" => $wpd->profile->new->email))->fetch();

        $user = null;
        if (count($user_list)) {
          $user = $user_list[0];
        } else {
          // If not logged in, means they are using email so create an account.
          list($error, $message, $user) = \User\Api::create_user(array("email" => $wpd->profile->new->email), true);
        }

        // Create their distributor and profile using the name.
        $distributor = \Wapo\Distributor::get_or_create_save(array("user" => $user), array(), false);
        $profile = \Wapo\Profile::get_or_create(array("distributor" => $distributor, "name" => $wpd->profile->new->name));

        // Upload the file of the newly created profile to the correct destination.
        if($wpd->profile->new->image) {
          $pathinfo = pathinfo($wpd->profile->new->image);
          $name = $pathinfo['basename'];
          $target = sprintf("media/wapo/profile/%s", $name);
          $profile->image = $name;
          @copy($wpd->profile->new->image, $target);
        }
        
        $profile->save(false);

        $wapo->profile = $profile;
      }

      // Set the sender if this is Facebook or Twitter.
      if ($wpd->delivery == "facebook-page" || $wpd->delivery == "any-facebook-friends") {
        $wapo->sender = $wpd->facebook->profile->id;
        //@todo - set extra for fb page.
      } else if ($wpd->delivery == "select-twitter-followers" || $wpd->delivery == "any-twitter-followers") {
        $wapo->sender = $wpd->twitter->account->id_str;
      }
      
      // If FB page, set the exernal (page id).
      if ($wpd->delivery == "facebook-page") {
        $wapo->external = $wpd->facebook->page;
      }

      // Set marketplace extras.
      if ($wpd->marketplace == "tangocards") {
        $wapo->tangocardrewards = \Wapo\TangoCardRewards::get_or_404(array("sku"=>$wpd->tangocards->sku), "Reward not found!");
        $wapo->sku = $wpd->tangocards->sku;
      } else if ($wpd->marketplace == "promotion") {
        
      }

      $wapo->marketplace = $wpd->marketplace;
      $wapo->payment_method = \Wapo\PaymentMethod::queryset()->get(array("tag" => "wepay"));
      
      if($wpd->payment_method == "wepay") {
        $wapo->checkoutid = $request->session->prefix('wepay')->find("checkout_id");
      }
      
      $wapo->delivery_method = $wpd->delivery;
      $wapo->delivery_message = $wpd->delivery_message;
      $wapo->quantity = $wpd->quantity;
      $wapo->status = "paid";

      $wapo->save(false);
      $wapo->profile->wapo_count += 1;
      $wapo->profile->save(false);

      $order_list = array();
      if ($wpd->marketplace == "tangocards") {
        $order_list = create_tangocard_reward($wpd->tangocards->sku, $wpd->quantity);
      }
      
      if($wpd->delivery == "free-for-all") {
        create_ffa($wapo, $wpd, $order_list);
      } else if($wpd->delivery == "email") {
        create_delivery_email($wapo, $wpd, $order_list);
      } else if($wpd->delivery == "email-list") {
        create_delivery_email_list($wapo, $wpd, $order_list);
      } else if($wpd->delivery == "mailchimp") {
        create_delivery_mailchimp($wapo, $wpd, $order_list);
      } else if($wpd->delivery == "facebook-page") {
        create_delivery_facebook($wapo, $wpd, $order_list);
      } else if($wpd->delivery == "any-facebook-friends") {
        create_delivery_facebook($wapo, $wpd, $order_list);
      } else if($wpd->delivery == "any-twitter-followers") {
        create_twitter($wapo, $wpd, $order_list);
      } else if($wpd->delivery == "select-twitter-followers") {
        create_select_twitter_followers($wapo, $wpd, $order_list);
      } else if($wpd->delivery == "text") {
        create_delivery_text($wapo, $wpd, $order_list);
      } else {
        $message = "Invalid delivery method!";
      }

//      if ($wpd->delivery == "email") {
//        create_delivery_email($wapo, $wpd, $order_list);
//      }

      return array($wapo, null);
    } catch (\Exception $ex) {
      return array(null, $ex->getMessage());
    }
  }
  
  function create_ffa($wapo, $wpd, $order_list) {
    $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "t");

    // Add each of them to the database, sent is false and will be updated once it has been sent.
    for ($x = 0; $x < $wpd->quantity; $x++) {
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

      \Wapo\WapoRecipient::create_save($recipient, false);
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

      \Wapo\WapoRecipient::create_save($recipient, false);
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

      \Wapo\WapoRecipient::create_save($recipient, false);
    }
  }

  function create_delivery_facebook($wapo, $wpd, $order_list) {
    $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "t");

    // Add each of them to the database, sent is false and will be updated once it has been sent.
    for ($x = 0; $x < $wpd->quantity; $x++) {
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

  function create_twitter($wapo, $wpd, $order_list) {
    $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "t");

    // Add each of them to the database, sent is false and will be updated once it has been sent.
    for ($x = 0; $x < $wpd->quantity; $x++) {
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

      \Wapo\WapoRecipient::create_save($recipient, false);
    }
  }

  function create_delivery_text($wapo, $wpd, $order_list) {
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

    if ($error) {
      return "You have some invalid phone numbers!";
    }

    $wapo->text->number_list = $number_list;

    return null;
  }
  
  // Rewards.
  /**
   * Given the reward (card) sku and quantity, request that number and return resources for this.
   * - Check for valid sku and get the price.
   * - Using the price of single item and quantity, calculate the total amount.
   * - Fund the account for this amount.
   * - Purchase each individual reward and store the resource.
   * - Return the resources as a list to the calling function, and how many were not fulfilled.
   */
  function create_tangocard_reward($sku, $quantity) {
    $tc = new \BlinkTangoCard\TangoCardAPI();
    
    // Get the total cost of the product.
    $reward = \Wapo\TangoCardRewards::get_or_404(array("sku"=>$sku), "Reward not found.");
    $total_cost = ($reward->unit_price / 100) * $quantity;

    // Make sure that we are requesting at least $100.
    if($total_cost < 100) {
      $total_cost = 100;
    }
    
    // Create information to fund account.
    $cc_fund = array(
          "customer" => \Blink\TangoCardConfig::CUSTOMER,
          "identifier" => \Blink\TangoCardConfig::IDENTIFIER,
          "amount" => $total_cost,
          "client_ip" => "55.44.33.22",// ????
          "security_code" => \Blink\TangoCardConfig::SECURITY_CODE,
          "cc_token" => \Blink\TangoCardConfig::CC_TOKEN
      );
    
    // Create a request to check for funds.
    $request = "accounts/" . \Blink\TangoCardConfig::CUSTOMER . "/" . \Blink\TangoCardConfig::IDENTIFIER;
    $account = $tc->request($request);
    
    // If funds < 100, add new funds.
    if($account->account->available_balance < 100) {
      $fund = $tc->request("cc_fund", $cc_fund);
      
      // Check success.
      if(!$fund->success) {
        \Blink\raise500("Order could not be completed.");
      }
    }
    
    $orders = array();
    for($i = 1; $i <= $quantity; $i++) {
      $info = array(
          "customer" => \Blink\TangoCardConfig::CUSTOMER,
          "account_identifier" => \Blink\TangoCardConfig::IDENTIFIER,
          "identifier" => \Blink\TangoCardConfig::IDENTIFIER,
          "recipient" => array(
              "name" => "John Doe",
              "email" => \Blink\TangoCardConfig::EMAIL),
          "sku" => 'TNGO-E-V-STD',
          "amount" => $reward->unit_price,
          "reward_message" => "Thank you for participating in the XYZ survey.",
          "reward_subject" => "XYZ Survey, thank you...",
          "reward_from" => "Jon Survey Doe"
      );

      $order = $tc->place_order($info);
      
      // Check success.
      $orders[] = (isset($order->order->order_id)) ? $order->order->order_id : '-';
    }
    
    return $orders;
  }

}