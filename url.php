<?php

namespace Wp {
  require_once("apps/wp/delivery-method.php");
  require_once("apps/wp/checkout.php");
  require_once("apps/wp/view.php");
  require_once("apps/wp/download/view.php");
  
  require_once("apps/wp/views/mailchimp.php");
  
  require_once("apps/wp/scalablepress/url.php");

  $wp_url_patterns = array(
      array(
          "uri" => '/scalablepress',
          "url_patterns" => \Blink\include_url_patters($wp_scalable_url_patterns)
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
          "uri" => "/sidebar/$",
          "view" => SideBarTemplateView::as_view(),
          "name" => "DashboardTemplateView",
          "title" => "DashboardTemplateView"
      ),
      array(
          "uri" => "/createwapo/$",
          "view" => CreateWapoView::as_view(),
          "name" => "CreateWapoView",
          "title" => "Create"
      ),
      array(
          "uri" => "/sendwapo/$",
          "view" => SendWapoView::as_view(),
          "name" => "SendWapoView",
          "title" => "Send"
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
          "uri" => "/(?P<step>\w+)/$",
          "view" => WpCookieWizardView::as_view(),
          "name" => "DashboardTemplateView",
          "title" => "DashboardTemplateView"
      ),
  );
}