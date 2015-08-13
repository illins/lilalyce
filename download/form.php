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
  
  /**
   * Request the phone number that the message was sent to.
   */
  class TextPhoneNumberForm extends \Blink\Form {
    public $phone_number;

    public function Fields() {
      $form_fields = parent::Fields();
      $this->phone_number = $form_fields->DecimalField(array("name" => "phone_number", "help_text"=>"Enter phone number that the code was sent to. We will send a confirmation code to verify. Please include the country code for your number.","min_length"=>3,"max_length"=>20,"decimal_places"=>0));
      return $form_fields;
    }
  }
  
  /**
   * Enter the confirmation code sent to the phone nyumber.
   */
  class TextConfirmCodeForm extends \Blink\Form {
    public $confirm;

    public function Fields() {
      $form_fields = parent::Fields();
      $this->confirm = $form_fields->CharField(array("verbose_name"=>"Confirmation Code","name" => "confirm","min_length"=>3,"max_length"=>20));
      return $form_fields;
    }
  }
  
  /**
   * Get the email to send the confirmation code to.
   */
  class EmailConfirmForm extends \Blink\Form {
    public $email;

    public function Fields() {
      $form_fields = parent::Fields();
      $this->email = $form_fields->EmailField(array("name" => "email", "help_text"=>"Please enter the email the Wapo was sent to. A confirmation code/url will be sent to the account."));
      return $form_fields;
    }
  }
  
  /**
   * Confirm the code sent to the email.
   */
  class EmailConfirmCodeForm extends \Blink\Form {
    public $confirm;

    public function Fields() {
      $form_fields = parent::Fields();
      $this->confirm = $form_fields->CharField(array("verbose_name"=>"Confirmation Code","name" => "confirm","min_length"=>3,"max_length"=>20));
      return $form_fields;
    }
  }

}
