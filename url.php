<?php

namespace Wp {
  // WP download.
  require_once("apps/wp/download/url.php");
  
  // Mailchimp views.
  require_once("apps/wp/views/mailchimp.php");

  $wp_url_patterns = array(
      array(
          "uri" => '/download',
          "url_patterns" => \Blink\include_url_patterns($wp_download_url_patterns)
      ),
      
      /* Mailchimp API */
      array(
          "uri" => "/mailchimp/lists/$",
          "view" => MailChimpListsView::as_view(),
          "name" => "MailChimpListsView",
          "title" => "MailChimp Lists"
      ),
      array(
          "uri" => "/mailchimp/lists/members/$",
          "view" => MailChimpListMembersView::as_view(),
          "name" => "MailChimpListMembersView",
          "title" => "MailChimp List Members"
      ),
  );

  require_once 'apps/wp/views/wapo/base.php';
  require_once 'apps/wp/views/wapo/wapo.php';
  require_once 'apps/wp/views/wapo/module.php';
  require_once 'apps/wp/views/wapo/profile.php';
  
  require_once 'apps/wp/views/wapo/tangocards.php';
  require_once 'apps/wp/views/wapo/promotions.php';
  
  require_once 'apps/wp/views/wapo/confirmation.php';
  
  // Download urls.
  require_once 'apps/wp/views/wapo-download/wapo-download-url.php';

  // Base template
  WpBaseView::register_url(array("pattern" => "/wp/wapo/"));
  
  // Reset.
  WpStartOverFormView::register_url(array("pattern"=>"/wp/wapo/start-over/"));

  // API FUNCTIONS
  // Set data for the API.
  WpWapoFormView::register_url(array("pattern" => "/wp/wapo/data/"));
  WpSetModuleFormView::register_url(array("pattern" => "/wp/wapo/set/module/"));
  WpSetProfileFormView::register_url(array("pattern" => "/wp/wapo/set/profile/"));
  WpClearProfileFormView::register_url(array("pattern" => "/wp/wapo/clear/profile/"));
  WpSetNewProfileFormView::register_url(array("pattern" => "/wp/wapo/set/profile/new/"));
  WpSetTangoCardsFormView::register_url(array("pattern" => "/wp/wapo/set/tangocards/"));
  WpSetPromotionFormView::register_url(array("pattern" => "/wp/wapo/set/promotion/"));

  WpSetFreeForAllDeliveryFormView::register_url(array("pattern" => "/wp/wapo/set/delivery/free-for-all/"));
  WpSetEmailDeliveryFormView::register_url(array("pattern" => "/wp/wapo/set/delivery/email/"));
  WpSetEmailListDeliveryFormView::register_url(array("pattern" => "/wp/wapo/set/delivery/email-list/"));
  WpSetMailChimpDeliveryFormView::register_url(array("pattern" => "/wp/wapo/set/delivery/mailchimp/"));
  WpSetTextDeliveryFormView::register_url(array("pattern" => "/wp/wapo/set/delivery/text/"));
  WpSetAnyTwitterFollowersDeliveryFormView::register_url(array("pattern" => "/wp/wapo/set/delivery/any-twitter-followers/"));
  WpSetSelectTwitterFollowersDeliveryFormView::register_url(array("pattern" => "/wp/wapo/set/delivery/select-twitter-followers/"));
  WpSetAnyFacebookFriendsDeliveryFormView::register_url(array("pattern" => "/wp/wapo/set/delivery/any-facebook-friends/"));
  WpSetFacebookPageDeliveryFormView::register_url(array("pattern" => "/wp/wapo/set/delivery/facebook-page/"));

  // Modules view.
  WpModuleListView::register_url(array("pattern" => "/wp/wapo/module/"));

  // Profile list view.
  WpProfileListView::register_url(array("pattern" => "/wp/wapo/profile/"));

  // Tangocards view.
  WpTangoCardRewardsListView::register_url(array("pattern" => "/wp/wapo/tangocards/"));
  WpPromotionCategoryListView::register_url(array("pattern" => "/wp/wapo/promotioncategories/"));
  WpPromotionListView::register_url(array("pattern" => "/wp/wapo/promotions/"));

  // Validate.
  WpValidateFormView::register_url(array("pattern" => "/wp/wapo/validate/"));
  
  // Create a checkout.
  WpCheckoutCreateFormView::register_url(array("pattern" => "/wp/wapo/checkout/create/"));

  // Post checkout routes.
  WpFreeFormView::register_url(array("pattern"=>"/wp/wapo/free/"));
  WpPaymentFormView::register_url(array("pattern"=>"/wp/wapo/payment/"));
  WpCreateFormView::register_url(array("pattern"=>"/wp/wapo/create/"));
  WpSendFormView::register_url(array("pattern" => "/wp/wapo/send/"));
  
  // Confirmation page.
  WpWapoConfirmationDetailView::register_url(array("pattern"=>"/wp/wapo/confirmation/"));
  
  
//  class RequestTestView extends \Blink\JSONView {
//    protected function get_context_data() {
//      $c = parent::get_context_data();
//      
////      $this->request->cookie->set("robin", "peach");
//      $c['eli'] = $this->request->cookie->find("robin");
////      $c['wapo'] = $this->request->session->find("wapo", array());
//      
////      \Blink\blink_log($_SESSION);
//      
////      $c['the-name-is-set'] = $this->request->get->prefix("the-")->is_set("name");
////      $c['name-is-set'] = $this->request->get->is_set("name");
////      
////      $c['name'] = $this->request->get->find("name");
////      $c['names'] = $this->request->get->find("names", array(), false, true, ",");
////      
////      $c['the-name'] = $this->request->get->prefix("the-")->find("name", null);
////      
////      $c['arr'] = $this->request->get->find("arr", array());
////      $c['arr-int'] = $this->request->get->float("arr", array());
//      
//      return $c;
//    }
//  }
//  
//  RequestTestView::register_url(array("pattern"=>"/request/"));
}