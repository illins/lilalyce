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
      
      $this->request->session->delete("wapo");
      
      // Make sure it is in the session and that it is sent.
      $wapo = \Wapo\Wapo::get_or_404(array("id"=>$this->request->session->find("wapo_id", null)), "Invalid Wapo id!");
      $notsent = \Wapo\WapoRecipient::queryset()->count(array("wapo"=>$wapo,"sent"=>false));
      
      // If we don't have this data, fetch it and populate it.
      if(!$wapo->checkout) {
        if($wapo->payment_method->tag == "wepay") {
          $checkout = (new \WePay\WepayAPI())->checkout($wapo->checkoutid);
          $wapo->checkout = json_encode($checkout);
          $wapo->save(false);
        }
      }
      
      // Output data.
      $c['wapo'] = array(
          "id" => $wapo->id,
          "profile" => $wapo->profile,
          "delivery_method" => str_replace("-", " ", $wapo->delivery_method),
          "payment_method" => $wapo->payment_method,
          "quantity" => $wapo->quantity,
          "unit_price" => $wapo->unit_price,
          "timestamp" => $wapo->timestamp,
          "tangocardrewards" => $wapo->tangocardrewards,
          "promotion" => $wapo->promotion,
          "marketplace" => $wapo->marketplace,
          "notsent" => $notsent,
          "checkout" => json_decode($wapo->checkout),
          "bitlyurl" => $wapo->bitlyurl
      );
      return $c;
    }
  }
}