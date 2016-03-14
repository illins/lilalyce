<?php
namespace Wp {
  require_once("apps/wp/config.php");
  
  class Helper {
    public static function social_links($social_links_list, $profile, $edit = false) {
      for($i = 0; $i < count($social_links_list); $i++) {
        if(strpos($social_links_list[$i]->link, "twitter") !== false) {
          $social_links_list[$i]->class = "fa fa-twitter fa-2x";
          $social_links_list[$i]->title = "Twitter";
        } else if(strpos($social_links_list[$i]->link, "facebook") !== false) {
          $social_links_list[$i]->class = "fa fa-facebook fa-2x";
          $social_links_list[$i]->title = "Facebook";
        } else if(strpos($social_links_list[$i]->link, "pintrest") !== false) {
          $social_links_list[$i]->class = "fa fa-pintrest fa-2x";
          $social_links_list[$i]->title = "Pintrest";
        } else if(strpos($social_links_list[$i]->link, "tumblr") !== false) {
          $social_links_list[$i]->class = "fa fa-tumblr fa-2x";
          $social_links_list[$i]->title = "Tumblr";
        } else if(strpos($social_links_list[$i]->link, "flickr") !== false) {
          $social_links_list[$i]->class = "fa fa-flickr fa-2x";
          $social_links_list[$i]->title = "Flickr";
        } else if(strpos($social_links_list[$i]->link, "instagram") !== false) {
          $social_links_list[$i]->class = "fa fa-instagram fa-2x";
          $social_links_list[$i]->title = "Instagram";
        } else if(strpos($social_links_list[$i]->link, "youtube") !== false) {
          $social_links_list[$i]->class = "fa fa-youtube fa-2x";
          $social_links_list[$i]->title = "YouTube";
        } else if(strpos($social_links_list[$i]->link, "vimeo") !== false) {
          $social_links_list[$i]->class = "fa fa-vimeo-square fa-2x";
          $social_links_list[$i]->title = "vimeo";
        } else {
          $social_links_list[$i]->class = "fa fa-square fa-2x";
          $social_links_list[$i]->title = "unknown";
        }
      }
      
      $context = array(
              "profile" => $profile,
              "sociallinks_list" => $social_links_list
      );
      
      if($edit) {
        return \Blink\render_get($context, TemplateConfig::$Directory . "dashboard/sociallinks.twig");
      } else {
        return \Blink\render_get($context, TemplateConfig::$Directory . "sociallinks.twig");
      }
    }
    
    public static function profile_progress($profile, $request, $current) {
      $progress = array(1, 0, 0, 0, 0, 0);
      
      // If start campaign.
      if($current == 1) {
        if($request->cookie->is_set("delivery_method")) {
          $progress[2] = 1;
        }
        if($request->cookie->is_set("delivery_message")) {
          $progress[3] = 1;
          $progress[4] = 1;
        }
      } else if($current == 2) {/* If delivery method. */
        $progress = array(1, 1, 0, 0, 0, 0);
        if($request->cookie->is_set("delivery_message")) {
          $progress[3] = 1;
          $progress[4] = 1;
        }
      } else if($current == 3) {/* If delivery message. */
        $progress = array(1, 1, 1, 0, 0, 0);
        if($request->cookie->is_set("delivery_message")) {
          $progress[4] = 1;
        }
      } else if($current == 4) {/* If checkout. */
        $progress = array(1, 1, 1, 1, 0, 0);
      } else if($current == 4) {/* If confirmation. */
        $progress = array(1, 1, 0, 0, 0, 0);
      }
      
      $context = array(
              "profile"=>$profile,
              "current"=>$current,
              "progress"=>$progress
      );
      return \Blink\render_get($context, TemplateConfig::Template("dashboard/progress.twig"));
    }
    
    public static function frontend_progress($request, $current) {
      $progress = array(1, 0, 0, 0, 0, 0);
      
      // If start campaign.
      if($current == 1) {
        if($request->cookie->is_set("delivery_method")) {
          $progress[2] = 1;
        }
        if($request->cookie->is_set("delivery_message")) {
          $progress[3] = 1;
          $progress[4] = 1;
        }
      } else if($current == 2) {/* If delivery method. */
        $progress = array(1, 1, 0, 0, 0, 0);
        if($request->cookie->is_set("delivery_message")) {
          $progress[3] = 1;
          $progress[4] = 1;
        }
      } else if($current == 3) {/* If delivery message. */
        $progress = array(1, 1, 1, 0, 0, 0);
        if($request->cookie->is_set("delivery_message")) {
          $progress[4] = 1;
        }
      } else if($current == 4) {/* If checkout. */
        $progress = array(1, 1, 1, 1, 0, 0);
      } else if($current == 4) {/* If confirmation. */
        $progress = array(1, 1, 0, 0, 0, 0);
      }
      
      $context = array(
              "current"=>$current,
              "progress"=>$progress
      );
      return \Blink\render_get($context, TemplateConfig::Template("frontend/progress.twig"));
    }
    
