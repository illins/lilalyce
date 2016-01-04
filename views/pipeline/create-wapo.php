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
//  require_once 'apps/wp/views/pipeline/definition.php';
//  
//  require_once 'apps/wp/views/pipeline/validate-wapo.php';
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
//   * Given the reward (card) sku and quantity, request that number and return resources for this.
//   * - Check for valid sku and get the price.
//   * - Using the price of single item and quantity, calculate the total amount.
//   * - Fund the account for this amount.
//   * - Purchase each individual reward and store the resource.
//   * - Return the resources as a list to the calling function, and how many were not fulfilled.
//   */
//  function create_tangocard_reward($sku, $quantity) {
//    $tc = new \BlinkTangoCard\TangoCardAPI();
//    
//    // Get the total cost of the product.
//    $reward = \Wapo\TangoCardRewards::get_or_404(array("sku"=>$sku), "Reward not found.");
//    $total_cost = $reward->unit_price * $quantity;
//    
//    // Create information to fund account.
//    $cc_fund = array(
//          "customer" => \Blink\TangoCardConfig::CUSTOMER,
//          "account_identifier" => \Blink\TangoCardConfig::IDENTIFIER,
//          "amount" => $total_cost,
//          "client_ip" => "55.44.33.22",// ????
//          "security_code" => \Blink\TangoCardConfig::SECURITY_CODE,
//          "cc_token" => \Blink\TangoCardConfig::CC_TOKEN
//      );
//    $fund = $tc->request("cc_fund", $cc_fund);
//    
//    // Check success.
//    if($fund->success) {
//      \Blink\raise500("Order could not be completed.");
//    }
//    
//    $orders = array();
//    for($i = 1; $i <= $quantity; $i++) {
//      $info = array(
//          "customer" => \Blink\TangoCardConfig::CUSTOMER,
//          "account_identifier" => \Blink\TangoCardConfig::IDENTIFIER,
//          "recipient" => array(
//              "name" => "John Doe",
//              "email" => \Blink\TangoCardConfig::EMAIL),
//          "sku" => $reward->sku,
//          "reward_message" => "Thank you for participating in the XYZ survey.",
//          "reward_subject" => "XYZ Survey, thank you...",
//          "reward_from" => "Jon Survey Doe"
//      );
//
//      $order = $tc->place_order($info);
//      
//      // Check success.
//      
//      
//      $orders[] = (isset($order->order->order_id)) ? $order->order->order_id : null;
//    }
//    
//    return $orders;
//  }
//  
//  /**
//   * Given the items below, create the rewards and return the relevant information.
//   * - Create a default skeleton order.
//   * - Check the sku.
//   * - For each quantity, create an order and add it to the list.
//   */
//  function create_ifeelgoods_reward($request, $wapo, $sku, $quantity) {
//    $ifg = new \BlinkIfeelGoods\IfeelGoodsAPI(array("request"=>$request));
//    
//    // Get the default order info for the reward.
//    $ifginfo = array(
//        "data" => array(
//            "order_id" => "",
//            "user" => array(
//                "email" => \Blink\IfeelGoodsConfig::EMAIL,
//                "phone_number" => "",
//                "first_name" => "Wapo",
//                "last_name" => "Wapo"
//            )
//        )
//    );
//    
//    $reward = \Wapo\IFeelGoodsRewards::get_or_404(array("sku"=>$sku), "Reward not found.");
//    
//    $orders = array();
//    for($i = 1; $i <= $quantity; $i++) {
//      $ifginfo['data']['order_id'] = sprintf("%s-order-%s", $wapo->id, $i);
//      $redemption = $ifg->redeem(\Blink\IfeelGoodsConfig::PROMOTION_ID, $sku, $ifginfo);
//      
//      // Check for success.
//      $order = $redemption->data->order_id;
//      
//      $orders[] = $order;
//    }
//    
//    return $orders;
//  }
//  
//  /**
//   * 
//   * @param \Blink\Request $request
//   */
//  function create_wapo($request) {
//    try {
//      // Validate Wapo.
//      list($error, $message, $data) = validate_wapo($request);
//      if($error) {
//        throw new \Exception($message);
//      }
//      
//      // Determine which Wapo we are creating.
//      if($data['module']->tag == "announcement") {
//        return create_wapo_announcement($request, $data);
//      } else {
//        return create_wapo_general($request, $data);
//      }
//    } catch (\Exception $ex) {
//      return array(true, $ex->getMessage(), null);
//    }
//  }
//  
//  /**
//   * 
//   * @param mixed $cookies
//   * @param \Blink\Request $request
//   * @throws \Exception
//   */
//  function create_wapo_general($request, $data) {
//    try {
//      $module = $data['module'];
//      
//      $request->session->set('checkoutid', 1411015);
//      
//      // Check that checkoutid is set.
//      if(!$request->session->is_set("checkoutid")) {
//        throw new \Exception("Checkout error. Payment transaction did not complete.");
//      }
//      
//      $profile = null;
//
//      // If they are logged in, get or create a profile.
//      if ($request->user) {
//        if (isset($cookies['profile_id'])) {
//          $profile = Profile::queryset()->get(array("id"=>$cookies['profile_id']));
//        } else {
//          // Create a new profile.
//          $distributor = Distributor::get_or_create_save(array("user"=>$request->user), array(), false);
//          $profile = Profile::create_save(array("distributor" => $distributor, "name" => $cookies['name']), false);
//        }
//      } else {
//        // Check if the user exists.
//        $user_list = \User\User::queryset()->filter(array("email"=>$cookies['email']))->fetch();
//        $user = null;
//        if(count($user_list)) {
//          $user = $user_list[0];
//        } else {
//          // If not logged in, means they are using email so create an account.
//          list($error, $message, $user) = \User\Api::create_user(array("email"=>$cookies["email"]), true);
//        }
//        
//        // Create their distributor and profile.
//        $distributor = Distributor::get_or_create_save(array("user"=>$user), array(), false);
//        $profile = Profile::get_or_create_save(array("distributor"=>$distributor, "name"=>$cookies["name"]));
//      }
//      
//      
//      // Get the external id.
//      $external = "";
//      switch($cookies['delivery']) {
//        case "aff":
//          $external = $cookies['facebook_id'];
//          break;
//        case "fp":
//          $external = $cookies['facebook_page_id'];
//          break;
//        case "aif":
//          $external = $cookies['instagram_id'];
//          break;
//        case "sif":
//          $external = $cookies['instagram_id'];
//          break;
//      }
//      
//      $extra = "";// Extra information about the marketplace.
//      $marketplace = "";// Marketplace the user chose based on the promotion id. Default is wapo.
//      $promotion = Promotion::get_or_null(array("id"=>$cookies['promotion_id']));
//      if($promotion) {
//        // For ifeelgoods promotion, this is the sku.
//        if($promotion->promotioncategory->name == "I Feel Goods") {
//          $extra = $cookies['sku'];
//          $marketplace = "ifeelgoods";
//        } else if($promotion->promotioncategory->name == "Tango Card") {
//          $extra = $cookies['sku'];
//          $marketplace = "tangocard";
//        }
//      }
//      
//      // If Twitter, we get the screen_name.
//      if(in_array($cookies['delivery'], array("atf", "stf"))) {
//        $connection = new \TwitterOAuth(\Blink\ConfigTwitter::$ConsumerKey, \Blink\ConfigTwitter::$ConsumerSecret, $request->session->prefix("twitter-")->get('oauth_token'), $request->session->prefix("twitter-")->get('oauth_token_secret'));
//        $account = $connection->get('account/verify_credentials');
//        $external = $account->screen_name;
//      }
//
//      // Create the wapo.
//      $create = array(
//          "module" => $module,
//          "profile" => $profile,
//          "promotion" => $cookies["promotion_id"],
//          "payment_method" => \Wapo\PaymentMethod::queryset()->get(array("tag"=>"wepay")),
//          "delivery_method_abbr" => $cookies['delivery'],
//          "delivery_method" => Config::$DeliveryMethod[$cookies['delivery']],
//          "external" => $external,
//          "delivery_message" => $cookies["delivery_message"],
//          "expiring_date" => $cookies["expiring_date"],
//          "status" => "p",
//          "checkoutid" => $request->session->get_delete("checkoutid"),
//          "quantity" => $request->cookie->find("quantity", 0),
//          "downloaded" => 0,
//          "extra" => $extra,
//          "email_confirmation"=> 1// Always to true.
//          
//      );
//      $wapo = Wapo::create_save($create, false);
//      $request->session->set("wapo_id", $wapo->id);
//
//      // Create empty shells for delivery method.
//      if($cookies['delivery'] == "ffa") { // FFA - Free For All
//        foreach(array("f", "t", "i", "gp", "g", "p") as $p) {
//          $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, $p);
//        }
//        
//        // If ifeelgoods marketplace.
//        if($marketplace == "ifeelgoods") {
//          // Create the number of items needed based on actual emails being sent out.
//          $order_list = create_ifeelgoods_reward($request, $wapo, $cookies['sku'], $wapo->quantity);  
//        } else if($marketplace == "tangocard") {
//          $order_list = create_tangocard_reward($cookies['sku'], $wapo->quantity);
//        }
//        
//        $counter = 0;
//        for($i = 1; $i <= $wapo->quantity; $i++) {
//          // If ifeelgoods, then make an api request for it.
//          $recipient = array(
//              "wapo"      => $wapo,
//              "contact"   => "",
//          );
//          
//          // If ifeelgoods marketplace, add an order id to this.
//          if($marketplace == "ifeelgoods") {
//            $recipient['extra'] = array_pop($order_list);
//          } else if($marketplace == "tangocard") {
//            $recipient['extra'] = array_pop($order_list);
//          }
//          
//          WapoRecipient::create_save($recipient, false);
//        }
//        
//      } else if ($cookies['delivery'] == "stf") { // STF - Select Twitter Followers.
//        $twitter_followers = explode(",", $cookies['twitter_followers']);
//        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "t");
//
//        // If ifeelgoods marketplace.
//        if($marketplace == "ifeelgoods") {
//          // Create the number of items needed based on actual emails being sent out.
//          $order_list = create_ifeelgoods_reward($request, $wapo, $cookies['sku'], count($twitter_followers));  
//        } else if ($marketplace == "tangocard") {
//          $order_list = create_tangocard_reward($cookies['sku'], $wapo->quantity);
//        }
//
//        // Add each of them to the database, sent is false and will be updated once it has been sent.
//        $counter = 0;
//        foreach ($twitter_followers as $tid) {
//          $recipient = array(
//              "targeturl" => $targeturl,
//              "wapo"      => $wapo,
//              "contact"   => trim($tid),
//          );
//          
//          // If ifeelgoods marketplace, add an order id to this.
//          if($marketplace == "ifeelgoods") {
//            $recipient['extra'] = array_pop($order_list);
//          } else if($marketplace == "tangocard") {
//            $recipient['extra'] = array_pop($order_list);
//          }
//          
//          WapoRecipient::create_save($recipient, false);
//        }
//        
//        $wapo->quantity = count($twitter_followers);
//        $wapo->save(false);
//      } else if ($cookies['delivery'] == "atf") { // ATF - Any Twitter Followers
//        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "t");
//        
//        // If ifeelgoods marketplace.
//        if($marketplace == "ifeelgoods") {
//          // Create the number of items needed based on actual emails being sent out.
//          $order_list = create_ifeelgoods_reward($request, $wapo, $cookies['sku'], $wapo->quantity);  
//        } else if($marketplace == "tangocard") {
//          $order_list = create_tangocard_reward($cookies['sku'], $wapo->quantity);
//        }
//        
//        $counter = 0;
//        for($i = 1; $i <= $wapo->quantity; $i++) {
//          // If ifeelgoods, then make an api request for it.
//          $recipient = array(
//              "wapo"      => $wapo,
//              "contact"   => "",
//          );
//          
//          // If ifeelgoods marketplace, add an order id to this.
//          if($marketplace == "ifeelgoods") {
//            $recipient['extra'] = array_pop($order_list);
//          } else if($marketplace == "ifeelgoods") {
//            $recipient['extra'] = array_pop($order_list);
//          }
//          
//          WapoRecipient::create_save($recipient, false);
//        }
//      } else if ($cookies['delivery'] == "sif") { // SIF - Select Instagram Followers
//        $instagram_followers = explode(",", $cookies['instagram_followers']);
//        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "t");
//        
//        // If ifeelgoods marketplace.
//        if($marketplace == "ifeelgoods") {
//          // Create the number of items needed based on actual emails being sent out.
//          $order_list = create_ifeelgoods_reward($request, $wapo, $cookies['sku'], count($instagram_followers));  
//        } else if($marketplace == "tangocard") {
//          $order_list = create_tangocard_reward($cookies['sku'], count($instagram_followers));
//        }
//        
//        // Add each of them to the database, sent is false and will be updated once it has been sent.
//        $counter = 0;
//        foreach ($instagram_followers as $iid) {
//          $recipient = array(
//              "targeturl" => $targeturl,
//              "wapo"      => $wapo,
//              "contact"   => trim($tid),
//          );
//          
//          // If ifeelgoods marketplace, add an order id to this.
//          if($marketplace == "ifeelgoods") {
//            $recipient['extra'] = array_pop($order_list);
//          } else if($marketplace == "tangocard") {
//            $recipient['extra'] = array_pop($order_list);
//          }
//          
//          WapoRecipient::create_save($recipient, false);
//        }
//        $wapo->quantity = count($instagram_followers);
//        $wapo->save(false);
//      } else if ($cookies['delivery'] == "e") { // E - Email
//        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "e");
//        $email_count = ($request->user) ? Config::$LoggedInMaxEmailDeliveryCount : Config::$NotLoggedInMaxEmailDeliveryCount;
//        
//        // Count how many emails we actually have (i.e. filled in boxes).
//        $actual_email_count = 0;
//        for($i = 1; $i <= $email_count; $i++) {
//          if(isset($cookies["email-$i"]) && $cookies["email-$i"]) {
//            $actual_email_count++;
//          }
//        }
//        
//        // I feel goods marketplace.
//        $order_list = array();
//        if($marketplace == "ifeelgoods") {
//          // Create the number of items needed based on actual emails being sent out.
//          $order_list = create_ifeelgoods_reward($request, $wapo, $cookies['sku'], $actual_email_count);  
//        } else if($marketplace == "tangocard") {
//          $order_list = create_tangocard_reward($cookies['sku'], $actual_email_count);
//        }
//        
//        for ($i = 1; $i <= $email_count; $i++) {
//          if (isset($cookies["email-$i"])) {
//            $recipient = array(
//                "wapo" => $wapo,
//                "targeturl" => $targeturl,
//                "contact" => trim($cookies[sprintf("email-%s", $i)]),
//            );
//
//            // If ifeelgoods marketplace, add an order id to this.
//            if($marketplace == "ifeelgoods") {
//              $recipient['extra'] = array_pop($order_list);
//            } else if($marketplace == "tangocard") {
//              $recipient['extra'] = array_pop($order_list);
//            }
//
//            WapoRecipient::create_save($recipient, false);
//          }
//        }
//
//        $wapo->quantity = $actual_email_count;
//        $wapo->save(false);
//      } else if ($cookies['delivery'] == "el") { // EL - Email List *** REMOVE
//        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "el");
//        $email_list = \Wapo\ContactItem::queryset()->select(array("item"))->filter(array("contact"=>$cookies['contact_id']));
//        
//        $counter = 0;
//        foreach($email_list as $email) {
//          $recipient = array(
//              "wapo"      => $wapo,
//              "targeturl" => $targeturl,
//              "contact"   => trim($email->email),
//          );
//          
//          // If ifeelgoods marketplace, add an order id to this.
//          if($marketplace == "ifeelgoods") {
//            $recipient['extra'] = array_pop($order_list);
//          } else if($marketplace == "ifeelgoods") {
//            $recipient['extra'] = array_pop($order_list);
//          }
//          
//          WapoRecipient::create_save($recipient, false);
//        }
//        
//        $wapo->quantity = count($email_list);
//        $wapo->save(false);
//      } else if ($cookies['delivery'] == "aff") { // AFF - Any Facebook Friends
//        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "f");
//        
//        // If ifeelgoods marketplace.
//        if($marketplace == "ifeelgoods") {
//          // Create the number of items needed based on actual emails being sent out.
//          $order_list = create_ifeelgoods_reward($request, $wapo, $cookies['sku'], $wapo->quantity);  
//        } else if($marketplace == "tangocard") {
//          $order_list = create_tangocard_reward($cookies['sku'], $wapo->quantity);
//        }
//        
//        $counter = 0;
//        for($i = 1; $i <= $wapo->quantity; $i++) {
//          // If ifeelgoods, then make an api request for it.
//          $recipient = array(
//              "wapo"      => $wapo,
//              "contact"   => "",
//          );
//          
//          // If ifeelgoods marketplace, add an order id to this.
//          if($marketplace == "ifeelgoods") {
//            $recipient['extra'] = array_pop($order_list);
//          }
//          
//          WapoRecipient::create_save($recipient, false);
//        }
//      } else if ($cookies['delivery'] == "fp") { // FP - Facebook Page
//        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "f");
//        
//        // If ifeelgoods marketplace.
//        if($marketplace == "ifeelgoods") {
//          // Create the number of items needed based on actual emails being sent out.
//          $order_list = create_ifeelgoods_reward($request, $wapo, $cookies['sku'], $wapo->quantity);  
//        } else if($marketplace == "tangocard") {
//          $order_list = create_tangocard_reward($cookies['sku'], $wapo->quantity);
//        }
//        
//        $counter = 0;
//        for($i = 1; $i <= $wapo->quantity; $i++) {
//          // If ifeelgoods, then make an api request for it.
//          $recipient = array(
//              "wapo"      => $wapo,
//              "contact"   => "",
//          );
//          
//          // If ifeelgoods marketplace, add an order id to this.
//          if($marketplace == "ifeelgoods") {
//            $recipient['extra'] = array_pop($order_list);
//          } else if($marketplace == "ifeelgoods") {
//            $recipient['extra'] = array_pop($order_list);
//          }
//          
//          WapoRecipient::create_save($recipient, false);
//        }
//      } else if($cookies['delivery'] == "mailchimp") { // Mailchimp - Mailchimp
//        $query = array(
//            "id"=>$id = $request->cookie->find("list_id", null)
//        );
//        
//        // If there are emails, get the emails for validation.
//        $emails = $request->cookie->find("emails", null);
//        if(trim($emails)) {
//          $query['emails'] = array();
//          foreach(explode(",", $emails) as $e) {
//            $query['emails'][] = array("euid"=>$e);
//          }
//        }
//        
//        // Check that id is user's.
//        $result = \BlinkMailChimp\Api::endpoint("lists/member-info", $query);
//        
//        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "m");
//        $sent_emails = 0;
//        
//        if(!$result['error'] && !isset($result['data']['error'])) {
//          // If ifeelgoods marketplace.
//          if($marketplace == "ifeelgoods") {
//            // Create the number of items needed based on actual emails being sent out.
//            $order_list = create_ifeelgoods_reward($request, $wapo, $cookies['sku'], $result['data']['success_count']);  
//          } else if($marketplace == "tangocard") {
//            $order_list = create_tangocard_reward($cookies['sku'], $result['data']['success_count']);
//          }
//        
//          $counter = 0;
//          foreach($result['data']['data'] as $e) {
//            $recipient = array(
//                "wapo" => $wapo,
//                "targeturl" => $targeturl,
//                "name" => trim(sprintf("%s %s", $e['merges']['FNAME'], $e['merges']['LNAME'])),
//                "contact" => trim($e['email']),
//            );
//            
//            // If ifeelgoods marketplace, add an order id to this.
//            if($marketplace == "ifeelgoods") {
//              $recipient['extra'] = array_pop($order_list);
//            } else if($marketplace == "ifeelgoods") {
//              $recipient['extra'] = array_pop($order_list);
//            }
//            
//            WapoRecipient::create_save($recipient, false);
//            $sent_emails++;
//          }
//        }
//        
//        $wapo->quantity = $sent_emails;
//        $wapo->save(false);
//        
//      } else if($cookies['delivery'] == "text") { // Text - Text
//        $number_list = explode(",", $request->cookie->find("numbers"));
//        
//        $targeturl = \Wapo\WapoTargetUrl::new_code($wapo, "t");
//        
//        // If ifeelgoods marketplace.
//        if($marketplace == "ifeelgoods") {
//          // Create the number of items needed based on actual emails being sent out.
//          $order_list = create_ifeelgoods_reward($request, $wapo, $cookies['sku'], count($number_list));  
//        } else if($marketplace == "tangocard") {
//          $order_list = create_tangocard_reward($cookies['sku'], count($number_list));
//        }
//        
//        $counter = 0;
//        foreach($number_list as $number) {
//          $recipient = array(
//              "wapo"      => $wapo,
//              "targeturl" => $targeturl,
//              "contact"   => $number,
//          );
//          
//          // If ifeelgoods marketplace, add an order id to this.
//          if($marketplace == "ifeelgoods") {
//            $recipient['extra'] = array_pop($order_list);
//          } else if($marketplace == "tangocard") {
//            $recipient['extra'] = array_pop($order_list);
//          }
//          
//          WapoRecipient::create_save($recipient, false);
//        }
//        
//        $wapo->quantity = count($number_list);
//        $wapo->save(false);
//      }
//    } catch (\Exception $ex) {
//      return array(true, $ex->getMessage(), null);
//    }
//    
//    return array(false, "", $wapo);
//  }
//  
//  /**
//   * Specialized function to create an announcement wapo.
//   * 
//   * @param mixed $cookies
//   * @param \Blink\Request $request
//   * @throws \Exception
//   */
//  function create_wapo_announcement($request, $data) {
//    try {
//      // Create the wapo.
//      $create = array(
//          "module" => $data['module'],
//          "profile" => $data['profile'],
////          "payment_method" => \Wapo\PaymentMethod::queryset()->get(array("tag"=>"wepay")),
//          "delivery_message" => $request->cookie->find("announcement"),
//          "status" => "p",
//      );
//      $wapo = Wapo::create_save($create, false);
//      $request->session->set("wapo_id", $wapo->id);
//
//      // Make an entry for each announcement service.
//      if($data['twitter_announcement']) {
//        $recipient = array(
//            "wapo" => $wapo,
//            "name" => "twitter_announcement",
//            "contact" => $data['twitter_account']->id
//        );
//        WapoRecipient::create_save($recipient, false);
//      }
//    } catch (\Exception $ex) {
//      return array(true, $ex->getMessage(), null);
//    }
//    
//    return array(false, "", $wapo);
//  }
//  
//}
