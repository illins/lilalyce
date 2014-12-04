<?php

namespace Wp {
  require_once("blink/base/view/edit.php");
  require_once("blink/base/view/generic.php");
  require_once("blink/base/view/list.php");
  require_once("blink/base/view/detail.php");
  require_once("blink/base/view/edit.php");

  require_once('apps/blink-mailchimp/api.php');
  
  class MailChimpListsView extends \Blink\TemplateView {
    protected function get_content_type() {
      return \Blink\View::CONTENT_JSON;
    }
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      
      // Get the list of email lists.
      $result = \BlinkMailChimp\Api::endpoint("lists/list");
      $c['data'] = $result['data'];
      
      return $c;
    }
  }
  
  class MailChimpListMembersView extends \Blink\TemplateView {
    protected function get_content_type() {
      return \Blink\View::CONTENT_JSON;
    }
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      
      // Get the list of emails that have status subscribed.
      $result = \BlinkMailChimp\Api::endpoint("lists/members", array("id"=>$id = $this->request->get->find("id", null),"status"=>"subscribed"));
      $c['data'] = $result['data'];
      
      return $c;
    }
  }
  
//  class MailChimpMembersView extends \Blink\TemplateView {
//    protected function get_content_type() {
//      return \Blink\View::CONTENT_JSON;
//    }
//    
//    protected function get_context_data() {
//      $c = parent::get_context_data();
//      
//      $emails = array(
//          array(
//              "email" => "livedev1@yahoo.com"
//          ),
//          array(
//              "email" => "condev1@outlook.com"
//          )
//      );
//      
//      // Get the list of emails that have status subscribed.
//      $result = \BlinkMailChimp\Api::endpoint("lists/member-info", array("id"=>$id = '451da43dc2',"emails"=>$emails));
//      $c['data'] = $result['data'];
//      
//      return $c;
//    }
//  }


}