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

  use Wapo\PromotionCategory;
  use Wapo\Promotion;
  use Wapo\Distributor;
  use Wapo\Profile;
  use Wapo\PromotionSend;
  use Wapo\PromotionRecipient;
  use Wapo\Helper;

  /**
   * - Get a twitter user's list of followers.
   */
  class GetTwitterFollowersView extends \Blink\TemplateView {
    protected function get_context_data() {
      $context = parent::get_context_data();
      
      $connection = new \TwitterOAuth(\Blink\Config::$TwitterConsumerKey, \Blink\Config::$TwitterConsumerSecret, $this->request->session->prefix("twitter-")->get('oauth_token'), $this->request->session->prefix("twitter-")->get('oauth_token_secret'));
      $follower_list = $connection->get('followers/list', array("page"=>$this->request->get->find("page", 1)));
      
      return $context;
    }
  }
  
  /**
   * - Post a wapo to twitter.
   */
  class PostToTwitterView extends \Blink\TemplateView {
    protected function get_context_data() {
      $context = parent::get_context_data();
      
      $connection = new \TwitterOAuth(\Blink\Config::$TwitterConsumerKey, \Blink\Config::$TwitterConsumerSecret, $this->request->session->prefix("twitter-")->get('oauth_token'), $this->request->session->prefix("twitter-")->get('oauth_token_secret'));
      $status = $connection->post('statuses/update', array('status' => 'Test message to twitter.'));
      
      return $context;
    }
  }
  
  /**
   * - Search twitter for a user's followers.
   */
  class SearchTwitterFollowersView extends \Blink\TemplateView {
    protected function get_context_data() {
      $context = parent::get_context_data();
      
      
      
      return $context;
    }
  }
  
  class GetTwitterFollowers3 extends \Blink\TemplateView {
    protected function get_context_data() {
      $context = parent::get_context_data();
      
      
      
      return $context;
    }
  }
  
  class GetTwitterFollowers4 extends \Blink\TemplateView {
    protected function get_context_data() {
      $context = parent::get_context_data();
      
      
      
      return $context;
    }
  }


}
