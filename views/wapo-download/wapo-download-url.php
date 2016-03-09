<?php

namespace Wp {
  require_once 'apps/wp/views/wapo-download/base.php';
  require_once 'apps/wp/views/wapo-download/wapo.php';
  require_once 'apps/wp/views/wapo-download/email.php';
  require_once 'apps/wp/views/wapo-download/text.php';
  
  // Base download.
  WpDownloadBaseView::register_url(array("pattern"=>"/wp/wapo/download/"));
}