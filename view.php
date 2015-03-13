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
  
  class TangoJSONView extends \Blink\JSONView {
    protected function get_context_data() {
      $c = parent::get_context_data();
      
      return $c;
      
      $bitly = new \BlinkBitly\BlinkBitlyAPI();
      $c['shortened'] = $bitly->shorten("http://engage.minilabs.co");
      
      return $c;
//      return $c;//
//      
//      $ifg = new \BlinkIfeelGoods\IfeelGoodsAPI(array("request"=>$this->request));
//      
//      $redemption = array(
//          "data" => array(
//              "order_id" => "test-1",
//              "user" => array(
//                  "email" => "livedev1@yahoo.com",
//                  "phone_number" => "",
//                  "first_name" => "Live", 
//                  "last_name" => "Dev"
//              )
//          )
//      );
//      
////      $c['me'] = $ifg->me();
////      $c['redemption'] = $ifg->redeem(117, "TAR-TGT-5USD-US", $redemption);
//      $c['redemptions'] = $ifg->available_rewards(117);
//      
//      return $c;
      
      $tc = new \BlinkTangoCard\TangoCardAPI();
      
      $ainfo = array(
          "identifier" => "creation-and-things-test1",
          "email" => "livedev1@yahoo.com",
          "customer" => "CreationAndThingsTest1"
      );
      $c['account'] = $tc->account(\Blink\TangoCardConfig::CUSTOMER, \Blink\TangoCardConfig::IDENTIFIER);
//      return $c;
      
      $cc_info = array(
          "customer" => $ainfo['customer'],
          "account_identifier" => $ainfo['identifier'],
          "client_ip" => "55.44.33.22", //$_SERVER['SERVER_ADDR'],
          "credit_card" => array(
              "number" => "4111111111111111",
              "security_code" => "123",
              "expiration" => "2016-11",
              "billing_address" => array(
                "f_name" => "John",
                "l_name" => "Doe",
                "address" => "1234 Fake St",
                "city" => "Springfield",
                "state" => "WA",
                "zip" => "99196",
                "country" => "USA",
                "email" => "livedev1@yahoo.com"
              )
          )
      );
      
//      $c['cc_register'] = $tc->register_cc($cc_info);
//      return $c;
      $cc_token = "28130103";
      
      $cc_fund = array(
          "customer" => $ainfo['customer'],
          "account_identifier" => $ainfo['identifier'],
          "amount" => 100,
          "client_ip" => "55.44.33.22",
          "security_code" => "123",
          "cc_token" => $cc_token
      );
//      $c['cc_fund'] = $tc->fund_cc($cc_fund);
//      return $c;

      $info = array(
          "customer" => \Blink\TangoCardConfig::CUSTOMER,
          "account_identifier" => \Blink\TangoCardConfig::IDENTIFIER,
          "recipient" => array(
              "name" => "John Doe",
              "email" => \Blink\TangoCardConfig::EMAIL),
          "sku" => "800F-E-1000-STD",//AMZN-E-V-STD  - TNGO-E-V-STD
          "reward_message" => "Thank you for participating in the XYZ survey.",
          "reward_subject" => "XYZ Survey, thank you...",
          "reward_from" => "Jon Survey Doe"
      );
      
      $c['order'] = $tc->place_order($info);
      $c['obj'] = is_object($c['order']);
      
//      $c['order'] = $tc->order("115-03691143-07");
      return $c;
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
        } else if($delivery == "text") {
          $targeturl = \Wapo\WapoTargetUrl::queryset()->get(array("wapo"=>$wapo));
          $recipient_list = WapoRecipient::queryset()->depth(1)->filter(array("wapo"=>$wapo))->fetch();
          $message  = sprintf("%s has sent you a Wapo. Follow %s/%s to download.", $wapo->profile, $site, $targeturl->code);
          foreach($recipient_list as $recipient) {
            $result = \BlinkTwilio\Api::send_sms($recipient->contact, $message);
            $recipient->resource = $result->sid;
            $recipient->sent = 1;
            $recipient->save(false);
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
