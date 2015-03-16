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

  /**
   * 
   * @param \Blink\Request $request
   * @return array
   */
  function send_wapo($request) {
    try {
      // Validate the Wapo.
      $wapo = \Wapo\Wapo::get_or_null(array("id" => $request->session->find("wapo_id")));
      if (!$wapo) {
        throw new \Exception("Wapo error: Wapo not found.");
      }

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
    $base_url = sprintf("%s/wpd/", \Blink\SiteConfig::$Site);
    
    try {
      $site = \Blink\SiteConfig::$Site;
      
      $delivery_message = $wapo->delivery_message;
      $delivery = $wapo->delivery_method_abbr;
      $message = "";

      if ($delivery == "e" || $delivery == "el" || $delivery == "mailchimp") {
        $mail = \Swift\Api::Message();

        foreach (WapoRecipient::queryset()->filter(array("wapo" => $wapo))->fetch() as $recipient) {
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

          // Create the url and shorten it. If the shorten didn't work, use original url.
          $url = sprintf("%s?code=%s", $base_url, $recipient->targeturl->code);
          $shortened = $bitlyapi->shorten($url);
          $shortened = ($shortened) ? $shortened : $url;
          
          if ($delivery_message) {
            $message = $delivery_message . " " . $shortened;
          } else {
            $message = sprintf("Click here '%s' to download your Wapo.", $shortened);
          }

          $recipient->sent = @mail($recipient->contact, "You have been sent a Wapo.", $message);
          $recipient->save(false);
        }
      } else if ($delivery == "aff") {
        $targeturl = \Wapo\WapoTargetUrl::queryset()->get(array("wapo" => $wapo));
        
        // Create the url and shorten it. If the shorten didn't work, use original url.
        $url = sprintf("%s?code=%s", $base_url, $targeturl->code);
        $shortened = $bitlyapi->shorten($url);
        $shortened = ($shortened) ? $shortened : $url;
        
        if ($delivery_message) {
          $message = $delivery_message . " " . $shortened;
        } else {
          $message = sprintf("Click here '%s' to download your Wapo.", $shortened);
        }
        $c['message'] = $message;
      } else if ($delivery == "fp") {
        $targeturl = \Wapo\WapoTargetUrl::queryset()->get(array("wapo" => $wapo));
        
        // Create the url and shorten it. If the shorten didn't work, use original url.
        $url = sprintf("%s?code=%s", $base_url, $targeturl->code);
        $shortened = $bitlyapi->shorten($url);
        $shortened = ($shortened) ? $shortened : $url;
        
        if ($delivery_message) {
          $message = $delivery_message . " " . $shortened;
        } else {
          $message = sprintf("Click here '%s' to download your Wapo.", $shortened);
        }
        $c['message'] = $message;
        $c['facebook_page_id'] = $wapo->external;
      } else if (in_array($delivery, array("stf", "atf"))) {
        $targeturl = \Wapo\WapoTargetUrl::queryset()->get(array("wapo" => $wapo));
        
        // Create the url and shorten it. If the shorten didn't work, use original url.
        $url = sprintf("%s?code=%s", $base_url, $targeturl->code);
        $shortened = $bitlyapi->shorten($url);
        $shortened = ($shortened) ? $shortened : $url;
        
        if ($delivery_message) {
          $message = $delivery_message . " " . $shortened;
        } else {
          $message = sprintf("Click here '%s' to download your Wapo.", $shortened);
        }

        if ($wapo->delivery_method_abbr == "atf") {
          $info = array(
              "status" => $message
          );
          $tweet = $connection->post('statuses/update', $info);
          if($tweet) {
            $wapo->resource = $tweet->id;
            $wapo->save(false);
          }
        } else if ($wapo->delivery_method_abbr == "stf") {
          // Get the twitter followers and send to them.
          $recipient_list = WapoRecipient::queryset()->depth(1)->filter(array("wapo" => $wapo))->fetch();
          foreach ($recipient_list as $recipient) {
            $info = array(
                "status" => sprintf("@%s %s", $recipient->contact, $message)
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
        $targeturl = \Wapo\WapoTargetUrl::queryset()->get(array("wapo" => $wapo));
        $recipient_list = WapoRecipient::queryset()->depth(1)->filter(array("wapo" => $wapo))->fetch();
        $message = sprintf("%s has sent you a Wapo. Follow %s/%s to download.", $wapo->profile, $site, $targeturl->code);
        foreach ($recipient_list as $recipient) {
          $result = \BlinkTwilio\Api::send_sms($recipient->contact, $message);
          $recipient->resource = $result->sid;
          $recipient->sent = 1;
          $recipient->save(false);
        }
      }
    } catch (Exception $ex) {
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
          $tweet = tweet(array("status" => $wapo->delivery_message . date("H:i:s")), $request);
          if ($tweet) {
            $recipient->resource = $tweet->id;
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

}
