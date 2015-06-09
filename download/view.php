<?php

namespace Wp {
  require_once("blink/base/view/generic.php");
  require_once("blink/base/view/edit.php");
  require_once("blink/base/view/detail.php");
  require_once("blink/base/view/list.php");
  require_once("blink/base/view/wizard.php");
  
  require_once("apps/wp/config.php");
  require_once("apps/wapo/model.php");
  require_once("apps/wp/download/form.php");

  require_once("apps/blink-user/api.php");
  
  /**
   * Fail view, displays a fail view if a query fails.
   */
  class ExpiredView extends \Blink\BaseError500 {

    protected function get_template() {
      return WpTemplateConfig::Template("download/expired.twig");
    }

  }

  function raiseExpired($message = "") {
    global $request;
    $class = new ExpiredView($request);
    $message = ($message) ? $message : "Sorry, the Wapo you are looking for has already expired.";
    $class->set_message($message);
    $class->execute();
  }
  
  /**
   * - Check that Wapo is valid then redirect to appropriate view if it is.
   * - If there is a code in the GET, pre-fill it.
   */
  class CheckWapoRedirectView extends \Blink\RedirectView {
    protected function get_redirect_url() {
      $wapo = \Wapo\Wapo::get_or_404(array("id"=>$this->request->get->find("wapo_id")), "Wapo not found.");
      return sprintf("/wp/download/%s/?%s", $wapo->delivery_method_abbr, $this->request->query_string);
    }
  }
  
  /**
   * Download base view to check if Wapo is valid.
   */
  class BaseDownloadTemplateView extends \Blink\TemplateView {
    protected $wapo = null;
    
    protected function get_template() {
      return WpTemplateConfig::Template("download/download.twig");
    }
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      $c['wapo'] = $this->wapo;
      $c['promotion'] = $this->wapo->promotion;
      $promotioncategory = \Wapo\PromotionCategory::get_or_null(array("id"=>$this->wapo->promotion->promotioncategory));
      
      $sku = null;
      if($promotioncategory->name == "Tango Card") {
        $sku = \Wapo\TangoCardRewards::get_or_null(array("sku"=>$this->wapo->sku));
      }
      
      $c['promotioncategory'] = $promotioncategory;
      $c['sku'] = $sku;
      return $c;
    }
    
    protected function is_expired() {
      if(date('Y-m-d H:i:s') > $this->wapo->expiring_date->format('Y-m-d H:i:s')) {
        return true;
      }
      
      return false;
    }


