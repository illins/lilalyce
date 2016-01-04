<?php

namespace Wp {
  require_once 'blink/base/view/generic.php';
  require_once 'apps/wapo/model.php';
  
  /**
   * Get the details of wapo for the confirmation page.
   */
  class WpWapoConfirmationDetailView extends \Blink\JSONView {
    protected function get_context_data() {
      $c = parent::get_context_data();
      
      // Make sure it is in the session and that it is sent.
      $wapo = \Wapo\Wapo::get_or_404(array("id"=>$this->request->session->find("wapo_id", null)), "Invalid Wapo id!");
      $notsent = \Wapo\WapoRecipient::queryset()->count(array("wapo"=>$wapo,"sent"=>false));
      
      // Get marketplace item.
      $tangocards = null;
      if($wapo->marketplace == "tangocards") {
        $tangocards = \Wapo\TangoCardRewards::get_or_null(array("sku"=>$wapo->sku));
      }
      
      $checkout = (new \WePay\WepayAPI())->checkout($wapo->checkoutid);
      
      // Output data.
      $c['wapo'] = array(
          "id" => $wapo->id,
          "profile" => $wapo->profile,
          "delivery_method" => $wapo->delivery_method,
          "payment_method" => $wapo->payment_method,
          "quantity" => $wapo->quantity,
          "date_created" => $wapo->date_created,
          "tangocards" => $tangocards,
          "notsent" => $notsent,
          "gross" => $checkout->gross
      );
      return $c;
    }
  }
}