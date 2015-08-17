<?php
/**
   * Configuration file for global settings for Wapo Pipeline (wp) app.
   */

namespace Wp {
  
  require_once("blink/base/config.php");

  /**
   * Template Configuration sets the path for templates.
   */
  class TemplateConfig extends \Blink\BaseTemplateConfig {
    const TEMPLATE_PATH = "apps/wp/templates/";
  }
  
  class WpTemplateConfig extends TemplateConfig {
    
  }
  
  /**
   * General config stores information for general items.
   * @todoc Change references to '$LoggedInMaxEmailDeliveryCount' & '$NotLoggedInMaxEmailDeliveryCount' to their 'const' counterparts. 
   */
  class Config extends \Blink\BaseConfig {

    // How many emails a logged in user can manually enter in to send a wapo to.
    public static $LoggedInMaxEmailDeliveryCount = 3;
    const MAX_EMAIL_DELIVERY_COUNT_USER = 3;
    
    // How many emails a guest user can send a wapo to.
    public static $NotLoggedInMaxEmailDeliveryCount = 1;
    const MAX_EMAIL_DELIVERY_COUNT_GUEST = 1;
    
    /**
     * - List of delivery methods available. 
     * @var type 
     */
    public static $DeliveryMethod = array(
        "ffa"=>"Free For All",
        "fp"=>"Facebook Page",
        "aff"=>"Any Facebook Friends",
        "e"=>"Email",
        "el"=>"Email List",
        "stf"=>"Select Twitter Followers",
        "atf"=>"Any Twitter Followers",
        "mailchimp"=>"MailChimp Email",
        "text" => "Text Message",
        "addr" => "Address"
    );
    
    
    // Maximum number of emails that can be sent in the 'el' delivery method.
    const MAX_EL_EMAIL_COUNT = 2;
    
    const MAX_TEXT_PHONE_NUMBER_COUNT = 2;
    
    // Maximum number of times a 'digital promotion' can be downloaded.
    const PROMOTION_MAX_DOWNLOADS = 2;
    
    // Rate to charge customer for sending using the 'text' method.
    const TEXT_RATE = 0.5;
  }
  
  class WpConfig extends Config {
    
  }

}