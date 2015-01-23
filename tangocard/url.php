<?php

namespace Wp {
  require_once("apps/wp/tangocard/view.php");

  $wp_tangocard_url_patterns = array(
      array(
          "uri" => "/rewards/$",
          "view" => TangoCardRewardsListTemplateView::as_view(),
          "name" => "TangoCardRewardsListTemplateView",
          "title" => "Rewards List"
      ),
  );
}

