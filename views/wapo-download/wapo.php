<?php

namespace Wp {
  require_once 'blink/base/view/json.php';
  require_once 'apps/wp/config.php';
  require_once 'apps/wp/helper.php';
  
  /**
   * Base form for download section.
   */
  class WpDownloadBaseFormView extends \Blink\JSONFormView {
    protected function get_require_csrf_token() {
      return false;
    }
    
    protected $form_class = "\Blink\Form";
  }
  
  /**
   * Given a wapo id or a code, check that it is valid.
   * @url /wp/wapo/download/
   */
  class WpWapoCodeCheckFormView extends WpDownloadBaseFormView {
    private $wapo;
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      $c['wapo'] = $this->wapo;
      return $c;
    }
    
    protected function form_valid() {
      $wapo = \Wapo\Wapo::get_or_404(array("id"=>$this->request->post->find("wapo")), "Wapo not found!");
      
      $this->wapo = array(
          "id" => $wapo->id,
          "marketplace" => $wapo->marketplace,
          "delivery_method" => $wapo->delivery_method
      );
      
      return parent::form_valid();
    }
  }
  
  /**
   * Given an wapo_id, id (contact) and confirmation, confirm the code.
   * @url /wp/wapo/download/confirm/
   */
  class WpWapoConfirmationCheckFormView extends WpDownloadBaseFormView {
    private $confirmed;
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      $c['confirmed'] = $this->confirmed;
      return $c;
    }
    
    protected function form_valid() {
      $wapo_id = $this->request->post->find("wapo");
      $contact = $this->request->post->find("contact");
      $confirmation = $this->request->post->find("confirmation");
      
      \Wapo\WapoRecipient::get_or_404(array("wapo"=>$wapo_id,"contact"=>$contact,"confirm"=>$confirmation), "Invalid confirmation code!");
      $this->confirmed = true;
      
      return parent::form_valid();
    }
  }
  
  /**
   * Given an wapo_id, id (contact) and confirmation, confirm the code.
   * @url /wp/wapo/download/confirm/
   */
  class WpWapoInfoFormView extends WpDownloadBaseFormView {
    protected function get_context_data() {
      $c = parent::get_context_data();
      $wapo = \Wapo\Wapo::get_or_404(array("id"=>$this->request->get->find('wapo')), "Wapo not found!");
      $c['wapo'] = array("id"=>$wapo->id,"marketplace"=>$wapo->marketplace);
      return $c;
    }
  }
  
  /**
   * Show card reward
   * @url /wp/wapo/download/reward/
   */
  class WpWapoDownloadRewardFormView extends WpDownloadBaseFormView {
    private $reward;
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      $c['reward'] = $this->reward;
      return $c;
    }
    
    protected function form_valid() {
      $wapo_id = $this->request->post->find("wapo", null);
      $contact = $this->request->post->find("contact", null);
      $confirmation = $this->request->post->find("confirmation", null);
      
      if(!$wapo_id || !$contact || !$confirmation) {
        $this->set_error("Could not confirm code!");
        return $this->form_invalid();
      }
      
      $wr = \Wapo\WapoRecipient::get_or_null(array("wapo"=>$wapo_id,"contact"=>$contact,"confirm"=>$confirmation));
      if(!$wr) {
        $this->set_error("Could not confirm code!");
        return $this->form_invalid();
      }
      
      if($wr->wapo->marketplace != "tangocards") {
        $this->set_error("Invalid path!");
        return $this->form_invalid();
      }
      
      $error = true;
      if ($wr->extra != "-") {
        $tangoapi = new \BlinkTangoCard\TangoCardAPI(array("request" => $this->request));

        $order = $tangoapi->order($wr->extra);

        // If we retrieved it correctly, then continue.
        if ($order->success) {
          if (isset($order->order->reward->pin)) {
            $this->reward = array(
                "number" => $order->order->reward->number,
                "pin" => $order->order->reward->pin
            );
          } else {
            $this->reward = array(
                "number" => $order->order->reward->number,
                "url" => $order->order->reward->redemption_url
            );
          }
          $error = false;
        }
      }

      if($error) {
        $this->set_error("Could not fetch the reward! Please try again later!");
        return $this->form_invalid();
      }
      
      return parent::form_valid();
    }
  }
  
  /**
   * Prepare download.
   * @url /wp/wapo/download/prepare/
   */
  class WpWapoPrepareDownloadFormView extends WpDownloadBaseFormView {
    private $hash;
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      
      $c['hash'] = null;
      if($this->hash) {
        $wapo_id = $this->request->post->find("wapo", null);
        $contact = $this->request->post->find("contact", null);
        $confirmation = $this->request->post->find("confirmation", null);
      
        $c['url'] = sprintf("/wp/wapo/download/get/?wapo=%s&contact=%s&confirmation=%s&hash=%s", $wapo_id, $contact, $confirmation, $this->hash);
      }
      
      return $c;
    }
    
    protected function form_valid() {
//      $wapo_id = $this->request->session->prefix("wapo-download-")->find("wapo_id", null);
//      $code = $this->request->session->prefix("wapo-download-")->find("code", null);
//      $confirm = $this->request->session->prefix("wapo-download-")->find("confirm", null);
      
      $wapo_id = $this->request->post->find("wapo", null);
      $contact = $this->request->post->find("contact", null);
      $confirmation = $this->request->post->find("confirmation", null);
      
      if(!$wapo_id || !$contact || !$confirmation) {
        $this->set_error("Could not confirm code!");
        return $this->form_invalid();
      }
      
      $wr = \Wapo\WapoRecipient::get_or_null(array("wapo"=>$wapo_id,"contact"=>$contact,"confirm"=>$confirmation));
      if(!$wr) {
        $this->set_error("Could not confirm code!");
        return $this->form_invalid();
      }
      
      if($wr->wapo->marketplace != "promotion") {
        $this->set_error("Invalid path!");
        return $this->form_invalid();
      }
      
      $this->hash = Helper::DigitalDownloadHash($wr);
      
      $wr->download_code = $this->hash;
      $wr->expire_date = date("m/d/Y H:i A");
      $wr->save(false);
      
      return parent::form_valid();
    }
  }
  
  /**
   * Allow donwload of wapo.
   * @url /wp/wapo/download/get/
   */
  class WpWapoDownloadFormView extends \Blink\TemplateView {
    protected function get() {
      if($this->request->get->is_set("hash")) {
        $wapo_id = $this->request->get->find("wapo", null);
        $contact = $this->request->get->find("contact", null);
        $confirmation = $this->request->get->find("confirmation", null);

        if (!$wapo_id || !$contact || !$confirmation) {
          return \Blink\HttpResponse("Could not confirm code!", "text/plain", \Blink\Response::NOT_FOUND);
        }

        $wr = \Wapo\WapoRecipient::get_or_null(array("wapo" => $wapo_id, "contact" => $contact, "confirm" => $confirmation));
        if(!$wr) {
          return \Blink\HttpResponse("Could not confirm download code!", "text/plain", \Blink\Response::NOT_FOUND);
        }
        
        $response = new \Blink\Response();
        $promotion = \Wapo\Promotion::queryset()->clear()->filter(array("id"=>$wr->wapo->promotion))->get();
        $response->file = $promotion->download;
        
        return $response;
      } else {
        return \Blink\HttpResponse("Download not found!", "text/plain", \Blink\Response::NOT_FOUND);
      }
    }
  }
  
}