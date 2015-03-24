<?php

namespace Wp {
  require_once("apps/wp/delivery-method.php");
  require_once("apps/wp/checkout.php");
  
  // WP pipeline views.
  require_once("apps/wp/view.php");
  require_once 'apps/wp/views/wp/wizard.php';
  require_once 'apps/wp/views/wp/progress.php';
  
  // WP download.
  require_once("apps/wp/download/view.php");
  
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
          "url_patterns" => \Blink\include_url_patters($wp_scalable_url_patterns)
      ),
      array(
          "uri" => '/ifeelgoods',
          "url_patterns" => \Blink\include_url_patters($wp_ifeelgoods_url_patterns)
      ),
      array(
          "uri" => '/tangocard',
          "url_patterns" => \Blink\include_url_patters($wp_tangocard_url_patterns)
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
      array(
          "uri" => "/download/$",
          "view" => CheckWapoView::as_view(),
          "name" => "CheckWapoView",
          "title" => "Check Wapo"
      ),
      array(
          "uri" => "/download/email/$",
          "view" => WapoEmailView::as_view(),
          "name" => "WapoEmailView",
          "title" => "Wapo Email"
      ),
      array(
          "uri" => "/download/email/senderror/$",
          "view" => EmailSendErrorView::as_view(),
          "name" => "EmailSendErrorView",
          "title" => "Wapo Email"
      ),
      array(
          "uri" => "/download/email/confirm/$",
          "view" => WapoConfirmEmailCodeUrlView::as_view(),
          "name" => "WapoConfirmEmailCodeUrlView",
          "title" => "Wapo Confirm Url Email"
      ),
      array(
          "uri" => "/download/email/code/confirm/$",
          "view" => WapoConfirmEmailCodeView::as_view(),
          "name" => "WapoConfirmEmailCodeView",
          "title" => "Wapo Confirm Email"
      ),
      array(
          "uri" => "/download/ffa/$",
          "view" => WapoFreeForAllEmailView::as_view(),
          "name" => "WapoFreeForAllEmailView",
          "title" => "Wapo Confirm Email"
      ),
      array(
          "uri" => "/download/ffa/confirm/$",
          "view" => WapoFreeForAllConfirmEmailCodeUrlView::as_view(),
          "name" => "WapoFreeForAllConfirmEmailCodeUrlView",
          "title" => "Wapo Confirm Url Email"
      ),
      array(
          "uri" => "/download/ffa/code/confirm/$",
          "view" => WapoFreeForAllConfirmEmailCodeView::as_view(),
          "name" => "WapoFreeForAllConfirmEmailCodeView",
          "title" => "Wapo Confirm Email Code"
      ),
      array(
          "uri" => "/download/twitter/$",
          "view" => TwitterUserCanDownloadWapoView::as_view(),
          "name" => "WapoConfirmEmailCodeView",
          "title" => "Wapo Confirm Email"
      ),
      array(
          "uri" => "/download/twitter/nofollow/$",
          "view" => TwitterNoFollowView::as_view(),
          "name" => "TwitterNoFollowView",
          "title" => "Twitter Follow Error"
      ),
      array(
          "uri" => "/download/expired/$",
          "view" => WapoExpiredView::as_view(),
          "name" => "WapoExpiredView",
          "title" => "Wapo Expired"
      ),
      array(
          "uri" => "/download/aff/$",
          "view" => WapoAnyFacebookFriendsView::as_view(),
          "name" => "WapoAnyFacebookFriendsView",
          "title" => "Facebook Friend"
      ),
      array(
          "uri" => "/download/fp/$",
          "view" => WapoFacebookPageView::as_view(),
          "name" => "WapoFacebookPageView",
          "title" => "Facebook Page"
      ),
      array(
          "uri" => "/download/download/$",
          "view" => WapoDownloadView::as_view(),
          "name" => "WapoDownloadView",
          "title" => "Download"
      ),
      
      array(
          "uri" => "/$",
          "view" => WpCookieWizardView::as_view(),
          "name" => "DashboardTemplateView",
          "title" => "DashboardTemplateView"
      ),
      array(
          "uri" => "/(?P<step>[\w-]+)/$",
          "view" => WpCookieWizardView::as_view(),
          "name" => "DashboardTemplateView",
          "title" => "DashboardTemplateView"
      ),
  );
}