<?php

namespace Wp {
  // WP download.
  require_once("apps/wp/download/view.php");
  
  require_once 'apps/wp/download/views/download.email.text.view.php';

  $wp_download_url_patterns = array(
      array(
          "uri" => "/$",
          "view" => CheckWapoRedirectView::as_view(),
          "name" => "CheckWapoRedirectView",
          "title" => "Check Wapo"
      ),
      array(
          "uri" => "/file/$",
          "view" => DigitalDownloadTemplateView::as_view(),
          "name" => "DigitalDownloadTemplateView",
          "title" => "File Download"
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
      ),
      array(
          "uri" => "/text/$",
          "view" => TextSendCodeFormView::as_view(),
          "name" => "TextSendCodeFormView",
          "title" => "Text Send Code"
      ),
      array(
          "uri" => "/(e|el|email|mailchimp)/$",
          "view" => EmailSendCodeFormView::as_view(),
          "name" => "EmailSendCodeFormView",
          "title" => "Email Send Code"
      ),
      array(
          "uri" => "/(e|el|email|text|mailchimp)/confirm/$",
          "view" => EmailTextConfirmCodeFormView::as_view(),
          "name" => "EmailTextConfirmCodeFormView",
          "title" => "Confirm Code"
      ),
      array(
          "uri" => "/(e|el|email|text|mailchimp)/download/$",
          "view" => EmailTextPrepareDownloadTemplateView::as_view(),
          "name" => "EmailTextPrepareDownloadTemplateView",
          "title" => "Download"
      )
  );
}