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
  
  require_once 'apps/blink-bulksms/api.php';


  /**
   * 
   * @param \Blink\Request $request
   * @return array
   */
  function send_wapo($request) {
    try {
      // Validate the Wapo.
      $wapo = \Wapo\Wapo::get_or_null(array("id" => $request->session->find("wapo_id")));
//      $wapo = \Wapo\Wapo::queryset()->depth(2)->get(array("id" => $request->session->find("wapo_id")));
//      if (!$wapo) {
//        throw new \Exception("Wapo error: Wapo not found.");
//      }

      // Determine which Wapo type we're sending and call the appropriate function.
      if ($wapo->module->tag == "gift") {
        return send_wapo_general($wapo, $request);
      } else if ($wapo->module->tag == "announcement") {
        return send_wapo_announcement($wapo, $request);
      }
    } catch (\Exception $ex) {
      return array(true, $ex->getMessage());
    }

    return array(false, '', $wapo);
  }

  function send_wapo_general($wapo, $request) {
    $bitlyapi = new \BlinkBitly\BlinkBitlyAPI();
    $base_url = sprintf("%s/wp/download/?wapo_id=%s", \Blink\SiteConfig::SITE, $wapo->id);
    
    try {
      $site = \Blink\SiteConfig::SITE;
      
      $delivery_message = $wapo->delivery_message;
      $delivery = $wapo->delivery_method_abbr;
      $message = "";
      
      $tangoapi = null;
      if($wapo->promotion->name == "Tango Card") {
        $tangoapi = new \BlinkTangoCard\TangoCardAPI(array("request"=>$request));
      }
      
      if ($delivery == "e" || $delivery == "el" || $delivery == "mailchimp") {
        $mail = \Swift\Api::Message();

        foreach (\Wapo\WapoRecipient::queryset()->filter(array("wapo" => $wapo))->fetch() as $recipient) {
          //          $mail->setSubject(sprintf("%s has contacted us.", $this->form->get("name")));
          //          $mail->setFrom(array($this->form->get("email") => $this->form->get("name")));
          //          $mail->addReplyTo($this->form->get("email"));
          //          $mail->setTo(array("livedev1@yahoo.com" => "Wapo.co"));//creationandthings@gmail.com
          //          $mail->addCc(array("creationandthings@gmail.com" => "Wapo.co"));//
          //          $message = \Blink\render_get($context, TemplateConfig::Template("frontend/contact_us.twig"));
          //          $mail->setBody($message, "text/html");
          //          $result = \Swift\Api::Send($mail);
//            $mail->setSubject("Subject");
//            $mail->setFrom(array("swanjie3@yahoo.com" => ".."));
//            //$mail->addReplyTo($this->form->get("email"));
//            $mail->setTo(array($recipient->contact => ".."));//creationandthings@gmail.com
//            //$mail->addCc(array("creationandthings@gmail.com" => "Wapo.co"));//
//            $message = "Testing email.";
//            $result = \Swift\Api::Send($mail);

          // Create the url and shorten it. If the shorten didn't work, use original url.
//          $url = sprintf("%s&code=%s", $base_url, $recipient->targeturl->code);
//          $shortened = $bitlyapi->shorten($url);
//          $shortened = ($shortened) ? $shortened : $url;
//          
//          if ($delivery_message) {
//            $message = $delivery_message . " " . $shortened;
//          } else {
//            $message = sprintf("Click here '%s' to download your Wapo.", $shortened);
//          }
          
          $recipient->sent = false;
          if($wapo->promotion->name == "Tango Card") {
            // If we have extra.
            if ($recipient->extra != "-") {
              $order = $tangoapi->order($recipient->extra);
              
              // If we retrieved it correctly, then continue.
              if($order->success) {
                $tc = \Wapo\TangoCardRewards::get_or_404(array("sku"=>$wapo->sku), "Tango Card not found.");
                
                $message = sprintf("You have been sent a giftcard from %s\n\n", $tc->brand_description);
                $message .= $delivery_message . "\n\n";
                $message .= sprintf("Your number is: %s\n", $order->order->reward->number);
                
                if(isset($order->order->reward->pin)) {
                  $message .= sprintf("Your pin number is: %s\n\n", $order->order->reward->pin);
                } else {
                  $message .= sprintf("Redemption url is: %s\n\n", $order->order->reward->redemption_url);
                }
                
                $message .= "Enjoy your gift.\nWapo Team.";
                $recipient->sent = @mail($recipient->contact, "You have been sent a Wapo.", $message);
              }
            }
          } else if($wapo->promotion->name == "I Feel Goods") {
            
          }
          
          $recipient->save(false);
        }
      } else if ($delivery == "aff") {
        $targeturl = \Wapo\WapoTargetUrl::queryset()->get(array("wapo" => $wapo));
        
        // Create the url and shorten it. If the shorten didn't work, use original url.
        $url = sprintf("%s&code=%s", $base_url, $targeturl->code);
        $shortened = $bitlyapi->shorten($url);
        $shortened = ($shortened) ? $shortened : $url;
        
        if ($delivery_message) {
          $message = $delivery_message;
        } else {
          $message = "You have been sent a Wapo. Click below to download.";
        }
        
        $facebook = fb_post_feed($request, $message, $shortened);
        if ($facebook) {
          $wapo->resource = $facebook->getProperty('id');
          $wapo->save(false);
        }
      } else if ($delivery == "fp") {
        $targeturl = \Wapo\WapoTargetUrl::queryset()->get(array("wapo" => $wapo));
        
        // Create the url and shorten it. If the shorten didn't work, use original url.
        $url = sprintf("%s&code=%s", $base_url, $targeturl->code);
        $shortened = $bitlyapi->shorten($url);
        $shortened = ($shortened) ? $shortened : $url;
        
        if ($delivery_message) {
          $message = $delivery_message;
        } else {
          $message = "You have been sent a Wapo. Click below to download.";
        }
        
        $facebook = fb_page_post_feed($request, $wapo->external, $message, $shortened);
        if ($facebook) {
          $wapo->resource = $facebook->getProperty('id');
          $wapo->save(false);
        }
        
      } else if (in_array($delivery, array("stf", "atf"))) {
        $targeturl = \Wapo\WapoTargetUrl::queryset()->get(array("wapo" => $wapo));
        
        // Create the url and shorten it. If the shorten didn't work, use original url.
        $url = sprintf("%s&code=%s", $base_url, $targeturl->code);
        $shortened = $bitlyapi->shorten($url);
        $shortened = ($shortened) ? $shortened : $url;
        
        if ($delivery_message) {
          $message = $delivery_message . " " . $shortened;
        } else {
          $message = sprintf("Click here '%s' to download your Wapo. ", $shortened);
        }

        if ($wapo->delivery_method_abbr == "atf") {
          // Tweet and update if it has been sent.
          $tweet = tweet(array("status" => $message), $request);
          if ($tweet) {
            $wapo->resource = $tweet->id;
//            $wapo->sent = true;
            $wapo->save(false);
          }
        } else if ($wapo->delivery_method_abbr == "stf") {
          // Get the twitter followers and send to them.
          $recipient_list = \Wapo\WapoRecipient::queryset()->depth(1)->filter(array("wapo" => $wapo))->fetch();
          foreach ($recipient_list as $recipient) {
            $info = array(
                "status" => sprintf("@%s %s", $recipient->name, $message)
            );
            $tweet = tweet($info, $request);
            if($tweet) {
              $recipient->resource = $tweet->id;
              $recipient->sent = 1;
              $recipient->save(false);
            }
          }
        } else {
          throw new \Exception("Invalid send designation.");
        }
      } else if ($delivery == "text") {
        $bulksms = new \BlinkBulkSMS\BulkSMSAPI();
        $targeturl = \Wapo\WapoTargetUrl::queryset()->get(array("wapo" => $wapo));
        
        // Create the url and shorten it. If the shorten didn't work, use original url.
        $url = sprintf("%s&code=%s", $base_url, $targeturl->code);
        $shortened = $bitlyapi->shorten($url);
        $shortened = ($shortened) ? $shortened : $url;
        
        $recipient_list = \Wapo\WapoRecipient::queryset()->depth(1)->filter(array("wapo" => $wapo))->fetch();
        $message = sprintf("%s has sent you a Wapo. Follow %s to download.", $wapo->profile, $shortened);
        foreach ($recipient_list as $recipient) {
          $result = $bulksms->send_seven_bit_sms($message, $recipient->contact);
          
          if($result[0]) {
            $recipient->resource = $result[1];
            $recipient->sent = 1;
          } else {
            $recipient->sent = 0;
          }
          
          $recipient->save(false);
        }
      }
    } catch (\Exception $ex) {
      throw $ex;
    }
  }

  function send_wapo_announcement($wapo, $request) {
    try {
      $error = false;

      $recipient_list = \Wapo\WapoRecipient::queryset()->filter(array("wapo" => $wapo))->fetch();
      foreach ($recipient_list as $recipient) {
        // If Twitter announcement, tweet.
        if ($recipient->name == "twitter_announcement") {
          // Tweet and update if it has been sent.
          $tweet = tweet(array("status" => $wapo->delivery_message), $request);
          if ($tweet) {
            $recipient->resource = $tweet->id;
            $recipient->sent = true;
            $recipient->save(false);
          } else {
            $error = true;
          }
        } else if ($recipient->name == "facebook_announcement") {
          // Post to Facebook Feed.
          $facebook = fb_post_feed($request, $wapo->delivery_message, "");
          if ($facebook) {
            $recipient->resource = $facebook->getProperty('id');
            $recipient->sent = true;
            $recipient->save(false);
          } else {
            $error = true;
          }
        } else if ($recipient->name == "facebook_page_announcement") {
          // Post to Facebook Page Feed.
          $facebook = fb_page_post_feed($request, $recipient->contact, $wapo->delivery_message, "");
          if ($facebook) {
            $recipient->resource = $facebook->getProperty('id');
            $recipient->sent = true;
            $recipient->save(false);
          } else {
            $error = true;
          }
        }
      }
    } catch (\Exception $ex) {
      throw $ex;
    }

    return $error;
  }

  /*   * *SEND TO SPECIFIC SERVICE.* */

  function tweet($data, $request) {
    list($tweet_error, $media_error, $tweet) = (new \BlinkTwitter\BlinkTwitterAPI($request))->tweet($data);
    if ($tweet_error) {
      return null;
    } else {
      return $tweet;
    }
  }
  
  function fb_post_feed($request, $message, $link) {
    return (new \BlinkFacebook\BlinkFacebookApi($request))->postToFeed($message, $link);
  }
  
  function fb_page_post_feed($request, $page_id, $message, $link) {
    return (new \BlinkFacebook\BlinkFacebookApi($request))->postToPageFeed($page_id, $message, $link);
  }

}
