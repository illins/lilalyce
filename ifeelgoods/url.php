<?php

namespace Wp {
  require_once("apps/wp/ifeelgoods/view.php");

  $wp_ifeelgoods_url_patterns = array(
      array(
          "uri" => "/rewards/$",
          "view" => IfeelGoodsRewardsListTemplateView::as_view(),
          "name" => "IfeelGoodsCategoryListTemplateView",
          "title" => "Rewards List"
      ),
  );
}

