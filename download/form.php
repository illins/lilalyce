<?php

namespace Wp {
  require_once("blink/base/form/form.php");

  /**
   * Form to accept a code.
   */
  class CheckWapoForm extends \Blink\Form {

    public $code;

    public function Fields() {
      $form_fields = parent::Fields();
      $this->code = $form_fields->CharField(array("name" => "code","min_length"=>3,"max_length"=>20));
      return $form_fields;
    }

  }
  
  class WapoEmailForm extends \Blink\Form {
    public $email;

    public function Fields() {
      $form_fields = parent::Fields();
      $this->email = $form_fields->EmailField(array("name" => "email","min_length"=>3,"max_length"=>20));
      return $form_fields;
    }
  }
  
  class ConfirmEmailCode extends \Blink\Form {
    public $confirm;

    public function Fields() {
      $form_fields = parent::Fields();
      $this->confirm = $form_fields->EmailField(array("name" => "confirm","min_length"=>3,"max_length"=>20));
      return $form_fields;
    }
  }
  
  class FacebookUserIdForm extends \Blink\Form {
    public $facebook_id;

    public function Fields() {
      $form_fields = parent::Fields();
      $this->facebook_id = $form_fields->HiddenCharField(array("name" => "facebook_id","min_length"=>3,"max_length"=>20));
      return $form_fields;
    }
  }

}
