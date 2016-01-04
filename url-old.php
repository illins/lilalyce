<?php

namespace Wp {
  require_once("apps/wp/delivery-method.php");
  require_once("apps/wp/checkout.php");
  
  // WP pipeline views.
  require_once("apps/wp/view.php");
  require_once 'apps/wp/views/wp/wizard.php';
  require_once 'apps/wp/views/wp/progress.php';
  
  // WP download.
  require_once("apps/wp/download/url.php");
  
  // API access urls.
  require_once("apps/wp/views/mailchimp.php");
  require_once("apps/wp/scalablepress/url.php");
  require_once("apps/wp/ifeelgoods/url.php");
  require_once("apps/wp/tangocard/url.php");
  
  $wp_url_patterns = array(
      array(
          "uri" => "/test/$",
          "view" => TestView::as_view(),
          "name" => "TangoJSONView",
          "title" => "Tango View"
      ),
      array(
          "uri" => '/scalablepress',
          "url_patterns" => \Blink\include_url_patterns($wp_scalable_url_patterns)
      ),
      array(
          "uri" => '/ifeelgoods',
          "url_patterns" => \Blink\include_url_patterns($wp_ifeelgoods_url_patterns)
      ),
      array(
          "uri" => '/tangocard',
          "url_patterns" => \Blink\include_url_patterns($wp_tangocard_url_patterns)
      ),
      array(
          "uri" => '/download',
          "url_patterns" => \Blink\include_url_patterns($wp_download_url_patterns)
      ),
      array(
          "uri" => "/order/$",
          "view" => TangoJSONView::as_view(),
          "name" => "TangoJSONView",
          "title" => "Tango View"
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
      
//      array(
//          "uri" => "/mailchimp/lists/members/oye/$",
//          "view" => MailChimpMembersView::as_view(),
//          "name" => "MailChimpListMembersView",
//          "title" => "MailChimp List Members"
//      ),
      
      array(
          "uri" => "/progress/module/(?P<pk>\d+)/$",
          "view" => ModuleJSONDetailView::as_view(),
          "name" => "ModuleJSONDetailView",
          "title" => "Module JSON Detail View"
      ),
      array(
          "uri" => "/progress/profile/(?P<pk>\d+)/$",
          "view" => ProfileJSONDetailView::as_view(),
          "name" => "ProfileJSONDetailView",
          "title" => "Profile JSON Detail View"
      ),
      array(
          "uri" => "/testandor/$",
          "view" => TestJSONView::as_view(),
          "name" => "TestJSONView",
          "title" => "Test AND/OR"
      ),
      
      array(
          "uri" => "/sidebar/$",
          "view" => SideBarTemplateView::as_view(),
          "name" => "DashboardTemplateView",
          "title" => "DashboardTemplateView"
      ),
      array(
          "uri" => "/createwapo/$",
          "view" => CreateWapoJSONView::as_view(),
          "name" => "CreateWapoJSONView",
          "title" => "Create Wapo"
      ),
      array(
          "uri" => "/sendwapo/$",
          "view" => SendWapoJSONView::as_view(),
          "name" => "SendWapoJSONView",
          "title" => "Send Wapo"
      ),
      array(
          "uri" => "/pay/$",
          "view" => FakeCheckoutView::as_view(),
          "name" => "FakeCheckoutView",
          "title" => "FakeCheckoutView"
      ),
      array(
          "uri" => "/startover/$",
          "view" => StartOverRedirectView::as_view(),
          "name" => "DashboardTemplateView",
          "title" => "DashboardTemplateView"
      ),
      array(
          "uri" => "/twitter/followers/$",
          "view" => TwitterFollowersView::as_view(),
          "name" => "TwitterFollowersView",
          "title" => "TwitterFollowersView"
      ),
      array(
          "uri" => "/instagram/followers/$",
          "view" => InstagramFollowersView::as_view(),
          "name" => "InstagramFollowersView",
          "title" => "InstagramFollowersView"
      ),
      array(
          "uri" => "/facebook/resource/$",
          "view" => FacebookUpdateResourceView::as_view(),
          "name" => "FacebookUpdateResourceView",
          "title" => "FacebookUpdateResourceView"
      ),
      
//      // Pipeline step wizard.
//      array(
//          "uri" => "/$",
//          "view" => WpCookieWizardView::as_view(),
//          "name" => "DashboardTemplateView",
//          "title" => "DashboardTemplateView"
//      ),
//      array(
//          "uri" => "/(?P<step>[\w-]+)/$",
//          "view" => WpCookieWizardView::as_view(),
//          "name" => "DashboardTemplateView",
//          "title" => "DashboardTemplateView"
//      ),
  );
  
  
  require_once 'apps/wp/views/wapo/wapo.php';
  require_once 'apps/wp/views/wapo/module.php';
  require_once 'apps/wp/views/wapo/profile.php';
  require_once 'apps/wp/views/wapo/tangocards.php';
  
  WpBaseView::register_url(array("pattern"=>"/wp/wapo/"));  
  
  // API FUNCTIONS
  
  // Wapo cookie update/get view.
  WpWapoFormView::register_url(array("pattern"=>"/wp/wapo/data/"));
  WpSetModuleFormView::register_url(array("pattern"=>"/wp/wapo/set/module/"));
  WpSetProfileFormView::register_url(array("pattern"=>"/wp/wapo/set/profile/"));
  WpSetNewProfileFormView::register_url(array("pattern"=>"/wp/wapo/set/profile/new/"));
  WpSetTangoCardsFormView::register_url(array("pattern"=>"/wp/wapo/set/tangocards/"));
  
  WpSetFreeForAllDeliveryFormView::register_url(array("pattern"=>"/wp/wapo/set/delivery/free-for-all/"));
  WpSetEmailDeliveryFormView::register_url(array("pattern"=>"/wp/wapo/set/delivery/email/"));
  WpSetEmailListDeliveryFormView::register_url(array("pattern"=>"/wp/wapo/set/delivery/email-list/"));
  WpSetMailChimpDeliveryFormView::register_url(array("pattern"=>"/wp/wapo/set/delivery/mailchimp/"));
  WpSetAnyTwitterFollowersDeliveryFormView::register_url(array("pattern"=>"/wp/wapo/set/delivery/any-twitter-followers/"));
  WpSetSelectTwitterFollowersDeliveryFormView::register_url(array("pattern"=>"/wp/wapo/set/delivery/select-twitter-followers/"));
  WpSetAnyFacebookFriendsDeliveryFormView::register_url(array("pattern"=>"/wp/wapo/set/delivery/any-facebook-friends/"));
  WpSetFacebookPageDeliveryFormView::register_url(array("pattern"=>"/wp/wapo/set/delivery/facebook-page/"));
  
  // Modules view.
  WpModuleListView::register_url(array("pattern"=>"/wp/wapo/module/"));
  
  // Profile list view.
  WpProfileListView::register_url(array("pattern"=>"/wp/wapo/profile/"));
  
  // Tangocards view.
  WpTangoCardRewardsListView::register_url(array("pattern"=>"/wp/wapo/tangocards/"));
  
  // Validate.
  WpValidateFormView::register_url(array("pattern"=>"/wp/wapo/validate/"));
  

}