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
   * Text page to go download the code.
   */
  class TextSendCodeFormView extends \Blink\FormView {

    protected $form_class = "\Wp\TextPhoneNumberForm";

    protected function get_template() {
      return WpTemplateConfig::Template("/download/text.code.send.form.twig");
    }

    protected function form_valid() {
      $recipient = \Wapo\WapoRecipient::get_or_404(array(
          "wapo" => $this->request->get->find("wapo_id"),
          "wapo_wapotargeturl.code" => $this->request->get->find("code"),
          "contact" => $this->form->get("phone_number")), "Phone Number not found.");

//      // Check that phone number entered matches the phone number of the account.
//      if($recipient->contact != $this->form->get("phone_number")) {
//        $this->set_error("Phone number entered does not match the phone number code was sent to.");
//        return $this->form_invalid();
//      }
      // Create the confirmation code.
      $recipient->confirm = dechex(rand(1, 16777215));
      $recipient->save(false);
      
      $long_url = sprintf("%s/wp/download/text/confirm/?%s&confirm=%s", \Blink\SiteConfig::SITE, $this->request->query_string, $recipient->confirm);
      $shortened = (new \BlinkBitly\BlinkBitlyAPI())->shorten($long_url);

      $bulksms = new \BlinkBulkSMS\BulkSMSAPI();
      $result = $bulksms->send_seven_bit_sms(sprintf("Use this code '%s' or url '%s' to confirm your phone number.", $recipient->confirm, $shortened), $this->form->get("phone_number"));

      if (!$result[0]) {
        $this->set_error("Could not send the confirmation code.");
        return $this->form_invalid();
      }

      return \Blink\HttpResponseRedirect("/wp/download/text/confirm/?" . $this->request->query_string);
    }

  }

  /**
   * Confirm the code sent to the phone number.
   */
  class TextConfirmCodeFormView extends \Blink\FormView {

    protected $form_class = "\Wp\TextConfirmCodeForm";

    protected function get_template() {
      return WpTemplateConfig::Template("/download/text.code.confirm.form.twig");
    }

    protected function form_valid() {
      $recipient = \Wapo\WapoRecipient::get_or_404(array(
          "wapo" => $this->request->get->find("wapo_id"),
          "wapo_wapotargeturl.code" => $this->request->get->find("code"),
          "confirm" => $this->form->get("confirm")), "Confirm Code is not valid.");

//      // Check that phone number entered matches the phone number of the account.
//      if($recipient->confirm != $this->form->get("confirm")) {
//        $this->set_error("Confirmation code doesn't match confirmation code in our records.");
//        return $this->form_invalid();
//      }
      // Create the confirmation code.
      $recipient->confirmed = true;
      $recipient->save(false);

      $url = sprintf("/wp/download/text/download/?confirm=%s&%s", $recipient->confirm, $this->request->query_string);
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
  class TextPrepareDownloadTemplateView extends \Blink\TemplateView {

    protected function get_template() {
      return WpTemplateConfig::Template("/download/text.download.twig");
    }

    protected function get_context_data() {
      $c = parent::get_context_data();

      $recipient = \Wapo\WapoRecipient::get_or_404(array(
          "wapo"=>$this->request->get->find("wapo_id"),
          "wapo_wapotargeturl.code"=>$this->request->get->find("code"),
          "confirm"=>$this->request->get->find("confirm")), "Download error.");
      
      $promotion = \Wapo\Promotion::get_or_404(array("id"=>$recipient->wapo->promotion));

      $recipient->download_code = Helper::DigitalDownloadHash($recipient);
      $recipient->expire_date = date("m/d/Y H:i A");
      $recipient->save(false);

      $c['promotion'] = $promotion;
      $c['promotioncategory'] = $promotion->promotioncategory;
      
      $c['recipient'] = $recipient;
    }

  }

}
