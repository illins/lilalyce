<?php

namespace Wp {
  require_once("apps/wp/scalablepress/view.php");

  $wp_scalable_url_patterns = array(
      array(
          "uri" => "/category/$",
          "view" => ScalablePressCategoryListTemplateView::as_view(),
          "name" => "ScalablePressCategoryListTemplateView",
          "title" => "Category List"
      ),
      array(
          "uri" => "/category/(?P<category_id>[\w-]+)/$",
          "view" => ScalablePressProductListTemplateView::as_view(),
          "name" => "ScalablePressProductListTemplateView",
          "title" => "Product List"
      ),
      array(
          "uri" => "/products/(?P<product_id>[\w-]+)/$",
          "view" => ScalablePressProductDetailTemplateView::as_view(),
          "name" => "ScalablePressProductDetailTemplateView",
          "title" => "Product Detail"
      ),
      array(
          "uri" => "/products/(?P<product_id>[\w-]+)/availability/$",
          "view" => ScalablePressAvailabilityTemplateView::as_view(),
          "name" => "ScalablePressAvailabilityTemplateView",
          "title" => "Product Availability"
      ),
      array(
          "uri" => "/quote/$",
          "view" => ScalablePressQuoteFormView::as_view(),
          "name" => "ScalablePressQuoteFormView",
          "title" => "Product Quote"
      ),
  );
}

