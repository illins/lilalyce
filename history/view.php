<?php

namespace Wp {
  require_once("blink/base/view/generic.php");
  require_once("blink/base/view/edit.php");
  require_once("blink/base/view/detail.php");
  require_once("blink/base/view/list.php");
  require_once("blink/base/view/wizard.php");
  
  require_once("apps/wp/config.php");
  require_once("apps/wapo/model.php");
  require_once("apps/wp/form.php");

  require_once("apps/blink-user/api.php");
  
  /**
   * - View one's history of Wapo(s) downloaded. 
   *    - Email: Enter email and send a confirmation if they have ever been sent a Wapo.
   *      - View history using special url that expires.
   *    - Facebook: Login through Facebook.
   *    - Twitter: Login through Twitter.
   */
  
  class WapoHistoryView extends \Blink\TemplateView {
    
  }
  
  /**
   * - Send code for them to confirm their email.
   */
  class WapoHistoryEmailView extends \Blink\FormView {
    
  }
  
  /**
   * - If they confirm email, they can view history.
   */
  class WapoHistoryConfirmEmailView extends \Blink\TemplateView {
    
  }
  
  /**
   * - If they have already logged in.
   */
  class WapoHistoryTwitterView extends \Blink\TemplateView {
    
  }
  
  /**
   * - Display the Facebook page for them to log in.
   */
  class WapoHistoryFacebookView extends \Blink\TemplateView {
    
  }
  
  /**
   * - Return a json list of Wapo(s).
   */
  class WapoHistoryFacebookWapoView extends \Blink\TemplateView {
    
  }
  
  
  
  
}