<?php

namespace Wp {
  // WP download.
  require_once("apps/wp/download/view.php");

  $wp_download_url_patterns = array(
      array(
          "uri" => "/$",
          "view" => CheckWapoRedirectView::as_view(),
          "name" => "CheckWapoRedirectView",
          "title" => "Check Wapo"
      ),
      array(
          "uri" => "/atf/$",
          "view" => TwitterATFDownloadTemplateView::as_view(),
          "name" => "TwitterATFDownloadTemplateView",
          "title" => "Any Twitter Follower"
      ),
      array(
          "uri" => "/stf/$",
          "view" => TwitterSTFDownloadTemplateView::as_view(),
          "name" => "TwitterSTFDownloadTemplateView",
          "title" => "Select Twitter Follower"
      ),
      array(
          "uri" => "/aff/$",
          "view" => FacebookAFFDownloadTemplateView::as_view(),
          "name" => "FacebookAFFDownloadTemplateView",
          "title" => "Facebook Friend"
      ),
      array(
          "uri" => "/fp/$",
          "view" => FacebookFPDownloadTemplateView::as_view(),
          "name" => "FacebookFPDownloadTemplateView",
          "title" => "Facebook Page"
      )
  );
}