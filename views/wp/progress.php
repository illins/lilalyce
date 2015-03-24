<?php

/**
 * Helper views for the progress bar.
 */
namespace Wp {
  require_once("blink/base/view/json.php");

  require_once("apps/wp/config.php");
  require_once("apps/wapo/model.php");

  require_once("apps/blink-user/api.php");
  
  require_once("apps/blink-user-role/api.php");

  require_once("apps/swiftmailer/api.php");
  
  require_once("apps/blink-twilio/api.php");
  
  require_once("apps/blink-tangocard/tangocard/tangocard.php");
  
  require_once 'apps/blink-bitly/bitly/bitly.php';

  /**
   * Get a module.
   */
  class ModuleJSONDetailView extends \Blink\JSONDetailView {
    protected $class = "\Wapo\Module";
  }
  
  /**
   * Get a profile that belongs to the user.
   */
  class ProfileJSONDetailView extends \Blink\JSONDetailView {
    protected $class = "\Wapo\Profile";
    
    protected function get_object() {
      parent::get_queryset();
      
      return \Wapo\Profile::get_or_404(array("id"=>$this->request->param->param['pk'],"wapo_distributor.user"=>$this->request->user), "Profile not found.");
    }
  }
  
  class TestJSONView extends \Blink\JSONView {
    protected function get_context_data() {
      $c = parent::get_context_data();
      
      $c['user_list'] = \User\User::queryset()->filter(\Blink\O(array("user_account.id"=>2),array("first_name"=>"Andrew")))->fetch();
      
      return $c;
    }
  }
  
}
