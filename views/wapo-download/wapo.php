<?php

namespace Wp {
  require_once 'blink/base/view/json.php';
  require_once 'apps/wp/config.php';
  
  class WpDownloadBaseFormView extends \Blink\JSONFormView {
    protected $form_class = "\Blink\Form";
  }
  
  class WpDownloadFormView extends WpDownloadBaseFormView {
    protected function form_valid() {
      parent::form_valid();
    }
  }
  
}