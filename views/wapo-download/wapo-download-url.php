<?php

namespace Wp {
  require_once 'apps/wp/views/wapo-download/base.php';
  require_once 'apps/wp/views/wapo-download/wapo.php';
  require_once 'apps/wp/views/wapo-download/email.php';
  require_once 'apps/wp/views/wapo-download/text.php';
  
  // Base download template.
  WpDownloadBaseView::register_url(array("pattern"=>"/wp/wapo/download/"));
  
  WpWapoCodeCheckFormView::register_url(array("pattern"=>"/wp/wapo/download/check/"));
  
  WpEmailSendConfirmCodeFormView::register_url(array("pattern"=>"/wp/wapo/download/email/check/"));
  WpTextSendConfirmCodeFormView::register_url(array("pattern"=>"/wp/wapo/download/text/check/"));
  
  WpWapoConfirmationCheckFormView::register_url(array("pattern"=>"/wp/wapo/download/confirm/"));
  
  WpWapoInfoFormView::register_url(array("pattern"=>"/wp/wapo/download/info/"));
  
  WpWapoPrepareDownloadFormView::register_url(array("pattern"=>"/wp/wapo/download/prepare/"));
  WpWapoDownloadFormView::register_url(array("pattern"=>"/wp/wapo/download/get/"));
  
  WpWapoDownloadRewardFormView::register_url(array("pattern"=>"/wp/wapo/download/reward/"));
}