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
 
  
  require_once 'apps/wp/views/wp/validate-wapo.php';

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
    $total_cost = $reward->unit_price * $quantity;

    // Make sure that we are requesting at least $100.
    if($total_cost < 100) {
      $total_cost = 100;
    }
    
    // Create information to fund account.
    $cc_fund = array(
          "customer" => \Blink\TangoCardConfig::CUSTOMER,
          "account_identifier" => \Blink\TangoCardConfig::IDENTIFIER,
          "amount" => $total_cost,
          "client_ip" => "55.44.33.22",// ????
          "security_code" => \Blink\TangoCardConfig::SECURITY_CODE,
          "cc_token" => \Blink\TangoCardConfig::CC_TOKEN
      );
    $fund = $tc->request("cc_fund", $cc_fund);

    // Check success.
    if(!$fund->success) {
      \Blink\raise500("Order could not be completed.");
    }
    
    $orders = array();
    for($i = 1; $i <= $quantity; $i++) {
      $info = array(
          "customer" => \Blink\TangoCardConfig::CUSTOMER,
          "account_identifier" => \Blink\TangoCardConfig::IDENTIFIER,
          "recipient" => array(
              "name" => "John Doe",
              "email" => \Blink\TangoCardConfig::EMAIL),
          "sku" => $reward->sku,
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
  
  /**
   * Given the items below, create the rewards and return the relevant information.
   * - Create a default skeleton order.
   * - Check the sku.
   * - For each quantity, create an order and add it to the list.
   */
  function create_ifeelgoods_reward($request, $wapo, $sku, $quantity) {
    $ifg = new \BlinkIfeelGoods\IfeelGoodsAPI(array("request"=>$request));
    
    // Get the default order info for the reward.
    $ifginfo = array(
        "data" => array(
            "order_id" => "",
            "user" => array(
                "email" => \Blink\IfeelGoodsConfig::EMAIL,
                "phone_number" => \Blink\IfeelGoodsConfig::PHONE,
                "first_name" => "Wapo",
                "last_name" => "Wapo"
            )
        )
    );
    
    $reward = \Wapo\IFeelGoodsRewards::get_or_404(array("sku"=>$sku), "Reward not found.");
    
    $orders = array();
    for($i = 1; $i <= $quantity; $i++) {
      $ifginfo['data']['order_id'] = sprintf("%s-order-%s", $wapo->id, $i);
      $redemption = $ifg->redeem(\Blink\IfeelGoodsConfig::PROMOTION_ID, $sku, $ifginfo);

      // Check for success.
      if(isset($redemption->data->order_id)) {
        $orders[] = $redemption->data->order_id;
      } else {
        $orders[] = '-';
      }
    }
    
    return $orders;
  }
  
  /**
   * 
   * @param \Blink\Request $request
   */
  function create_wapo($request) {
    try {
      // Validate Wapo.
      list($error, $message, $data) = validate_wapo($request);
      if($error) {
        throw new \Exception($message);
      }
      
      // Determine which Wapo we are creating.
      if($data['module']->tag == "announcement") {
        return create_wapo_announcement($request, $data);
      } else {
        return create_wapo_general($request, $data);
      }
    } catch (\Exception $ex) {
      return array(true, $ex->getMessage(), null);
    }
  }
  
  /**
   * 
   * @param mixed $data
   * @param \Blink\Request $request
   * @throws \Exception
   */
  function create_wapo_general($request, $data) {
    try {
      $module = $data['module'];
      
      // Check that 'checkout_id' is set.
//      $request->session->set('checkout_id', 1411015);// @todo - remove.
      if(!$request->session->is_set("checkout_id")) {
        throw new \Exception("Checkout error. Payment transaction did not complete.");
      }
      
      $profile = null;

      // If they are logged in, get or create a profile.
      if ($request->user) {
        if ($data['profile']) {
          $profile = Profile::queryset()->get(array("id"=>$data['profile']));
        } else {
          // Create a new profile.
          $distributor = Distributor::get_or_create_save(array("user"=>$request->user), array(), false);
          $profile = Profile::create_save(array("distributor" => $distributor, "name" => $data['profile_name']), false);
        }
      } else {
        // Check if the user exists.
        $user_list = \User\User::queryset()->filter(array("email"=>$data['profile_email']))->fetch();
        $user = null;
        if(count($user_list)) {
          $user = $user_list[0];
        } else {
          // If not logged in, means they are using email so create an account.
          list($error, $message, $user) = \User\Api::create_user(array("email"=>$data["profile_email"]), true);
        }
        
        // Create their distributor and profile using the name.
        $distributor = Distributor::get_or_create_save(array("user"=>$user), array(), false);
        $profile = Profile::get_or_create_save(array("distributor"=>$distributor, "name"=>$data["profile_name"]));
      }
      
      // Capture user's id for the various social media.
      
      // Get the external id.
      $sender = "";// Accound id of the person who sent the item based on service.
      $extra = "";// Extra information about the sent wapo.
      
      if($data['delivery'] == "atf" || $data['delivery'] == "stf") {
        $sender = $data['twitter_account']->id_str;
      } else if($data['delivery'] == "aff") {
        $sender = $data['facebook_account']->id;
      } else if($data['delivery'] == "fp") {
        $sender = $data['facebook_account']->id;
        $extra = $data['facebook_page_id'];
      }
      
//      switch($data['delivery']) {
////        case "aff":
////          $external = $data['facebook_id'];
////          break;
////        case "aff":
////          $external = $data['facebook_id'];
////          break;
//        case "fp":
//          $external = $data['facebook_page_id'];
//          break;
//        case "aif":
//          $external = $data['instagram_id'];
//          break;
//        case "sif":
//          $external = $data['instagram_id'];
//          break;
//      }
      
      $sku = "";
      $marketplace = "";// Marketplace the user chose based on the promotion id. Default is wapo.
      $promotion = $data['promotion'];
      
      if($promotion) {
        // For ifeelgoods promotion, this is the sku.
        if($promotion->promotioncategory->tag == "i-feel-goods") {
          $sku = $data['sku']->sku;
          $marketplace = "ifeelgoods";
        } else if($promotion->promotioncategory->tag == "tango-card") {
          $sku = $data['sku']->sku;
          $marketplace = "tangocard";
        } else if($promotion->promotioncategory->tag == "wapo") {
          $marketplace == "wapo";
        }
      }
      
      // If Twitter, we get the screen_name.
//      if(in_array($data['delivery'], array("atf", "stf"))) {
//        $connection = new \TwitterOAuth(\Blink\ConfigTwitter::$ConsumerKey, \Blink\ConfigTwitter::$ConsumerSecret, $request->session->nmsp("twitter")->get('oauth_token'), $request->session->nmsp("twitter")->get('oauth_token_secret'));
//        $account = $connection->get('account/verify_credentials');
//        $external = $account->screen_name;
//      }
      
      // Create the wapo.
      $create = array(
          "module" => $module,
          "profile" => $profile,
          "promotion" => $promotion,
          "payment_method" => \Wapo\PaymentMethod::queryset()->get(array("tag"=>"wepay")),
          "delivery_method_abbr" => $data['delivery'],
          "delivery_method" => Config::$DeliveryMethod[$data['delivery']],
          "sender" => $sender,
          "delivery_message" => $data["delivery_message"],
//          "expiring_date" => $data["expiring_date"],
          "status" => "p",
          "checkoutid" => $request->session->get_delete("checkout_id"),
          "quantity" => $data['quantity'],
          "downloaded" => 0,
          "extra" => $extra,
          "sku" => $sku,
          "email_confirmation"=> 1// Always to true.
          
      );
      $wapo = Wapo::create_save($create, false);
      $request->session->set("wapo_id", $wapo->id);
      
      // Purchase the rewards.
      $order_list = array();
      
      // If ifeelgoods marketplace.
      if ($marketplace == "ifeelgoods") {
        // Create the number of items needed based on actual emails being sent out.
        $order_list = create_ifeelgoods_reward($request, $wapo, $data['sku']->sku, $wapo->quantity);
      } else if ($marketplace == "tangocard") {
        $order_list = create_tangocard_reward($data['sku']->sku, $wapo->quantity);
      }

      // Create empty shells for delivery method.
      if($data['delivery'] == "ffa") { // FFA - Free For All
        foreach(array("f", "t", "i", "gp", "g", "p") as $p) {
          $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, $p);
        }
        
        $counter = 0;
        for($i = 1; $i <= $wapo->quantity; $i++) {
          $recipient = array(
              "wapo"      => $wapo,
              "contact"   => "",
              "sent" => true
          );
          
          // Check that we still have orders.
          if(count($order_list)) {
            $recipient['extra'] = array_pop($order_list);
          }
          
          WapoRecipient::create_save($recipient, false);
        }
        
      } else if (in_array($data['delivery'], array("e", "el"))) { // E - Email or EL - Email List
        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, $data['delivery']);
        
        // Go through each email and crate a recipient.
        foreach($data['email_list'] as $email) {
          $recipient = array(
              "wapo" => $wapo,
              "targeturl" => $targeturl,
              "contact" => $email
          );
          
          // Check that we still have orders.
          if(count($order_list)) {
            $recipient['extra'] = array_pop($order_list);
          }
          
          WapoRecipient::create_save($recipient, false);
        }
      } else if($data['delivery'] == "mailchimp") { // Mailchimp - Mailchimp
        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "m");
        
        foreach ($data['email_list'] as $email) {
          $recipient = array(
              "wapo" => $wapo,
              "targeturl" => $targeturl,
              "contact" => $email,
          );
          
          // Check that we still have orders.
          if(count($order_list)) {
            $recipient['extra'] = array_pop($order_list);
          }

          WapoRecipient::create_save($recipient, false);
        }
      } else if ($data['delivery'] == "stf") { // STF - Select Twitter Followers.
        $twitter_followers = $data['twitter_followers'];
        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "t");

        // Add each of them to the database, sent is false and will be updated once it has been sent.
        $counter = 0;
        foreach ($twitter_followers as $sn) {
          $recipient = array(
              "wapo"      => $wapo,
              "targeturl" => $targeturl,
              "name"   => trim($sn),
          );
          
          // Check that we still have orders.
          if(count($order_list)) {
            $recipient['extra'] = array_pop($order_list);
          }
          
          WapoRecipient::create_save($recipient, false);
        }
      } else if ($data['delivery'] == "atf") { // ATF - Any Twitter Followers
        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "t");
        
        for($i = 1; $i <= $wapo->quantity; $i++) {
          $recipient = array(
              "wapo"      => $wapo,
              "contact"   => "",
              "sent" => true
          );
          
          // Check that we still have orders.
          if(count($order_list)) {
            $recipient['extra'] = array_pop($order_list);
          }
          
          WapoRecipient::create_save($recipient, false);
        }
      } else if ($data['delivery'] == "sif") { // SIF - Select Instagram Followers
        $instagram_followers = explode(",", $data['instagram_followers']);
        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "t");
        
        // If ifeelgoods marketplace.
        if($marketplace == "ifeelgoods") {
          // Create the number of items needed based on actual emails being sent out.
          $order_list = create_ifeelgoods_reward($request, $wapo, $data['sku'], count($instagram_followers));  
        } else if($marketplace == "tangocard") {
          $order_list = create_tangocard_reward($data['sku'], count($instagram_followers));
        }
        
        // Add each of them to the database, sent is false and will be updated once it has been sent.
        $counter = 0;
        foreach ($instagram_followers as $iid) {
          $recipient = array(
              "targeturl" => $targeturl,
              "wapo"      => $wapo,
              "contact"   => trim($tid),
          );
          
          // If ifeelgoods marketplace, add an order id to this.
          if($marketplace == "ifeelgoods") {
            $recipient['extra'] = array_pop($order_list);
          } else if($marketplace == "tangocard") {
            $recipient['extra'] = array_pop($order_list);
          }
          
          WapoRecipient::create_save($recipient, false);
        }
        $wapo->quantity = count($instagram_followers);
        $wapo->save(false);
      } else if ($data['delivery'] == "aff") { // AFF - Any Facebook Friends
        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "f");
        
        for($i = 1; $i <= $wapo->quantity; $i++) {
          $recipient = array(
              "wapo"      => $wapo,
              "contact"   => "",
              "sent" => true
          );
          
          // Check that we still have orders.
          if(count($order_list)) {
            $recipient['extra'] = array_pop($order_list);
          }
          
          WapoRecipient::create_save($recipient, false);
        }
      } else if ($data['delivery'] == "fp") { // FP - Facebook Page
        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "f");
        
        for($i = 1; $i <= $wapo->quantity; $i++) {
          $recipient = array(
              "wapo"      => $wapo,
              "contact"   => "",
              "sent" => true
          );
          
          // Check that we still have orders.
          if(count($order_list)) {
            $recipient['extra'] = array_pop($order_list);
          }
          
          WapoRecipient::create_save($recipient, false);
        }
      } else if($data['delivery'] == "text") { // Text - Text
        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "t");
        
        foreach ($data['phone_number_list'] as $phone_number) {
          $recipient = array(
              "wapo" => $wapo,
              "targeturl" => $targeturl,
              "contact" => $phone_number,
          );
          
          // Check that we still have orders.
          if(count($order_list)) {
            $recipient['extra'] = array_pop($order_list);
          }

          WapoRecipient::create_save($recipient, false);
        }
      }
    } catch (\Exception $ex) {
      return array(true, $ex->getMessage(), null);
    }
    
    return array(false, "", $wapo);
  }
  
  /**
   * Specialized function to create an announcement wapo.
   * 
   * @param mixed $cookies
   * @param \Blink\Request $request
   * @throws \Exception
   */
  function create_wapo_announcement($request, $data) {
    try {
      $profile = null;

      // If they are logged in, get or create a profile.
      if ($request->user) {
        if ($data['profile']) {
          $profile = Profile::queryset()->get(array("id"=>$data['profile']));
        } else {
          // Create a new profile.
          $distributor = Distributor::get_or_create_save(array("user"=>$request->user), array(), false);
          $profile = Profile::create_save(array("distributor" => $distributor, "name" => $data['name']), false);
        }
      } else {
        // Check if the user exists.
        $user_list = \User\User::queryset()->filter(array("email"=>$data['profile_email']))->fetch();
        $user = null;
        if(count($user_list)) {
          $user = $user_list[0];
        } else {
          // If not logged in, means they are using email so create an account.
          list($error, $message, $user) = \User\Api::create_user(array("email"=>$data["profile_email"]), true);
        }
        
        // Create their distributor and profile using the name.
        $distributor = Distributor::get_or_create_save(array("user"=>$user), array(), false);
        $profile = Profile::get_or_create_save(array("distributor"=>$distributor, "name"=>$data["profile_name"]));
      }
      
      // Create the wapo.
      $create = array(
          "module" => $data['module'],
          "profile" => $profile,
//          "payment_method" => \Wapo\PaymentMethod::queryset()->get(array("tag"=>"wepay")),
          "delivery_message" => $request->cookie->find("announcement"),
          "status" => "p",
      );
      $wapo = Wapo::create_save($create, false);
      $request->session->set("wapo_id", $wapo->id);

      // Make an entry for each announcement service.
      if($data['twitter_announcement']) {
        $recipient = array(
            "wapo" => $wapo,
            "name" => "twitter_announcement",
            "contact" => $data['twitter_account']->id
        );
        WapoRecipient::create_save($recipient, false);
      }
      
      if($data['facebook_announcement']) {
        $recipient = array(
            "wapo" => $wapo,
            "name" => "facebook_announcement",
            "contact" => $data['facebook_account']->id
        );
        WapoRecipient::create_save($recipient, false);
      }
      
      if(count($data['facebook_page_id_list'])) {
        foreach($data['facebook_page_id_list'] as $page_id) {
          $recipient = array(
              "wapo" => $wapo,
              "name" => "facebook_page_announcement",
              "contact" => $page_id
          );
          WapoRecipient::create_save($recipient, false);
        }
      }
    } catch (\Exception $ex) {
      return array(true, $ex->getMessage(), null);
    }
    
    return array(false, "", $wapo);
  }
  
}
