<?php

namespace Wp {
  require_once 'blink/base/view/generic.php';
  require_once 'apps/wapo/model.php';
  
  class WpTangoCardRewardsListView extends \Blink\JSONListView {
    protected $class = "\Wapo\TangoCardRewards";
  }
}