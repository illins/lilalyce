<?php

namespace Wp {
  require_once("blink/base/view/generic.php");
  require_once("blink/base/view/edit.php");
  require_once("blink/base/view/detail.php");
  require_once("blink/base/view/list.php");
  require_once("blink/base/view/wizard.php");
  
  require_once("apps/wp/config.php");
  require_once("apps/wapo/model.php");
  require_once("apps/wp/download/form.php");

  require_once("apps/blink-user/api.php");
  
  require_once 'apps/blink-bulksms/api.php';
  
  require_once 'apps/wp/helper.php';
  
  require_once 'apps/blink-bitly/bitly/bitly.php';
  
  /**
   * Email page to go download the code.
   */
  class EmailSendCodeFormView extends \Blink\FormView {
    protected $form_class = "\Wp\EmailConfirmForm";
    
    protected function get_template() {
      return WpTemplateConfig::Template("/download/email.code.send.form.twig");
    }
    
    protected function form_valid() {
      $recipient = \Wapo\WapoRecipient::get_or_404(array(
          "wapo"=>$this->request->get->find("wapo_id"),
          "wapo_wapotargeturl.code"=>$this->request->get->find("code"),
          "contact"=>$this->form->get("email")), "Email not found.");
      
      // Create the confirmation code.
      $recipient->confirm = dechex(rand(1, 16777215));
      
      // Send the email and check if it was sent.
      $long_url = sprintf("%s/wp/download/e/confirm/?%s&confirm=%s", \Blink\SiteConfig::SITE, $this->request->query_string, $recipient->confirm);
      $shortened = (new \BlinkBitly\BlinkBitlyAPI())->shorten($long_url);
      
      $message = sprintf("Use this code '%s' to confirm your email or follow the link to confirm [%s].", $recipient->confirm, $shortened);
      $sent = @mail($recipient->contact, "Wapo Confirmation Code", $message);
      if(!$sent) {
        $this->set_error("Could not send confirmation code.");
        return $this->form_invalid();
      }
      
      // Save the confirmation code.
      $recipient->save(false);
      
      return \Blink\HttpResponseRedirect("/wp/download/e/confirm/?" . $this->request->query_string);
    }
  }
  
  /**
   * Confirm the code sent to the phone number.
   */
  class EmailConfirmCodeFormView extends \Blink\FormView {
    protected $form_class = "\Wp\EmailConfirmCodeForm";
    
    protected function get_template() {
      return WpTemplateConfig::Template("/download/email.code.confirm.form.twig");
    }
    
    protected function form_valid() {
      $recipient = \Wapo\WapoRecipient::get_or_404(array(
          "wapo"=>$this->request->get->find("wapo_id"),
          "wapo_wapotargeturl.code"=>$this->request->get->find("code"),
          "confirm"=>$this->form->get("confirm")), "Confirm Code is not valid.");
      
      // Create the confirmation code.
      $recipient->confirmed = true;
      $recipient->save(false);
      
      $url = sprintf("/wp/download/e/download/?confirm=%s&%s", $recipient->confirm, $this->request->query_string);
      return \Blink\HttpResponseRedirect($url);
    }
    
    protected function get_field_override_data() {
      return array(
          "confirm" => array(
              "value" => $this->request->get->find("confirm")
          )
      );
    }
  }
  
  /**
   * Prepare the download.
   * - If card, get the number.
   * - If downloadable item, prepare the link.
   */
  class EmailPrepareDownloadTemplateView extends \Blink\TemplateView {
    
    protected function get_template() {
      return WpTemplateConfig::Template("/download/email.download.twig");
    }
    
    protected function get_context_data() {
      $c = parent::get_context_data();
      
      $recipient = \Wapo\WapoRecipient::get_or_404(array(
          "wapo"=>$this->request->get->find("wapo_id"),
          "wapo_wapotargeturl.code"=>$this->request->get->find("code"),
          "confirm"=>$this->request->get->find("confirm")), "Download error.");
      
      $promotion = \Wapo\Promotion::get_or_404(array("id"=>$recipient->wapo->promotion));
      
//      $download = true;
//      // Check that that the pipeline time has not expried.
//      $now = new \DateTime('now');
//      $expire_date = new \DateTime($recipient->expire_date);
//      $diff = $now->diff($expire_date);
//      
//      if($diff->h > 1) {
//        $download = false;
//      }
      
//      // If: 'tango-card' - Get the reward.
//      // ElseIf: 'wapo' - Set the downloads.
//      $reward = null;
//      $sku = null;
//      if ($promotion->promotioncategory->tag == "tango-card") {
//        $reward = (new \BlinkTangoCard\TangoCardAPI(array("request" => $this->request)))->order($recipient->extra);
//        $sku = \Wapo\TangoCardRewards::get_or_null(array("sku"=>$wapo->sku));
//      } else if ($promotion->promotioncategory->tag == "wapo" && $download) {
//        $recipient->download = Helper::DigitalDownloadHash($recipient);
//        $recipient->expire_date = date("m/d/Y H:i A");
//        $recipient->save(false);
//      }
      
      $recipient->download_code = Helper::DigitalDownloadHash($recipient);
      $recipient->expire_date = date("m/d/Y H:i A");
      $recipient->save(false);
            
//      $c['reward'] = $reward;
//      $c['wapo'] = $wapo;
      $c['promotion'] = $promotion;
      $c['promotioncategory'] = $promotion->promotioncategory;
//      $c['sku'] = $sku;
      $c['recipient'] = $recipient;
      
      return $c;
    }
  }
  
  
}
