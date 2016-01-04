<?php

namespace Wp {
  require_once 'blink/base/view/generic.php';
  require_once 'apps/wp/config.php';

  /**
   * Base HTML view for the wapo template.
   * @url /wp/wapo/
   */
  class WpBaseView extends \Blink\TemplateView {

    protected function get_template() {
      return WpTemplateConfig::Template("wapo/base.twig");
    }

  }

}