    public static function clear_cookies($cookie) {
      $cookie->delete("promotion_id");
      $cookie->delete("delivery_date");
      $cookie->delete("delivery_message");
      $cookie->delete("delivery_method");
      
      for($i = 0; $i <= Config::$NotLoggedInMaxEmailCount; $i++) {
        $cookie->delete("email_email$i");
        $cookie->delete("email_name$i");
      }
      
      $cookie->delete("expiring_date");
      $cookie->delete("facebook_ids");
      $cookie->delete("facebook_id");
      $cookie->delete("profile");
      $cookie->delete("name");
      $cookie->delete("email");
    }
    
    /**
     * Check that the delivery method is filled in correctly.
     * @param \Blink\Request $request
     * @return boolean
     */
    public static function promotion_check_delivery(\Blink\Request $request) {
      $delivery_method = $request->cookie->find("delivery_method");
      
      if(!$request->cookie->is_set("delivery_method")) {
        return false;
      }
      
      if($delivery_method == "email") {
        $count = 0;
        for($i = 1; $i <= Config::$NotLoggedInMaxEmailCount; $i++) {
          if($request->cookie->is_set("email_email$i")) {
            $count++;
          }
        }
        if(!$count) {
          return false;
        }
      } else if($delivery_method == "facebook") {
        if(!$request->cookie->is_set("facebook_ids")) {
          return false;
        }
      }
      
      return true;
    }
    
    /**
     * 
     * @param \Blink\Request $request
     * @param string $stage Stage that the user is in (filling out). The check validates data from the previous stage.
     * @param \Wp\Profile $profile Profile of the user if they are logged in.
     */
    public static function check_promotion($request, $stage = "promotion", $profile_id = null) {
      $base_url = "/wapo/";
      if($profile_id) {
        $base_url = sprintf("/wapo/dashboard/profile/%s/", $profile_id);
      }
      
      $result = array(
          "error" => False,
          "message" => "",
          "url" => ""
      );
      
      if($stage == "profile") {
        return $result;
      }
      
      if($profile_id) {
        if (!$request->cookie->is_set(("profile_id"))) {
          return array(
              "error" => True,
              "message" => "Please select a profile.",
              "url" => $base_url . "profile/"
          );
        }
      }

      // Nothing to check here.
      if ($stage == "promotion") {
        return $result;
      }
      
      // Check that the promotion is set.
      if (!$request->cookie->is_set("promotion_id")) {
        return array(
            "error" => True,
            "message" => "Please select a promotion.",
            "url" => $base_url."marketplace/"
        );
      }

      // If we are at 'delivery_method', everything above checks, so return.
      if ($stage == "delivery-method") {
        return $result;
      }

      // Check the delivery information.
      $delivery_method = $request->cookie->find("delivery_method");
      if (!$delivery_method) {
        return array(
            "error" => True,
            "message" => "Please fill out delivery information.",
            "url" => $base_url."delivery-method/"
        );
      }
      
      // Check that the email is set based on where we are going.
      if($delivery_method == "email") {
        $count = 0;
        if($profile_id) {
          for ($i = 1; $i <= Config::$LoggedInMaxEmailCount; $i++) {
            if ($request->cookie->is_set("email_email$i")) {
              $count++;
            }
          }
        } else {
          $count = 0;
          for ($i = 1; $i <= Config::$NotLoggedInMaxEmailCount; $i++) {
            if ($request->cookie->is_set("email_email$i")) {
              $count++;
            }
          }
        }
        
        if (!$count) {
          return array(
              "error" => True,
              "message" => "Please enter an email address to send to.",
              "url" => $base_url."delivery-method/email/"
          );
        }
      } else if($delivery_method == "facebook") {
        $facebook_ids = $request->cookie->find("facebook_ids");
        if(!$facebook_ids) {
          return array(
              "error" => True,
              "message" => "Please select valid users.",
              "url" => $base_url."delivery-method/facebook/"
          );
        }
      }
      
      // If we are here, then everything else checked.
      if($stage == "profile") {
        return $result;
      }

      // Check that the expiring date is set.
      if (!$request->cookie->find("expiring_date")) {
        return array(
            "error" => True,
            "message" => "Please fill out promotion expiring date.",
            "url" => $base_url . "profile/"
        );
      }
    }
    
    /**
     * 
     * @param \Wapo\WapoRecipient $recipient
     * @return string
     */
    public static function DigitalDownloadHash($recipient) {
      $extra = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ" . uniqid();
      return hash('ripemd160', str_shuffle($recipient->id . $recipient->contact . $extra));
    }
    
    public static function CleanNumber($number) {
      return str_replace(array(" ", "-", ")", "("), "", $number);
    }
  }
}