<?php

namespace Wp {
  require_once("apps/wapo/model.php");

  require_once("apps/wp/config.php");

  require_once("apps/blink-user/api.php");

  require_once("apps/wepay/api.php");
  require_once("apps/blink-user-role/api.php");

  require_once 'apps/blink-mandrill/api/Mandrill.php';

  require_once("apps/blink-tangocard/tangocard/tangocard.php");

  require_once 'apps/blink-bitly/bitly/bitly.php';

  require_once 'apps/blink-bulksms/api.php';

  /**
   * Create a Wapo.
   * @param \Wapo\Wapo $wapo
   * @param \Blink\Request $request
   * @throws \Exception
   */
  function send_wapo($request, $wapo) {
    // Set the bitly api for urls and the base url for anything that needs it.
    $bitlyapi = new \BlinkBitly\BlinkBitlyAPI();
    $base_url = sprintf("%s/wp/download/?wapo_id=%s", \Blink\SiteConfig::SITE, $wapo->id);

    try {
      // General data.
      $delivery_message = $wapo->delivery_message;
      $message = "";

      // Get the target url to be used later.
      $targeturl = \Wapo\WapoTargetUrl::queryset()->get(array("wapo" => $wapo));
      $url = sprintf("%s&code=%s", $base_url, $targeturl->code);
      $shortened = $bitlyapi->shorten($url);
      $bitly_url = ($shortened) ? $shortened : $url;
      
      $wapo->bitlyurl = $bitly_url;

      // Get the tango card api if this is a tango delivery.
      $tangoapi = null;
      if ($wapo->marketplace == "tangocards") {
        $tangoapi = new \BlinkTangoCard\TangoCardAPI(array("request" => $request));
      }

      if ($wapo->delivery_method == "email" || $wapo->delivery_method == "email-list" || $wapo->delivery_method == "mailchimp") {
        $mandrill = new \Mandrill(\Blink\MandrillConfig::API_KEY);
        $tc = \Wapo\TangoCardRewards::get_or_404(array("sku" => $wapo->sku), "Tango Card not found.");

        $struct = array(
            'html' => '',
            'text' => '',
            'subject' => "You have been sent a Wapo.",
            'from_email' => \Blink\MandrillConfig::FROM_EMAIL,
            'to' => array(
                array(
                    'email' => '',
                    'type' => 'to'
                )
            ),
            'headers' => array('Reply-To' => \Blink\MandrillConfig::FROM_EMAIL),
        );

        foreach (\Wapo\WapoRecipient::queryset()->filter(array("wapo" => $wapo))->fetch() as $recipient) {
          $recipient->sent = false;
          if ($wapo->marketplace == "tangocards") {
            // If we have extra.
            if ($recipient->extra != "-") {
              $order = $tangoapi->order($recipient->extra);

              // If we retrieved it correctly, then continue.
              if ($order->success) {
                $message = sprintf("You have been sent a giftcard from %s\n\n", $tc->brand_description);
                
                if($wapo->delivery_message) {
                  $message .= $wapo->delivery_message . "\n\n";
                }
                
                $message .= sprintf("Your number is: %s\n", $order->order->reward->number);

                if (isset($order->order->reward->pin)) {
                  $message .= sprintf("Your pin number is: %s\n\n", $order->order->reward->pin);
                } else {
                  $message .= sprintf("Redemption url is: %s\n\n", $order->order->reward->redemption_url);
                }

                $message .= "Enjoy your gift.\nWapo Team.";
                
//                $struct['html'] = $message;
                $struct['text'] = $message;
                $struct['to'][0]['email'] = $recipient->contact;
                
                $async = false;
                $ip_pool = 'Main Pool';
                $result = $mandrill->messages->send($struct, $async, $ip_pool);
                if (in_array($result[0]['status'], array("sent", "queued", "scheduled"))) {
                  $recipient->sent = true;
                }

//                $recipient->sent = @mail($recipient->contact, "You have been sent a Wapo.", $message);
              }
            }
          } else if ($wapo->marketplace == "i-feel-goods") {
            
          } else if ($wapo->marketplace == "wapo") {
            if ($wapo->delivery_message) {
              $message = sprintf("%s Click here '%s' to download your Wapo. ", $wapo->delivery_message, $bitly_url);
            } else {
              $message = sprintf("You have been sent a Wapo. Click here '%s' to download your Wapo. ", $bitly_url);
            }

            $recipient->sent = @mail($recipient->contact, "You have been sent a Wapo.", $message);
          }

          $recipient->save(false);
        }
      } else if ($wapo->delivery_method == "any-facebook-friends") {
        $message = "";
        if ($wapo->delivery_message) {
          $message = $wapo->delivery_message . " Download: " . $bitly_url;
        } else {
          $message = sprintf("Download your free Wapo here %s", $bitly_url);
        }

        $facebook = fb_post_feed($request, $message, $bitly_url);
        if ($facebook) {
          $wapo->resource = $facebook->getProperty('id');
        }
      } else if ($wapo->delivery_method == "facebook-page") {
        $message = "";
        if ($wapo->delivery_message) {
          $message = $wapo->delivery_message . " Download: " . $bitly_url;
        } else {
          $message = sprintf("Download your free Wapo here %s", $bitly_url);
        }

        $facebook = fb_page_post_feed($request, $wapo->external, $message, $bitly_url);
        if ($facebook) {
          $wapo->resource = $facebook->getProperty('id');
        }
      } else if ($wapo->delivery_method == "any-twitter-followers") {
        // Prepare the tweet message.
        $message = "";
        if ($wapo->delivery_message) {
          $message .= $wapo->delivery_message . " Download: " . $bitly_url;
        } else {
          $message .= sprintf("Download your free Wapo here %s", $bitly_url);
        }

        // Tweet and set the resource. Update the wapo.
        $tweet = tweet(array("status" => $message), $request);
        if ($tweet) {
          $wapo->resource = $tweet->id;
        }
      } else if ($wapo->delivery_method == "select-twitter-followers") {
        // Prepare the tweet message.
        $message = "";
        if ($wapo->delivery_message) {
          $message .= $wapo->delivery_message . " Download: " . $bitly_url;
        } else {
          $message .= sprintf("%s sent you a Wapo. Download: %s", $wapo->profile, $bitly_url);
        }

        // Get the twitter followers and post to them.
        $recipient_list = \Wapo\WapoRecipient::queryset()->depth(1)->filter(array("wapo" => $wapo))->fetch();
        foreach ($recipient_list as $recipient) {
          $info = array(
              "status" => sprintf("@%s %s", $recipient->name, $message)
          );
          $tweet = tweet($info, $request);
          if ($tweet) {
            $recipient->resource = $tweet->id;
            $recipient->sent = 1;
            $recipient->save(false);
          }
        }
      } else if ($wapo->delivery_method == "text") {
        // Create bulksms api object.
        $bulksms = new \BlinkBulkSMS\BulkSMSAPI();

        // Create the delivery message.
        $message = sprintf("%s sent you a Wapo. Download here %s.", $wapo->profile, $bitly_url);

        // Send to each recipient.
        $recipient_list = \Wapo\WapoRecipient::queryset()->depth(1)->filter(array("wapo" => $wapo))->fetch();
        foreach ($recipient_list as $recipient) {
          // Send message and check for results.
          $result = $bulksms->send_seven_bit_sms($message, $recipient->contact);

          // Check results and update the ricipient object.
          if ($result[0]) {
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
    
    $wapo->status = "sent";
    $wapo->save(false);
  }

  // Send a Tweet.
  function tweet($data, $request) {
    list($tweet_error, $media_error, $tweet) = (new \BlinkTwitter\BlinkTwitterAPI($request))->tweet($data);
    if ($tweet_error) {
      return null;
    } else {
      return $tweet;
    }
  }

  // Post to feed.
  function fb_post_feed($request, $message, $link) {
    return (new \BlinkFacebook\BlinkFacebookApi($request))->postToFeed($message, $link);
  }

  // Post to page feed.
  function fb_page_post_feed($request, $page_id, $message, $link) {
    return (new \BlinkFacebook\BlinkFacebookApi($request))->postToPageFeed($page_id, $message, $link);
  }

}
