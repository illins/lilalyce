<?php

namespace Wp {
  require_once 'blink/base/view/generic.php';
  require_once 'apps/wp/config.php';

  require_once 'apps/wp/views/wapo-download/wapo.php';
  require_once 'apps/blink-bulksms/api.php';
  
  class WpEmailSendConfirmCodeFormView extends WpDownloadBaseFormView {
    private $sent = false;
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      $c['sent'] = $this->sent;
      return $c;
    }
    
    protected function form_valid() {
      $wapo_id = $this->request->post->find("wapo", null);
      $email = $this->request->post->find("email", null);
      
      $wr = \Wapo\WapoRecipient::get_or_404(array("wapo"=>$wapo_id,"contact"=>$email), "Wapo was not sent to this email!");
      $wr->confirm = dechex(rand(1, 16777215));
      $wr->save(false);
      
      if(@mail($wr->contact, "Wapo confirmation code: ", $wr->confirm)) {
        $this->sent = true;
      }
      
      if(!$this->sent) {
        $this->set_error("Could not send confirmation code!");
        return $this->form_invalid();
      }
      
      return parent::form_valid();
    }
  }
  
  class WpTextSendConfirmCodeFormView extends WpDownloadBaseFormView {
    private $sent = false;
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      $c['sent'] = $this->sent;
      return $c;
    }
    
    protected function form_valid() {
      $wapo_id = $this->request->post->find("wapo", null);
      $number = $this->request->post->find("number", null);
      $cleaned = Helper::CleanNumber($number);
      
      // If the text confirmation has been sent more than 2 or more times, go to the confirmation.
      if(!is_int((int) $cleaned) || strlen($cleaned) != 10) {
        $this->set_error("Please enter a valid 10 digit phone number");
        return $this->form_invalid();
      }
      
      $wr = \Wapo\WapoRecipient::get_or_404(array("wapo"=>$wapo_id,"contact"=>$cleaned), "Wapo was not sent to this email!");
      $wr->confirm = dechex(rand(1, 16777215));
      
      // If the text confirmation has been sent more than 2 or more times, go to the confirmation.
      if($wr->text_confirm_count >= 2) {
        $this->set_error("You can only send the text confirmation 2 times.");
        return $this->form_invalid();
      }
      
      $bulksms = new \BlinkBulkSMS\BulkSMSAPI();
      $message = sprintf("Use this code '%s' to confirm your phone number.", $wr->confirm);
      $result = $bulksms->send_to_us_number($message, $cleaned);

      if (!$result[0]) {
        $this->set_error("Could not send the confirmation code.");
        return $this->form_invalid();
      }
      
      $this->sent = true;
      $wr->text_confirm_count++;
      $wr->save(false);

      return parent::form_valid();
    }

  }

}