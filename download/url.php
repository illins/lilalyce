<?php

namespace Wp {
  require_once("apps/wp/download/view.php");

  $wp_download_url_patterns = array(
//      array(
//          "uri" => "/sidebar/$",
//          "view" => SideBarTemplateView::as_view(),
//          "name" => "DashboardTemplateView",
//          "title" => "DashboardTemplateView"
//      ),
      array(
          "uri" => "/preview/$",
          "view" => PreviewDownloadTemplateView::as_view(),
          "name" => "PreviewDownloadTemplateView",
          "title" => "Preview"
      ),
//      array(
//          "uri" => "/createwapo/$",
//          "view" => CreateWapoView::as_view(),
//          "name" => "CreateWapoView",
//          "title" => "Create"
//      ),
//      array(
//          "uri" => "/sendwapo/$",
//          "view" => SendWapoView::as_view(),
//          "name" => "SendWapoView",
//          "title" => "Send"
//      ),
//      array(
//          "uri" => "/pay/$",
//          "view" => FakeCheckoutView::as_view(),
//          "name" => "FakeCheckoutView",
//          "title" => "FakeCheckoutView"
//      ),
//      array(
//          "uri" => "/startover/$",
//          "view" => StartOverRedirectView::as_view(),
//          "name" => "DashboardTemplateView",
//          "title" => "DashboardTemplateView"
//      ),
//      array(
//          "uri" => "/twitter/followers/$",
//          "view" => TwitterFollowersView::as_view(),
//          "name" => "TwitterFollowersView",
//          "title" => "TwitterFollowersView"
//      ),
//      array(
//          "uri" => "/instagram/followers/$",
//          "view" => InstagramFollowersView::as_view(),
//          "name" => "InstagramFollowersView",
//          "title" => "InstagramFollowersView"
//      ),
//      array(
//          "uri" => "/facebook/resource/$",
//          "view" => FacebookUpdateResourceView::as_view(),
//          "name" => "FacebookUpdateResourceView",
//          "title" => "FacebookUpdateResourceView"
//      ),
//      array(
//          "uri" => "/d/$",
//          "view" => DownloadCookieWizardView::as_view(),
//          "name" => "DownloadCookieWizardView",
//          "title" => "DownloadCookieWizardView"
//      ),
//      array(
//          "uri" => "/d/(?P<step>\w+)/$",
//          "view" => DownloadCookieWizardView::as_view(),
//          "name" => "DownloadCookieWizardView",
//          "title" => "DownloadCookieWizardView"
//      ),
//      array(
//          "uri" => "/$",
//          "view" => WpCookieWizardView::as_view(),
//          "name" => "DashboardTemplateView",
//          "title" => "DashboardTemplateView"
//      ),
//      array(
//          "uri" => "/(?P<step>\w+)/$",
//          "view" => WpCookieWizardView::as_view(),
//          "name" => "DashboardTemplateView",
//          "title" => "DashboardTemplateView"
//      ),
  );
}