    protected function dispatch() {
      $this->wapo = \Wapo\Wapo::get_or_404(array("id"=>$this->request->get->find("wapo_id")), "Wapo not found.");
      return parent::dispatch();
    }
  }
  
  /**
   * Any Twitter Follower download.
   */
  class TwitterATFDownloadTemplateView extends BaseDownloadTemplateView {
    protected function get_context_data() {
      $c = parent::get_context_data();
      
      $tapi = new \BlinkTwitter\BlinkTwitterAPI($this->request);
      $twitter_authenticated = $tapi->isLoggedIn();
      $profile = $tapi->getProfile();
      $recipient = null;
      $reward = null;
      
      // Check if source is followed by target.
      $relationship = null;
      if($twitter_authenticated) {
        $relationship = $tapi->getRelationship($this->wapo->sender, $profile->id_str);
        
        // If the target user follows the source user.
        if($relationship->relationship->source->followed_by) {
          $recipient = \Wapo\WapoRecipient::get_or_null(array("wapo"=>$this->wapo,"contact"=>$profile->id_str));
          
          // If not downloaded yet, check if Wapo has expired.
          if(!$recipient && $this->is_expired()) {
            raiseExpired();
          } else if(!$recipient) {
            // Get an empty one.
            $recipient_list = \Wapo\WapoRecipient::queryset()->filter(array("wapo"=>$this->wapo,"contact"=>""))->fetch();
            
            if(!count($recipient_list)) {
              raiseExpired("Sorry, maximum downloadable Wapos have been reached.");
            }
            $recipient = $recipient_list[0];
            $recipient->contact = $profile->id_str;
            $recipient->confirmed = true;
            $recipient->open = true;
            $recipient->downloaded = true;
            $recipient->save(false);
            $this->wapo->downloaded += 1;
            $this->wapo->save(false);
          }
          
          if($this->wapo->promotion->name == "Tango Card") {
            $reward = (new \BlinkTangoCard\TangoCardAPI(array("request"=>$this->request)))->order($recipient->extra);
          }
        }
      }
      
      $c['twitter_authenticated'] = $twitter_authenticated;
      $c['recipient'] = $recipient;
      $c['reward'] = $reward;
      
      return $c;
    }
  }
  
  /**
   * Select Twitter Followers Download.
   */
  class TwitterSTFDownloadTemplateView extends BaseDownloadTemplateView {
    protected function get_context_data() {
      $c = parent::get_context_data();
      
      $tapi = new \BlinkTwitter\BlinkTwitterAPI($this->request);
      $twitter_authenticated = $tapi->isLoggedIn();
      $profile = $tapi->getProfile();
      $recipient = null;
      $reward = null;
      
      

      // Check if source is followed by target.
      if($twitter_authenticated) {
        $recipient = \Wapo\WapoRecipient::get_or_null(array("wapo"=>$this->wapo,"name"=>$profile->screen_name));
        if(!$recipient) {
          raiseExpired("This Wapo was not sent to you.");
        }
        
        // @todo - Consider how long to keep this open if it hasn't been downloaded.
        if($this->is_expired()) {
          raiseExpired();
        }
        
        $recipient->name = $profile->screen_name;
        $recipient->contact = $profile->id_str;
        $recipient->confirmed = true;
        $recipient->open = true;
        $recipient->downloaded = true;
        $recipient->save(false);
        $this->wapo->downloaded += 1;
        $this->wapo->save(false);

        if ($this->wapo->promotion->name == "Tango Card") {
          $reward = (new \BlinkTangoCard\TangoCardAPI(array("request" => $this->request)))->order($recipient->extra);
        }
      }
      
      $c['twitter_authenticated'] = $twitter_authenticated;
      $c['recipient'] = $recipient;
      $c['reward'] = $reward;
      
      return $c;
    }
  }
  
  /**
   * Any Facebook Friends Download.
   */
  class FacebookAFFDownloadTemplateView extends BaseDownloadTemplateView {
    protected function get_context_data() {
      $c = parent::get_context_data();
      
      $fbapi = new \BlinkFacebook\BlinkFacebookApi($this->request);
      $facebook_authenticated = $fbapi->isLoggedIn();
      $profile = null;
      $reward = null;
      
      // Check if they have the 'user_friends' permission.
      $user_friends_permission = false;
      foreach($fbapi->getFacebookPermissions() as $p) {
        if($p->permission == "user_friends" && $p->status == "granted") {
          $user_friends_permission = true;
        }
      }
      
      if ($facebook_authenticated && $user_friends_permission) {
        $profile = $fbapi->getUserProfile();
        $recipient = \Wapo\WapoRecipient::get_or_null(array("wapo" => $this->wapo, "contact" => $profile->id));

        // If not downloaded yet, check if Wapo has expired.
        if (!$recipient && $this->is_expired()) {
          raiseExpired();
        } else if(!$recipient) {
          $relationship = $fbapi->getRelationship($this->wapo->sender);

          // If there is a relationship.
          if (count($relationship)) {
            // Get an empty one.
            $recipient_list = \Wapo\WapoRecipient::queryset()->filter(array("wapo" => $this->wapo, "contact" => ""))->fetch();

            if (!count($recipient_list)) {
              raiseExpired("Sorry, maximum downloadable Wapos have been reached.");
            }

            // Reserve the empty slot.
            $recipient = $recipient_list[0];
            $recipient->name = $profile->name;
            $recipient->contact = $profile->id;
            $recipient->confirmed = true;
            $recipient->open = true;
            $recipient->downloaded = true;
            $recipient->save(false);
            $this->wapo->downloaded += 1;
            $this->wapo->save(false);
          }
        }

        // If we have a recipient.
        if ($recipient && $this->wapo->promotion->name == "Tango Card") {
          $reward = (new \BlinkTangoCard\TangoCardAPI(array("request" => $this->request)))->order($recipient->extra);
        }
      }

      $c['profile'] = $profile;
      $c['reward'] = $reward;
      $c['facebook_authenticated'] = $facebook_authenticated;
      $c['user_friends_permission'] = $user_friends_permission;
      return $c;
    }
  }
  
  /**
   * Facebook Page Download.
   * ref: if we want to add before when: https://developers.facebook.com/docs/graph-api/reference/user/likes
   */
  class FacebookFPDownloadTemplateView extends BaseDownloadTemplateView {
    protected function get_context_data() {
      $c = parent::get_context_data();
      
      $fbapi = new \BlinkFacebook\BlinkFacebookApi($this->request);
      $facebook_authenticated = $fbapi->isLoggedIn();
      $profile = null;
      $reward = null;
      
      // Check if they have the 'user_friends' permission.
      $user_likes_permission = false;
      foreach($fbapi->getFacebookPermissions() as $p) {
        if($p->permission == "user_likes") {
          $user_likes_permission = true;
        }
      }
      
      if ($facebook_authenticated && $user_likes_permission) {
        $profile = $fbapi->getUserProfile();
        $recipient = \Wapo\WapoRecipient::get_or_null(array("wapo" => $this->wapo, "contact" => $profile->id));

        $likes = $fbapi->getPageLikes();

        // If not downloaded yet, check if Wapo has expired.
        if (!$recipient && $this->is_expired()) {
          raiseExpired();
        } else if(!$recipient) {
          $likes = $fbapi->getPageLikes();

          foreach ($likes as $like) {
            if ($like->id == $this->wapo->extra) {
              // Get an empty one.
              $recipient_list = \Wapo\WapoRecipient::queryset()->filter(array("wapo" => $this->wapo, "contact" => ""))->fetch();

              if (!count($recipient_list)) {
                raiseExpired("Sorry, maximum downloadable Wapos have been reached.");
              }

              $recipient = $recipient_list[0];
              $recipient->name = $profile->name;
              $recipient->contact = $profile->id;
              $recipient->confirmed = true;
              $recipient->open = true;
              $recipient->downloaded = true;
              $recipient->save(false);
              $this->wapo->downloaded += 1;
              $this->wapo->save(false);

              break;
            }
          }
        }

        if ($this->wapo->promotion->name == "Tango Card") {
          $reward = (new \BlinkTangoCard\TangoCardAPI(array("request" => $this->request)))->order($recipient->extra);
        }
      }

      $c['profile'] = $profile;
      $c['reward'] = $reward;
      $c['facebook_authenticated'] = $facebook_authenticated;
      $c['user_likes_permission'] = $user_likes_permission;
      return $c;
    }
  }
  
  /**
   * Give the history of Facebook Downloaded Wapos.
   */
  class WapoFacebookHistoryTemplateView extends BaseDownloadTemplateView {
    
  }
  
  class PreviewDownloadTemplateView extends \Blink\TemplateView {
    protected function get_template() {
      return TemplateConfig::Template("download/preview.twig");
    }
  }
  
  class WapoRedirectView extends \Blink\RedirectView {
    protected function get_redirect_url() {
      return sprintf("/wp/download/?code=%s", $this->request->param->param['code']);
    }
  }
  
  // Check that code is a valid Wapo and return the wapo.
  function check_code($code) {
    try {
      $targeturl = \Wapo\WapoTargetUrl::queryset()->get(array("code" => $code), "Wapo not found.");
    } catch(\Exception $ex) {
      return array(
          "error" => true,
          "message" => $ex->getMessage(),
          "url" => "/wp/download/",
          "targeturl" => null
      );
    }

    // Check that Wapo has not expired.
    $now = new \DateTime("now");
    $wapo_date = new \DateTime($targeturl->wapo->expiring_date->format('Y-m-d H:i:s'));
    if($wapo_date < $now) {
      return array(
          "error" => true,
          "message" => "Wapo has expired.",
          "url" => "/wp/download/expired/",
          "targeturl" => $targeturl
      );
    }

    // Check what type it is.
    if(in_array($targeturl->wapo->delivery_method_abbr, array("e", "el"))) {
      return array(
          "error" => false,
          "message" => "",
          "url" => "/wp/download/email/",
          "targeturl" => $targeturl
      );
    } else if(in_array($targeturl->wapo->delivery_method_abbr, array("atf", "stf"))) {
      return array(
          "error" => false,
          "message" => "",
          "url" => "/wp/download/twitter/",
          "targeturl" => $targeturl
      );
    } else if($targeturl->wapo->delivery_method_abbr == "aff") {
      return array(
          "error" => false,
          "message" => "",
          "url" => "/wp/download/aff/",
          "targeturl" => $targeturl
      );
    } else if($targeturl->wapo->delivery_method_abbr == "fp") {
      return array(
          "error" => false,
          "message" => "",
          "url" => "/wp/download/fp/",
          "targeturl" => $targeturl
      );
    } else if($targeturl->wapo->delivery_method_abbr == "ffa") {
      return array(
          "error" => false,
          "message" => "",
          "url" => "/wp/download/ffa/",
          "targeturl" => $targeturl
      );
    }
  }

  // Check that user can download Wapo.
  function check_wapo($targeturl, $extra) {
    // Check that Wapo has not expired.
    $now = new \DateTime("now");
    $wapo_date = new \DateTime($targeturl->wapo->expiring_date->format('Y-m-d H:i:s'));
    if ($wapo_date < $now) {
      return array(
          "error" => true,
          "url" => "/wp/download/expired/",
          "recipient" => null,
          "message"=> "Wapo has expired"
      );
    }
    
    // Check that user can retrieve Wapo based on delivery method.
    $wapo_recipient = null;
    if(in_array($targeturl->wapo->delivery_method_abbr, array("e", "el"))) {
      try {
        $wapo_recipient = \Wapo\WapoRecipient::queryset()->get(array("contact"=>$extra, "targeturl"=>$targeturl), "Wapo was not sent to this email account.");
      } catch (\Exception $ex) {
        return array(
            "error" => true,
            "url" => "/wp/download/notsent/",
            "recipient" => null,
            "message" => $ex->getMessage()
        );
      }
    } else if($targeturl->wapo->delivery_method_abbr == "stf") {
      try {
        $wapo_recipient = \Wapo\WapoRecipient::queryset()->get(array("contact"=>$extra, "targeturl"=>$targeturl), "Wapo was not sent to this Twitter account.");
      } catch (\Exception $ex) {
        return array(
            "error" => true,
            "url" => "/wp/download/notsent/",
            "recipient" => null,
            "message" => $ex->getMessage()
        );
      }
    } else if($targeturl->wapo->delivery_method_abbr == "atf") {
      $recipient = array(
          "targeturl" => $targeturl,
          "wapo" => $targeturl->wapo,
          "contact" => $extra,
      );
      $wapo_recipient = \Wapo\WapoRecipient::get_or_create($recipient);
    } else if(in_array($targeturl->wapo->delivery_method_abbr, array("aff", "fp"))) {
      $recipient = array(
          "targeturl" => $targeturl,
          "wapo" => $targeturl->wapo,
          "contact" => $extra,
      );
      $wapo_recipient = \Wapo\WapoRecipient::get_or_create($recipient);
    } else if($targeturl->wapo->delivery_method_abbr == "ffa") {
      try {
        $wapo_recipient = \Wapo\WapoRecipient::queryset()->get(array("contact"=>$extra, "targeturl"=>$targeturl), "Error with confirmation email.");
      } catch (\Exception $ex) {
        return array(
            "error" => true,
            "url" => "/wp/download/notsent/",
            "recipient" => null,
            "message" => $ex->getMessage()
        );
      }
    }
    
    return array(
        "error" => false,
        "url" => "",
        "recipient" => $wapo_recipient,
        "message" => ""
    );
  }
  
  function prepare_download($recipient) {
    // Find where Wapo download is located.
    
    // Copy it to a new location.
    $recipient->download_code = hash("SHA512", $recipient->id . $recipient->contact);
    $recipient->confirmed = true;
    $recipient->date_confirmed = \date("Y-m-d H:i:s");
    $recipient->save(false);
    
    return array(
        "error"=> false,
        "url" => sprintf("/wp/download/download/?download=%s", $recipient->download_code)
    );
  }
  
  /**
   * - Validate that the code was sent to the email.
   */
  class WapoEmailView extends \Blink\FormView {
    protected $form_class = "Wp\WapoEmailForm";
    
    protected function form_valid() {
      // Check the code.
      $result = check_code($this->request->session->find("code"));
      if($result['error']) {
        \Blink\Messages::error($result['message']);
        return \Blink\HttpResponseRedirect($result['url']);
      }
      
      // Check target url.
      $tresult = check_wapo($result['targeturl'], $this->form->get("email"));
      if($tresult['error']) {
        \Blink\Messages::error($tresult['message']);
        return \Blink\HttpResponseRedirect($tresult['url']);
      }
      
      // Set the variables.
      $this->request->session->set("email", $this->form->get("email"));
      $this->request->session->set("code", $this->request->session->find("code"));
      
      // Send the confirmation code.
      $recipient = $tresult['recipient'];
      $hash = hash("SHA512", $recipient->id . "2n3ialv" . $recipient->contact);
      $url = sprintf("%s/wp/download/email/confirm/?email=%s&code=%s", \Blink\SiteConfig::SITE, $recipient->contact, $hash);
      $code = dechex(rand(1, 16777215));
      $message = sprintf("Follow url: [%s] \n Or enter code: %s to download Wapo.", $url, $code);
      
      if(mail($recipient->contact, "Please confirm your email to download Wapo.", $message)) {
        \Blink\Messages::success("Confirmation email sent.");
      } else {
        \Blink\Messages::error("Error sending confirmation email.");
        return \Blink\HttpResponseRedirect("/wp/download/email/senderror/");
      }
      
      $recipient->confirm = $code;
      $recipient->email_confirm_url = $hash;
      $recipient->save(false);
      
      // Go to the confirmation page.
      return \Blink\HttpResponseRedirect("/wp/download/email/code/confirm/");
    }
  }
  
  /**
   * - If there is an error sending an email.
   */
  class EmailSendErrorView extends \Blink\TemplateView {
    protected function get_template() {
      return TemplateConfig::Template("download/email_senderror.twig");
    }
  }
  
  /**
   * - If the user does not follow the Wapo user.
   */
  class TwitterNoFollowView extends \Blink\TemplateView {
    protected function get_template() {
      return TemplateConfig::Template("download/twitter_nofollow.twig");
    }
  }
  
  /**
   * - Display the page that the Wapo has expired.
   */
  class WapoExpiredView extends \Blink\TemplateView {
    protected function get_template() {
      return TemplateConfig::Template("download/wapo_expired.twig");
    }
  }
  
  /**
   * - All FFA need confirmation to deter fraud.
   */
  class WapoFreeForAllEmailView extends \Blink\FormView {

    protected $form_class = "Wp\WapoEmailForm";
    
    protected function get_template() {
      return TemplateConfig::Template("download/send_email_code.twig");
    }

    protected function form_valid() {
      // Check that the code set is still valid.
      $result = check_code($this->request->session->find("code"));
      if($result['error']) {
        \Blink\Messages::error($result['message']);
        return \Blink\HttpResponseRedirect($result['url']);
      }
      $targeturl = $result['targeturl'];
      
      $recipient = \Wapo\WapoRecipient::get_or_create_save(array("wapo"=>$targeturl->wapo,"targeturl"=>$targeturl,"contact"=>$this->form->get("email")), false);
      
      // Check if this user has downloaded.
      if($recipient->downloaded) {
        \Blink\Messages::error("You have already downloaded Wapo.");
        return \Blink\HttpResponseRedirect("/wp/download/downloaded/");
      }
      
      // Send the confirmation code.
      $hash = hash("SHA512", $recipient->id . "2n3ialv" . $recipient->contact);
      $url = sprintf("%s/wp/download/ffa/confirm/?email=%s&code=%s", \Blink\SiteConfig::SITE, $recipient->contact, $hash);
      $code = dechex(rand(1, 16777215));
      $message = sprintf("Follow url: [%s] \n Or enter code: %s to download Wapo.", $url, $code);
      
      if(mail($recipient->contact, "Please confirm your email to download Wapo.", $message)) {
        \Blink\Messages::success("Confirmation email sent.");
      } else {
        \Blink\Messages::error("Error sending confirmation email.");
        return \Blink\HttpResponseRedirect("/wp/download/email/senderror/");
      }
      
      $recipient->confirm = $code;
      $recipient->email_confirm_url = $hash;
      $recipient->save(false);
      
      $this->request->session->set("email", $this->form->get("email"));
      
      return \Blink\HttpResponseRedirect("/wp/download/ffa/code/confirm/");
    }
    
    protected function get() {
      // Check that the code set is still valid.
      $result = check_code($this->request->session->find("code"));
      if($result['error']) {
        \Blink\Messages::error($result['message']);
        return \Blink\HttpResponseRedirect($result['url']);
      }
      
      $targeturl = $result['targeturl'];
      
      // If Wapo does not need email confirmation, create a blank recipient and prepare download.
      if(!$targeturl->wapo->email_confirmation) {
        $array = array(
            "targeturl" => $targeturl,
            "wapo" => $targeturl->wapo,
            "contact" => "-----------",
        );
        $recipient = \Wapo\WapoRecipient::create_save($array, false);
        $download = prepare_download($recipient);
        return \Blink\HttpResponseRedirect($download['url']);
      }
      
      return parent::get();
    }

  }
  
  /**
   * - Confirm email code for ffa.
   */
  class WapoFreeForAllConfirmEmailCodeView extends \Blink\FormView {

    protected $form_class = "Wp\ConfirmEmailCode";
    
    protected function get_template() {
      return TemplateConfig::Template("download/confirm_email_code.twig");
    }

    protected function form_valid() {
      $confirm = $this->form->get("confirm");
      
      // Check that the code set is still valid.
      $result = check_code($this->request->session->find("code"));
      if($result['error']) {
        \Blink\Messages::error($result['message']);
        return \Blink\HttpResponseRedirect($result['url']);
      }
      
      // Check target url.
      $tresult = check_wapo($result['targeturl'], $this->request->session->find("email"));
      if($tresult['error']) {
        \Blink\Messages::error($tresult['message']);
        return \Blink\HttpResponseRedirect($tresult['url']);
      }
      $recipient = $tresult['recipient'];
      
      // Check that confirm code matches.
      if($recipient->confirm != $confirm) {
        \Blink\Messages::error("Confirmation code not found.");
        return $this->form_invalid();
      }
      
      // Prepare the download.
      $download = prepare_download($recipient);
      
      return \Blink\HttpResponseRedirect($download['url']);
    }

  }
  
  /**
   * - Confirm special url for the same ffa code.
   */
  class WapoFreeForAllConfirmEmailCodeUrlView extends \Blink\RedirectView {
    protected function get_redirect_url() {
      // Check that confirmation is set.
      $code = $this->request->get->find("code");
      $email = $this->request->get->find("email");
      
      if (!$code) {
        \Blink\Messages::error("Confirmation code not found.");
        return "/wp/download/";
      }
      
      if (!$email) {
        \Blink\Messages::error("Confirmation email not found.");
        return "/wp/download/";
      }
      
      try {
        // Check that confirmation is valid.
        $recipient = \Wapo\WapoRecipient::queryset()->get(array("contact"=>$email,"email_confirm_url"=>$code), "Confirmation not found.");
      } catch (\Exception $ex) {
        \Blink\Messages::error($ex->getMessage());
        return "/wp/download/";
      }
      
      // Check Wapo.
      $result = check_wapo($recipient->targeturl, $email);
      if($result['error']) {
        \Blink\Messages::error($result['message']);
        return $result['url'];
      }
      
      $download = prepare_download($recipient);
      return $download['url'];
    }
  }
  
  /**
   * - Confirm email code.
   */
  class WapoConfirmEmailCodeView extends \Blink\FormView {

    protected $form_class = "Wp\ConfirmEmailCode";
    
    protected function get_template() {
      return TemplateConfig::Template("download/confirm_email_code.twig");
    }

    protected function form_valid() {
      $confirm = $this->form->get("confirm");
      
      // Check that the code set is still valid.
      $result = check_code($this->request->session->find("code"));
      if($result['error']) {
        \Blink\Messages::error($result['message']);
        return \Blink\HttpResponseRedirect($result['url']);
      }
      
      // Check target url.
      $tresult = check_wapo($result['targeturl'], $this->request->session->find("email"));
      if($tresult['error']) {
        \Blink\Messages::error($tresult['message']);
        return \Blink\HttpResponseRedirect($tresult['url']);
      }
      $recipient = $tresult['recipient'];
      
      // Check that confirm code matches.
      if($recipient->confirm != $confirm) {
        \Blink\Messages::error("Confirmation code not found.");
        return $this->form_invalid();
      }
      
      // Prepare the download.
      $download = prepare_download($recipient);
      
      return \Blink\HttpResponseRedirect($download['url']);
    }

  }

  /**
   * - Confirm special url for the same code.
   */
  class WapoConfirmEmailCodeUrlView extends \Blink\RedirectView {
    protected function get_redirect_url() {
      // Check that confirmation is set.
      $code = $this->request->get->find("code");
      $email = $this->request->get->find("email");
      
      if (!$code) {
        \Blink\Messages::error("Confirmation code not found.");
        return "/wp/download/";
      }
      
      if (!$email) {
        \Blink\Messages::error("Confirmation email not found.");
        return "/wp/download/";
      }
      
      try {
        // Check that confirmation is valid.
        $recipient = \Wapo\WapoRecipient::queryset()->get(array("contact"=>$email,"email_confirm_url"=>$code), "Confirmation not found.");
      } catch (\Exception $ex) {
        \Blink\Messages::error($ex->getMessage());
        return "/wp/download/";
      }
      
      // Check Wapo.
      $result = check_wapo($recipient->targeturl, $email);
      if($result['error']) {
        \Blink\Messages::error($result['message']);
        return $result['url'];
      }
      
      $download = prepare_download($recipient);
      return $download['url'];
    }
  }
  
  /**
   * - Check that logged in Twitter user can download the Wapo.
   * - https://dev.twitter.com/docs/api/1.1/get/friendships/lookup
   */
  class TwitterUserCanDownloadWapoView extends \Blink\FormView {
    protected $form_class = "Blink\Form";
    
    protected function get_template() {
      return TemplateConfig::Template("download/twitter.twig");
    }
    
    protected function form_valid() {
      // Check that the code set is still valid.
      $cresult = check_code($this->request->session->find("code"));
      if($cresult['error']) {
        \Blink\Messages::error($cresult['message']);
        return \Blink\HttpResponseRedirect($cresult['url']);
      }
      $targeturl = $cresult['targeturl'];
      
      // Check that user is authenticated using twitter.
      $connection = new \TwitterOAuth(\Blink\ConfigTwitter::$ConsumerKey, \Blink\ConfigTwitter::$ConsumerSecret, $this->request->session->nmsp("twitter")->get('oauth_token'), $this->request->session->nmsp("twitter")->get('oauth_token_secret'));
      $account = $connection->get('account/verify_credentials');
      if (!isset($account->screen_name)) {
        \Blink\Messages::error("Please log in with Twitter.");
        return \Blink\HttpResponseRedirect("/wp/download/twitter/");
      }
      
      // Check the relationship between this user and the Wapo sender.
      $relationship = $connection->get("friendships/lookup", array("screen_name"=>$targeturl->wapo->external));
      try {
        if(in_array("following", $relationship[0]->connections)) {}
      } catch (\Exception $ex) {
        \Blink\Messages::error("You must be following Twitter user to download Wapo.");
        return \Blink\HttpResponseRedirect("/wp/download/twitter/nofollow/");
      }
      
      // Check Wapo.
      $result = check_wapo($targeturl, $account->screen_name);
      if($result['error']) {
        \Blink\Messages::error($result['message']);
        return \Blink\HttpResponseRedirect($result['url']);
      }
      
      $download = prepare_download($result['recipient']);
      
      return \Blink\HttpResponseRedirect($download['url']);
    }
  }
  
  /**
   * - Get the download that the user is dowloading. 
   */
  class WapoDownloadView extends \Blink\TemplateView {
    protected function get_template() {
      return TemplateConfig::Template("download/download.twig");
    }
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      
      $profiles = \Wapo\Profile::queryset()->all();
      $c['profile'] = $profiles[0];
      
      $promotion = \Wapo\Promotion::queryset()->all();
      $c['promotion'] = $promotion[0];
      
      return $c;
    }
  }
  
  /**
   * - Link to the actual file (if audio or video or e-book, etc.) to download.
   */
  class WapoDownloadFileView extends \Blink\TemplateView {
    protected function get_content_type() {
      return \Blink\View::CONTENT_TEXT;
    }
    
    protected function render_plain() {
      return "Download not found.";
    }
    
    protected function get() {
      // Check the download code.
      $response = new \Blink\Response();
      $response->file = "";
    }
  }
  
  
  /**
   * - Allow user to log in to FB.
   * - Verify that they are Wapo creator's friend.
   * - Capture their FB id.
   */
  class WapoAnyFacebookFriendsView extends \Blink\FormView {

    protected $form_class = "Wp\FacebookUserIdForm";
    
    protected function get_template() {
      return TemplateConfig::Template("download/any_facebook_friend.twig");
    }
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      $result = check_code($this->request->session->find("code"));
      $c['targeturl'] = $result['targeturl'];
      return $c;
    }

    protected function form_valid() {
      // Check that the code set is still valid.
      $result = check_code($this->request->session->find("code"));
      if($result['error']) {
        \Blink\Messages::error($result['message']);
        return \Blink\HttpResponseRedirect($result['url']);
      }
      
      // Check target url.
      $tresult = check_wapo($result['targeturl'], $this->form->get("facebook_id"));
      if($tresult['error']) {
        \Blink\Messages::error($tresult['message']);
        return \Blink\HttpResponseRedirect($tresult['url']);
      }
      $recipient = $tresult['recipient'];
      
      // Prepare the download.
      $download = prepare_download($recipient);
      
      return \Blink\HttpResponseRedirect($download['url']);
    }

  }
  
  /**
   * - Show page for user to log in to Facebook.
   * - Validate that they like the FB page.
   * - Submit their FB ID.
   */
  class WapoFacebookPageView extends \Blink\FormView {

    protected $form_class = "Wp\FacebookUserIdForm";
    
    protected function get_template() {
      return TemplateConfig::Template("download/facebook_page.twig");
    }
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      $result = check_code($this->request->session->find("code"));
      $c['targeturl'] = $result['targeturl'];
      return $c;
    }

    protected function form_valid() {
      // Check that the code set is still valid.
      $result = check_code($this->request->session->find("code"));
      if($result['error']) {
        \Blink\Messages::error($result['message']);
        return \Blink\HttpResponseRedirect($result['url']);
      }
      
      // Check target url.
      $tresult = check_wapo($result['targeturl'], $this->form->get("facebook_id"));
      if($tresult['error']) {
        \Blink\Messages::error($tresult['message']);
        return \Blink\HttpResponseRedirect($tresult['url']);
      }
      $recipient = $tresult['recipient'];
      
      // Prepare the download.
      $download = prepare_download($recipient);
      
      return \Blink\HttpResponseRedirect($download['url']);
    }

  }
  
  class DownloadStartOverRedirectView extends \Blink\RedirectView {
    public function get_redirect_url() {
      $this->request->cookie->delete("code");
      $this->request->session->delete("code");
      $this->request->cookie->delete("email");
      $this->request->cookie->delete("confirm");
      $this->redirect_url = "/wp/download/";
    }
  }
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
//  class DownloadCookieWizardView extends \Blink\CookieWizardView {
//    private $error;
//    private $wapo;
//    
//    protected function get_step_definition_list() {
//      $definition = array(
//          "process" => array(
//              "title" => "Process",
//              "template" => TemplateConfig::Template("download/process.twig"),
//              "form" => "Wp\ProcessForm"
//          )
//      );
//      
//      if($this->error == "Wapo not found.") {
//        $definition["wnf"]= array(
//              "title" => "Wapo not found",
//              "template" => TemplateConfig::Template("download/wapo_not_found.twig"),
//              "form" => "Blink\Form"
//          );
//      } else if($this->error == "Wapo expired.") {
//        $definition["we"]= array(
//              "title" => "Wapo expired",
//              "template" => TemplateConfig::Template("download/wapo_expired.twig"),
//              "form" => "Blink\Form"
//          );
//      } else if($this->error == "Wapo expired.") {// If download not found.
//        
//      }
//      
//      
//      // Add the done (download step).
//      if(!in_array($this->error, array("Wapo not found.", "Wapo expired."))) {
//        // Check which delivery method the wapo is.
//        if($this->wapo->wapo->delivery_method_abbr == "ffa") {
//          // If they want it confirmed.
//          if($this->wapo->email_confirmation) {
//            // Send Email Code step.
//            $definition["sec"] = array(
//                "title" => "Send Code",
//                "template" => TemplateConfig::Template("download/send_email_code.twig"),
//                "form" => "Blink\Form"
//            );
//
//            // Confirm email code step.
//            $definition["cec"] = array(
//                "title" => "Receive Code",
//                "template" => TemplateConfig::Template("download/confirm_email_code.twig"),
//                "form" => "Blink\Form"
//            );
//          }
//        } else if(in_array($this->wapo->delivery_method_abbr, array("e", "el"))) {
//          // Send Email Code step.
//          $definition["sec"] = array(
//              "title" => "Send Code",
//              "template" => TemplateConfig::Template("download/send_email_code.twig"),
//              "form" => "Blink\Form"
//          );
//          
//          // Confirm email code step.
//          $definition["cec"] = array(
//              "title" => "Receive Code",
//              "template" => TemplateConfig::Template("download/confirm_email_code.twig"),
//              "form" => "Blink\Form"
//          );
//        } else if($this->wapo->delivery_method_abbr == "aff") {
//          // Check Facebook Friendship (i.e. they log in to FB).
//          $definition["aff"] = array(
//              "title" => "Send Code",
//              "template" => TemplateConfig::Template("download/any_facebook_friend.twig"),
//              "form" => "Blink\Form"
//          );
//        } else if($this->wapo->delivery_method_abbr == "fp") {
//          // Check their Facebook Page likes (i.e. they log in to FB).
//          $definition["fp"] = array(
//              "title" => "Send Code",
//              "template" => TemplateConfig::Template("download/facebook_page.twig"),
//              "form" => "Blink\Form"
//          );
//        } else if($this->wapo->delivery_method_abbr == "atf") {
//          // Check that they are following this person (i.e. log into twitter).
//          $definition["atf"] = array(
//              "title" => "Send Code",
//              "template" => TemplateConfig::Template("download/any_twitter_follower.twig"),
//              "form" => "Blink\Form"
//          );
//        } else if($this->wapo->delivery_method_abbr == "stf") {
//          // Once they log in, check that their twitter is one that this wapo is for.
//          $definition["stf"] = array(
//              "title" => "Send Code",
//              "template" => TemplateConfig::Template("download/select_twitter_follower.twig"),
//              "form" => "Blink\Form"
//          );
//        }
//        
//        // Prepare the download link here.
//        $definition["prepare"] = array(
//            "title" => "Prepare Download",
//            "template" => TemplateConfig::Template("download/prepare.twig"),
//            "form" => "Blink\Form"
//        );
//        
//        // Download/Confirmation page.
//        $definition["done"] = array(
//            "title" => "Download",
//            "template" => TemplateConfig::Template("download/done.twig"),
//            "form" => "Blink\Form"
//        );
//      }
//      
//      return $definition;
//    }
//    
//    protected function process_step() {
//      // If the process step, check the code.
//      if($this->current_step == "process") {
//        $code = $this->request->cookie->get("code");
//        
//        // Check code.
//        
//        
//      }
//      
//      return parent::process_step();
//    }
//    
//    protected function get_context_data() {
//      $c = parent::get_context_data();
//      
////      if($this->current_step == "process") {
////        if(!$this->request->get->is_set("code")) {
////          \Blink\raise404("Page not found.");
////        }
////        $c['code'] = $this->request->get->get("code");
////      }
//      
//      return $c;
//    }
//    
//    protected function post() {
//      // Pre-process the wapo before we submit.
//      $code = $this->request->post->find("code");
//      try {
//        $targeturl = \Wapo\WapoTargetUrl::queryset()->get(array("code" => $code), "Wapo not found.");
//        
//        // Check that the date has not expired.
//        $now = new \DateTime("now");
//        $wapo_date = new \DateTime($targeturl->wapo->expiring_date->format("Y-m-d H:i:s"));
//        
//        // If expiring_date < now, then Wapo has expired.
//        if($wapo_date < $now) {
//          throw new \Exception("Wapo expired.");
//        }
//        
//        $this->wapo = $targeturl->wapo;
//      } catch (\Exception $ex) {
//        $this->error = $ex->getMessage();
//      }
//
//      return parent::post();
//    }
//  }
//  
//  
//  
//  class ReceivedAWpTemplateView extends \Blink\TemplateView {
//    protected function get_template() {
//      $this->template_name = TemplateConfig::Template("/download/received_wapo.twig");
//    }
//    public function get() {
//      if($this->request->get->is_set("code")) {
//        try {
//          $promotion_recipient = WapoRecipient::queryset()->get(array("code"=>$this->request->get->get("code")));
//          
//          // Check that it is not expired.
//          $expiring = $promotion_recipient->wapo->expiring_date->format("Y-m-d");
//          if($expiring < date("Y-m-d")) {
//            \Blink\Messages::error("Wp has expired.");
//            return parent::get();
//          }
//
//          // Check that the code has not been downloaded.
//          if($promotion_recipient->downloaded) {
//            \Blink\Messages::error("Wp has already been downloaded.");
//            return parent::get();
//          }
//          
//          $promotion_recipient->open = 1;
//          $promotion_recipient->save(false);
//          
//          $this->request->cookie->reset("code", $this->request->get->get("code"));
//          $this->request->cookie->delete("confirm");
//          
//          // Redirect to confirmation page based on delivery type.
//          if($promotion_recipient->wapo->delivery_method == "email") {
//            return \Blink\HttpResponseRedirect("/wapo/download/email/");
//          } else if($promotion_recipient->wapo->delivery_method == "facebook") {
//            return \Blink\HttpResponseRedirect("/wapo/download/facebook/");
//          }
//        } catch(\Exception $e) {
//          exit($e->getMessage());
//          \Blink\Messages::error(sprintf("Wp code '%s' not found.", $this->request->get->get("code")));
//        }
//      }
//      
//      return parent::get();
//    }
//  }
//  
//  class FacebookDownloadLoginTemplateView extends \Blink\TemplateView {
//    private $promotionrecipient = null;
//    
//    protected function get_template() {
//      $this->template_name = TemplateConfig::Template("/download/facebook.twig");
//    }
//    
//    public function get_context_data() {
//      
//      $profile = Profile::queryset()->get(array("id"=>$this->promotionrecipient->wapo->profile));
//      $promotion = Promotion::queryset()->get(array("id"=>$this->promotionrecipient->wapo->promotion));
//      $product_list = Product::queryset()->filter(array("profile"=>$profile->id))->fetch();
//      $sociallinks_list = SocialLinks::queryset()->filter(array("profile"=>$profile->id))->fetch();
//      
//      $this->context['promotionrecipient'] = $this->promotionrecipient;
//      $this->context['promotion'] = $promotion;
//      $this->context['profile'] = $profile;
//      $this->context['product_list'] = $product_list;
//      $this->context['social_links'] = Helper::social_links($sociallinks_list, $profile, false);
//    }
//    
//    public function get() {
//      try {
//        $this->promotionrecipient = WapoRecipient::queryset()->get(array("code"=>$this->request->cookie->get("code")));
//      } catch(\Exception $e) {
//        \Blink\Messages::error("Code not found.");
//        return \Blink\HttpResponseRedirect("/wapo/download/");
//      }
//      
//      $this->promotionrecipient->confirmed = true;
//      $this->promotionrecipient->save(false);
//      
//      return parent::get();
//    }
//  }
//  
//  class FacebookDownloadConfirmTemplateView extends \Blink\TemplateView {
//    public function get() {
//      try {
//        $get = array(
//            "code" => $this->request->cookie->find("code"),
//            "contact" => $this->request->get->find("facebook_id"),
//        );
//        
//        $pr = WapoRecipient::queryset()->get($get);
//      } catch(\Exception $e) {
//        return \Blink\HttpResponse("error", "text/plain");
//      }
//      
//      $this->request->cookie->set("facebook_id", $this->request->get->get("facebook_id"));
//      $ps = Wapo::queryset()->get(array("id"=>$pr->wapo->id));
//      
//      $pr->confirmed = true;
//      $pr->save(false);
//      
//      return \Blink\HttpResponse("/wapo/download/download/", "text/plain");
//    }
//  }
//  
//  class WpDownloadTemplateView extends \Blink\TemplateView {
//    public function get() {
//      $get = array();
//      if($this->request->cookie->is_set("confirm")) {
//        $get = array(
//            "code" => $this->request->cookie->find("code"),
//            "confirm" => $this->request->cookie->find("confirm"),
//        );
//      } else {
//        $get = array(
//            "code" => $this->request->cookie->find("code"),
//            "contact" => $this->request->cookie->find("facebook_id"),
//        );
//      }
//      
//      try {
//        $pr = WapoRecipient::queryset()->get($get);
//      } catch(\Exception $e) {
//        \Blink\raise404("File not found.");
//      }
//      
//      $ps = Wapo::queryset()->get(array("id"=>$pr->wapo->id));
//      
//      // Set download.
//      $response = new \Blink\Response();
//      $response->file = $ps->promotion->download;
//      
//      $pr->downloaded = true;
//      $pr->save(false);
//      
//      return $response;
//    }
//  }
//  
//  class EmailCodeFormView extends \Blink\FormView {
//    protected function get_template() {
//      $this->template_name = TemplateConfig::Template("/download/email.twig");
//    }
//    
//    public function get_form() {
//      if(!$this->form) {
//        $fields = new \Blink\FormFields();
//        $field_list = array();
//        
//        $field_list[] = $fields->CharField(array("name"=>"code","max_length"=>10,"min_length"=>2,"help_text"=>"Code received."));
//        
//        if($this->request->method == "post") {
//          $this->form = new \Blink\Form($this->request->post, $field_list);
//        } else {
//          $data = array(
//                  "code" => array(
//                          "value" => $this->request->cookie->find("code")
//                  )
//          );
//          $this->form = new \Blink\Form(array(), $field_list, $data);
//        }
//      }
//    }
//    
//    public function get_success_url() {
//      $this->success_url = sprintf("/wapo/download/email/confirm/");
//    }
//    
//    public function get_post_url() {
//      $this->post_url = sprintf("%s?%s", $this->request->url, $this->request->query_string);
//    }
//    
//    public function get_cancel_url() {
//      $this->cancel_url = "/wapo/download/restart/";
//    }
//    
//    public function get_context_data() {
//      parent::get_context_data();
//      
//      $this->context['form'] = $this->form->Form($this->post_url, $this->cancel_url);
//    }
//    
//    public function form_valid() {
//      try {
//        $promotion_recipient = WapoRecipient::queryset()->get(array("code"=>$this->form->get("code")));
//        $promotion = Promotion::queryset()->get(array("id"=>$promotion_recipient->wapo->promotion));
//      } catch(\Exception $e) {
//        return $this->form_invalid();
//      }
//      
//      $this->request->cookie->reset("code", $this->form->get("code"));
//      $this->request->cookie->reset("contact", $promotion_recipient->contact);
//      
//      // Send email and ask for confirmation.
//      $mail = \Swift\Api::Message();
//      $mail->setSubject("Wp download code.");
//      $mail->setFrom(array(\Blink\Config::$EmailAccount=>"Wp.co"));
//      $mail->setTo($promotion_recipient->contact);
//      
//      $message = \Blink\render_get(array("promotion" => $promotion, "promotionrecipient" => $promotion_recipient,"url"=>\Blink\Config::$Site), TemplateConfig::Template("download/confirmation_email.twig"));
//      
//      $mail->setBody($message, "text/html");
//      $result = \Swift\Api::Send($mail);
//      
//      $promotion_recipient->open = true;
//      $promotion_recipient->save(false);
//      
//      if($result === true) {
//        \Blink\Messages::success("Confirmation sent to email.");
//      } else {
//        \Blink\Messages::error("Error sending confirmation.");
//        return parent::get();
//      }
//      
//      $this->get_success_url();
//      return \Blink\HttpResponseRedirect($this->success_url);
//    }
//  }
//  
//  class EmailConfirmFormView extends \Blink\FormView {
//    protected function get_template() {
//      $this->template_name = TemplateConfig::Template("/download/email_confirm.twig");
//    }
//    
//    public function get_form() {
//      if(!$this->form) {
//        $fields = new \Blink\FormFields();
//        $field_list = array();
//        
//        //$field_list[] = $fields->HiddenCharField(array("name"=>"email","max_length"=>100,"min_length"=>4));
//        //$field_list[] = $fields->HiddenCharField(array("name"=>"code","max_length"=>10,"min_length"=>2));
//        $field_list[] = $fields->CharField(array("name"=>"confirm","verbose_name"=>"Confirmation Code","max_length"=>10,"min_length"=>2,"help_text"=>"Enter confirmation code"));
//        
//        if($this->request->method == "post") {
//          $this->form = new \Blink\Form($this->request->post, $field_list);
//        } else {
//          $data = array(
//                  "confirm" => array(
//                          "value" => $this->request->get->find("confirm")
//                  )
//          );
//          $this->form = new \Blink\Form(array(), $field_list, $data);
//        }
//      }
//    }
//    
//    public function get_success_url() {
//      $this->success_url = "/wapo/download/email/confirmed/";
//    }
//    
//    public function get_post_url() {
//      $this->post_url = sprintf("%s?%s", $this->request->url, $this->request->query_string);
//    }
//    
//    public function get_cancel_url() {
//      $this->cancel_url = "/wapo/download/restart/";
//    }
//    
//    public function get_context_data() {
//      parent::get_context_data();
//      
//      $this->context['form'] = $this->form->Form($this->post_url, $this->cancel_url);
//    }
//    
//    public function form_valid() {
//      try {
//        $promotion_recipient = WapoRecipient::queryset()->get(array("contact"=>$this->request->cookie->find("contact"),"code"=>$this->request->cookie->find("code"),"confirm"=>$this->form->get("confirm")));
//      } catch(\Exception $e) {
//        \Blink\Messages::error("Promotion not found.");
//        return $this->form_invalid();
//      }
//      
//      $this->request->cookie->reset("confirm",$this->form->get("confirm"));
//      
//      $promotion_recipient->confirmed = true;
//      $promotion_recipient->save(false);
//      
//      $this->get_success_url();
//      return \Blink\HttpResponseRedirect($this->success_url);
//    }
//    
//    public function get() {
//      if($this->request->get->is_set("confirm")) {
//        try {
//          $pr = WapoRecipient::queryset()->get(array(
//                  "contact" => $this->request->cookie->find("contact"), 
//                  "code" => $this->request->cookie->find("code"),
//                  "confirm" => $this->request->get->get("confirm")));
//        } catch(\Exception $e) {
//          return parent::get();
//        }
//        
//        $pr->confirmed = true;
//        $pr->save(false);
//
//        $this->request->cookie->reset("confirm", $this->request->get->get("confirm"));
//        return \Blink\HttpResponseRedirect("/wapo/download/email/confirmed/");
//      }
//      
//      return parent::get();
//    }
//  }
//  
//  class EmailConfirmedTemplateView extends \Blink\TemplateView {
//    private $promotionrecipient = null;
//    
//    protected function get_template() {
//      $this->template_name = TemplateConfig::Template("/download/email_download.twig");
//    }
//    
//    public function get_context_data() {
//      parent::get_context_data();
//      
//      $profile = Profile::queryset()->get(array("id"=>$this->promotionrecipient->wapo->profile->id));
//      $promotion = Promotion::queryset()->get(array("id"=>$this->promotionrecipient->wapo->promotion->id));
//      $product_list = Product::queryset()->filter(array("profile"=>$profile->id))->fetch();
//      $sociallinks_list = SocialLinks::queryset()->filter(array("profile"=>$profile->id))->fetch();
//      
//      $this->context['promotionrecipient'] = $this->promotionrecipient;
//      $this->context['promotion'] = $promotion;
//      $this->context['profile'] = $profile;
//      $this->context['product_list'] = $product_list;
//      $this->context['social_links'] = Helper::social_links($sociallinks_list, $profile, false);
//    }
//    
//    public function get() {
//      $get = array(
//              "code" => $this->request->cookie->find("code"),
//              "confirm" => $this->request->cookie->find("confirm"),
//      );
//      
//      try {
//        $this->promotionrecipient = WapoRecipient::queryset()->depth(2)->get($get);
//      } catch(\Exception $e) {
//        return \Blink\HttpResponseRedirect("/wapo/download/email/");
//      }
//      
//      return parent::get();
//    }
//  }
//
//  
//  class TextCodeTemplateView extends \Blink\TemplateView {
//    protected function get_template() {
//      $this->template_name = TemplateConfig::Template("/download/text.twig");
//    }
//  }
//  
//  class TextConfirmFormView extends \Blink\FormView {
//    protected function get_template() {
//      $this->template_name = TemplateConfig::Template("/download/text_confirm.twig");
//    }
//  }
//  
//  class DownloadTextDownloadTemplateView extends \Blink\FormView {
//    protected function get_template() {
//      $this->template_name = TemplateConfig::Template("/download/text_download.twig");
//    }
//  }
  
  
}
