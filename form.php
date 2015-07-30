<?php

namespace Wp {
  require_once("blink/base/form/form.php");
  
  /**
   * Marketplace form.
   */
//  class MarketplaceForm extends \Blink\Form {
//    public $promotion_id;
//    
//    public function Fields() {
//      $form_fields = parent::Fields();
//      $this->promotion_id = $form_fields->IntegerField(array("verbose_name"=>"Promotion","name"=>"promotion_id"));
//      return $form_fields;
//    }
//  }
  
  /**
   * Modules form.
   */
  class WapoModuleForm extends \Blink\Form {
    public $module_id;
    
    public function Fields() {
      $form_fields = parent::Fields();
      $this->module_id = $form_fields->HiddenIntegerField(array("verbose_name"=>"Module","name"=>"module_id"));
      return $form_fields;
    }
  }
  
  /**
   * Announcement Form - Basically a delivery message form.
   */
  class WapoAnnouncementForm extends \Blink\Form {
    public $announcement;
    
    public function Fields() {
      $form_fields = parent::Fields();
      $this->announcement = $form_fields->TextField(array("name"=>"announcement"));
      return $form_fields;
    }
  }
  
  
  /**
   * Wapo form.
   */
  class WapoMarketplaceForm extends \Blink\Form {
    public $promotion_id;
    
    public function Fields() {
      $form_fields = parent::Fields();
      $this->promotion_id = $form_fields->IntegerField(array("verbose_name"=>"Promotion","name"=>"promotion_id"));
      return $form_fields;
    }
  }
  
  
  // DELIVERY SECTION.
  class DeliveryFFAForm extends \Blink\Form {
    public $ffa_quantity;
    
    public function Fields() {
      $form_fields = parent::Fields();
      
      $this->ffa_quantity = $form_fields->IntegerField(array("verbose_name"=>"Quantity","name"=>"ffa_quantity"));
      
      return $form_fields;
    }
  }
  
  /**
   * Wapo Details form like delivery message and expiring date.
   */
  class DetailsForm extends \Blink\Form {
    public $delivery_message;
    public $expiring_date;
    
    public function Fields() {
      $form_fields = parent::Fields();
      $this->delivery_message = $form_fields->TextField(array("name"=>"delivery_message","blank"=>true,"help_text"=>"Message sent or seen by the Wapo recipient."));
      $this->expiring_date = $form_fields->DateTimeField(array("name"=>"expiring_date","format"=>"m/d/Y H:i A","min_value"=>date("m/d/Y H:i A"), "help_text"=>"Date Wapo will expire (regardless of how many downloaded)."));
      
      return $form_fields;
    }
  }
  
  /**
   * Garment form.
   */
  class GarmentPickForm extends \Blink\Form {
    public $category_id;
    public $product_id;
    public $color;
    public $quantity;
    public $size;
    
    public function Fields() {
      $form_fields = parent::Fields();
      
      $this->category_id = $form_fields->HiddenIntegerField(array("verbose_name"=>"Category ID","name"=>"category_id"));
      $this->product_id = $form_fields->HiddenIntegerField(array("verbose_name"=>"Product ID","name"=>"product_id"));
      $this->color = $form_fields->HiddenCharField(array("name"=>"color","max_length"=>10));
      $this->quantity = $form_fields->HiddenIntegerField(array("name"=>"quantity","max_length"=>5,"min_value"=>1));
      $this->size = $form_fields->HiddenCharField(array("name"=>"size","max_length"=>10));
      
      return $form_fields;
    }
  }
  
  /**
   * Garment form.
   */
  class GarmentForm extends \Blink\Form {
    public $category_id;
    public $product_id;
    public $color;
    public $quantity;
    public $size;
    public $name;
    public $address1;
    public $city;
    public $state;
    public $zip;
    public $designId;
    
    public function Fields() {
      $form_fields = parent::Fields();
      
      $STATES = array(
          "" => "Select State",
          "AL" => "Alabama",
          "AK" => "Alaska",
          "AZ" => "Arizona",
          "AR" => "Arkansas",
          "CA" => "California",
          "CO" => "Colorado",
          "CT" => "Connecticut",
          "DE" => "Delaware",
          "FL" => "Florida",
          "GA" => "Georgia",
          "HI" => "Hawaii",
          "ID" => "Idaho",
          "IL" => "Illinois",
          "IN" => "Indiana",
          "IA" => "Iowa",
          "KS" => "Kansas",
          "KY" => "Kentucky",
          "LA" => "Louisiana",
          "ME" => "Maine",
          "MD" => "Maryland",
          "MA" => "Massachusetts",
          "MI" => "Michigan",
          "MN" => "Minnesota",
          "MS" => "Mississippi",
          "MO" => "Missouri",
          "MT" => "Montana",
          "NE" => "Nebraska",
          "NV" => "Nevada",
          "NH" => "New Hampshire",
          "NJ" => "New Jersey",
          "NM" => "New Mexico",
          "NY" => "New York",
          "NC" => "North Carolina",
          "ND" => "North Dakota",
          "OH" => "Ohio",
          "OK" => "Oklahoma",
          "OR" => "Oregon",
          "PA" => "Pennsylvania",
          "RI" => "Rhode Island",
          "SC" => "South Carolina",
          "SD" => "South Dakota",
          "TN" => "Tennessee",
          "TX" => "Texas",
          "UT" => "Utah",
          "VT" => "Vermont",
          "VA" => "Virginia",
          "WA" => "Washington",
          "WV" => "West Virginia",
          "WI" => "Wisconsin",
          "WY" => "Wyoming"
      );
      
      $this->category_id = $form_fields->HiddenIntegerField(array("verbose_name"=>"Category ID","name"=>"category_id"));
      $this->product_id = $form_fields->HiddenIntegerField(array("verbose_name"=>"Product ID","name"=>"product_id"));
      $this->color = $form_fields->HiddenCharField(array("name"=>"color","max_length"=>10));
      $this->quantity = $form_fields->IntegerField(array("name"=>"quantity","max_length"=>5,"min_value"=>1));
      $this->size = $form_fields->HiddenCharField(array("name"=>"size","max_length"=>10));
      $this->name = $form_fields->CharField(array("name"=>"name","max_length"=>100));
      $this->address1 = $form_fields->TextField(array("name"=>"address1"));
      $this->city = $form_fields->CharField(array("name"=>"city"));
      $this->state = $form_fields->CharField(array("name"=>"state",$choices=$STATES));
      $this->zip = $form_fields->IntegerField(array("name"=>"zip","min_length"=>5,"max_length"=>5));
      $this->designId = $form_fields->HiddenCharField(array("verbose_name"=>"Design ID","name"=>"designId","max_length"=>100));
      
      return $form_fields;
    }
  }
  
  /**
   * Tango form.
   */
  class TangoCardMarketplaceForm extends \Blink\Form {
    public $sku;
//    public $amount;
    
    public function Fields() {
      $form_fields = parent::Fields();
      $this->sku = $form_fields->HiddenCharField(array("name"=>"sku"));//,"value"=>"AMZN-E-V-STD"
//      $this->amount = $form_fields->DecimalField(array("name"=>"amount"));
      return $form_fields;
    }
  }
  
  /**
   * I feel Goods form.
   */
  class IfgMarketplaceForm extends \Blink\Form {
    public $sku;
    
    public function Fields() {
      $form_fields = parent::Fields();
      $this->sku = $form_fields->HiddenCharField(array("name"=>"sku"));
      return $form_fields;
    }
  }
  
  /**
   * Scalable form.
   */
  class ScalableMarketplaceForm extends \Blink\Form {
    public $promotioncategory;
    public $sku;
    public $amount;
    
    public function Fields() {
      $form_fields = parent::Fields();
      $this->promotioncategory = $form_fields->HiddenIntegerField(array("name"=>"promotioncategory"));
      $this->sku = $form_fields->HiddenCharField(array("name"=>"sku"));
      $this->amount = $form_fields->DecimalField(array("name"=>"amount"));
      return $form_fields;
    }
  }
  
  /**
   * Delivery form.
   */
  class DeliveryForm extends \Blink\Form {
    public $delivery;
    
    public function Fields() {
      $delivery = array(
          "ffa" => "Free For All",
          "aff" => "Any Facebook Friends",
          "sff" => "Select Facebook Friends",
          "fp" => "Facebook Page",
          "e" => "Email",
          "el" => "Email List",
          "text" => "Text"
      );
      
      $form_fields = parent::Fields();
      $this->delivery = $form_fields->CharField(array("name"=>"delivery","choices"=>$delivery));
      return $form_fields;
    }
  }
  
  /**
   * - Base form that just requires delivery message and expiring date.
   */
  class BaseForm extends \Blink\Form {
    public $delivery_message;
    public $expiring_date;
    
    public function Fields() {
      $form_fields = parent::Fields();
      $this->delivery_message = $form_fields->TextField(array("name"=>"delivery_message","blank"=>true,"help_text"=>"Message sent or seen by the Wapo recipient."));
      $this->expiring_date = $form_fields->DateTimeField(array("name"=>"expiring_date","format"=>"m/d/Y H:i A","min_value"=>date("m/d/Y H:i A"), "help_text"=>"Date Wapo will expire (regardless of how many downloaded)."));
      
      return $form_fields;
    }
  }
  
  /**
   * - Get the id of an email list.
   */
  class EmailListForm extends BaseForm {
    public $emails;
    
    public function Fields() {
      $form_fields = parent::Fields();
      $this->emails = $form_fields->TextField(array("verbose_name"=>"Email List","name"=>"emails","blank"=>false,"help_text"=>"Please enter a comma ',' seperated list of emails. (Maximum 25)."));
      return $form_fields;
    }
  }
  
  /**
   * - Get the text numbers.
   */
  class PhoneNumberListForm extends BaseForm {
    public $phone_numbers;
    
    public function Fields() {
      $form_fields = parent::Fields();
      $this->phone_numbers = $form_fields->TextField(array("verbose_name"=>"Phone numbers","name"=>"phone_numbers","blank"=>false,"help_text"=>"Please enter a comma ',' seperated list of phone numbers (include country code and zip-code along with each phone number). (Maximum 25)."));
      return $form_fields;
    }
  }
  
  /**
   * - 
   */
  class MailChimpForm extends BaseForm {
    public $list_id;
    public $mailchimps;
    
    public function Fields() {
      $form_fields = parent::Fields();
      $this->list_id = $form_fields->HiddenCharField(array("verbose_name"=>"List","name"=>"list_id","max_length"=>100,"blank"=>false));
      $this->mailchimps = $form_fields->HiddenCharField(array("verbose_name"=>"Email List","name"=>"mailchimps","max_length"=>1000,"blank"=>true));
      return $form_fields;
    }
  }
  
  class TextForm extends BaseForm {
    public $numbers;
    
    public function Fields() {
      $form_fields = parent::Fields();
      $this->numbers = $form_fields->CharField(array("verbose_name"=>"Phone Numbers","name"=>"numbers","max_length"=>1000,"blank"=>false,"help_text"=>"Seperate Phone Numbers by a comma ','."));
      return $form_fields;
    }
  }
  
  /**
   * - Generic quantity form that just requires a quantity along with other info.
   */
  class GenericQuantityForm extends BaseForm {
    public $quantity;
    
    public function Fields() {
      $form_fields = parent::Fields();
      $this->quantity = $form_fields->IntegerField(array("name"=>"quantity","min_value"=>1,"help_text"=>"Enter the number of Wapos to send."));
      return $form_fields;
    }
  }
  
  class FreeForAllForm extends GenericQuantityForm {
//    public $email_confirmation;
//    
//    public function Fields() {
//      $form_fields = parent::Fields();
//      $this->email_confirmation = $form_fields->BooleanField(array("name"=>"email_confirmation","help_text"=>"User must use email in order to download the Wapo."));
//      return $form_fields;
//    }
  }
  
  class AnyFacebookFriendsForm extends BaseForm {
    public $quantity;
    
    public function Fields() {
      $form_fields = parent::Fields();
      $this->quantity = $form_fields->IntegerField(array("name"=>"quantity","min_value"=>1,"help_text"=>"Enter the number of Wapos to send."));
      return $form_fields;
    }
  }
  
  /**
   * - Form to get the page id.
   */
  class FacebookFriendsForm extends BaseForm {
    public $facebook_friends;
    
    public function Fields() {
      $form_fields = parent::Fields();
      $this->facebook_friends = $form_fields->HiddenCharField(array("verbose_name"=>"Facebook Friends","name"=>"facebook_friends","blank"=>false,"max_length"=>500));
      return $form_fields;
    }
  }
  
  /**
   * - Form to get the page id.
   */
  class FacebookPageForm extends BaseForm {
    public $quantity;
    public $facebook_page_id;
    
    public function Fields() {
      $form_fields = parent::Fields();
      $this->quantity = $form_fields->IntegerField(array("name"=>"quantity","min_value"=>1));
      $this->facebook_page_id = $form_fields->HiddenCharField(array("verbose_name"=>"Facebook Page","name"=>"facebook_page_id","blank"=>false,"max_length"=>50));
      return $form_fields;
    }
  }
  
  /**
   * - Form to get selected twitter followers.
   */
  class SelectTwitterFollowersForm extends BaseForm {
    public $twitter_followers;
    
    public function Fields() {
      $form_fields = parent::Fields();
      $this->twitter_followers = $form_fields->HiddenCharField(array("verbose_name"=>"Twitter Followers","name"=>"twitter_followers","blank"=>false,"max_length"=>500));
      return $form_fields;
    }
  }
  
  /**
   * - Form to get selected instagram followers.
   */
  class SelectInstagramFollowersForm extends BaseForm {
    public $instagram_followers;
    
    public function Fields() {
      $form_fields = parent::Fields();
      $this->instagram_followers = $form_fields->HiddenCharField(array("verbose_name"=>"Instagram Followers","name"=>"instagram_followers","blank"=>false,"max_length"=>500));
      return $form_fields;
    }
  }
  
  /**
   * - Create a new form.
   */
  class NewProfileForm extends \Blink\Form {
    public $name;
    public $email;// If no current account (require for facebook for back comunication).
    public $password;
    public $confirm_password;
//    public $fb_loc_id;
//    public $fb_loc_category;
//    public $fb_loc_name;

    public function Fields() {
      $form_fields = parent::Fields();
      $this->name = $form_fields->CharField(array("name"=>"name","verbose_name"=>"Name / Company Name","help_text"=>"Name to be used to create your profile."));
      $this->email = $form_fields->EmailField(array("name"=>"email","verbose_name"=>"Your email","help_text"=>"Email to be used to create or fetch your account (you retrieve your account at the last step)."));
      $this->password = $form_fields->PasswordField(array("name"=>"password","help_text"=>"Enter password to be used for your account"));
      $this->confirm_password = $form_fields->PasswordField(array("name"=>"confirm_password","help_text"=>"Confirm password to be used for your account"));
//      $this->fb_loc_id = $form_fields->HiddenCharField(array("name"=>"fb_loc_id","verbose_name"=>"Facebook Location ID","max_length"=>50,"blank"=>true,"default"=>"103983392971091"));
//      $this->fb_loc_category = $form_fields->HiddenCharField(array("name"=>"fb_loc_category","verbose_name"=>"Facebook Location Category","max_length"=>100,"blank"=>true,"default"=>"City"));
//      $this->fb_loc_name = $form_fields->HiddenCharField(array("name"=>"fb_loc_name","verbose_name"=>"Facebook Location Name","max_length"=>256,"blank"=>true,"default"=>"South Bend, Indiana"));
      return $form_fields;
    }
  }
  
  /**
   * - Form to get the page id.
   */
  class ProfilesForm extends \Blink\Form {
    public $profile_id;
    
    public function Fields() {
      $form_fields = parent::Fields();
      $this->profile_id = $form_fields->HiddenIntegerField(array("verbose_name"=>"Profile","name"=>"profile_id","blank"=>false));
      return $form_fields;
    }
  }
  
  class PaymentMethodForm extends \Blink\Form {
    protected $payment_method_id;
    
    public function Fields() {
      $form_fields = parent::Fields();
      $this->payment_method_id = $form_fields->IntegerField(array("verbose_name"=>"Payment Method","name"=>"payment_method_id","choices"=>\Wapo\PaymentMethod::queryset()->filter(array("name"=>"WePay"))));
      return $form_fields;
    }
  }
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  /**
   * - Expect some facebook ids as strings.
   */
  class DeliveryMethodSelectFacebookFriendsForm extends BaseForm {
    public $facebook_id_list;
    
    public function Fields() {
      $form_fields = parent::Fields();
      $this->facebook_id_list = $form_fields->HiddenCharField(array("name"=>"facebook_id_list","max_value"=>1000));
      return $form_fields;
    }
  }
  
  
  
  /**
   * - Expect some twiter followers ids as strings.
   */
  class DeliveryMethodSelectTwitterFollowersForm extends BaseForm {
    public $twitter_id_list;
    
    public function Fields() {
      $form_fields = parent::Fields();
      $this->twitter_id_list = $form_fields->HiddenCharField(array("name"=>"twitter_id_list","max_value"=>1000));
      return $form_fields;
    }
  }
  
  /**
   * - Expect some instagram followers ids as strings.
   */
  class DeliveryMethodSelectInstagramFollowersForm extends BaseForm {
    public $instagram_id_list;
    
    public function Fields() {
      $form_fields = parent::Fields();
      $this->instagram_id_list = $form_fields->HiddenCharField(array("name"=>"instagram_id_list","max_value"=>1000));
      return $form_fields;
    }
  }
  
  
  
  
  /**
   * - Capture/Validate these default fields required for each type of Wapo.
   */
  class ProfileInfoForm extends \Blink\Form {
    public $name;
    public $email;
    public $delivery_message;
    public $expiring_date;
    
    public function Fields() {
      $form_fields = parent::Fields();
      $this->name = $form_fields->CharField(array("name"=>"name","verbose_name"=>"Name / Company Name"));
      $this->email = $form_fields->EmailField(array("name"=>"email","verbose_name"=>"Your email.", "blank"=>True));
      $this->delivery_message = $form_fields->TextField(array("name"=>"delivery_message"));
      $this->expiring_date = $form_fields->DateField(array("name"=>"expiring_date"));
      return $form_fields;
    }
  }
  
  /**
   * - Capture an email list.
   */
  class ContactForm extends \Blink\Form {
    public $contact_id;
    
    public function Fields() {
      parent::Fields();
      
      $form = new \Blink\FormFields();
      $this->contact_id = $form->IntegerField(array("verbose_name"=>"Email List", "name"=>"contact_id"));
    }
  }
  
//  class ContactForm extends \Blink\Form {
//    public $contact_id;
//    public $delivery_method;
//    
//    public function Fields() {
//      parent::Fields();
//      
//      $form = new \Blink\FormFields();
//      $this->contact_id = $form->IntegerField(array("verbose_name"=>"Contact", "name"=>"contact_id"));
//      $this->delivery_method = $form->HiddenCharField(array("name"=>"delivery_method","value"=>"contact-list","max_length"=>20));
//    }
//  }
  
  class PhoneForm extends \Blink\Form {
    public $name_1;
    public $contact_1;
    public $name_2;
    public $contact_2;
    public $name_3;
    public $contact_3;
    
    public function Fields() {
      parent::Fields();
      
      $form = new \Blink\FormFields();
      $this->name_1 = $form->CharField(array("verbose_name"=>"Name", "name"=>"name_1","min_length"=>3,"max_length"=>50));
      $this->contact_1 = $form->IntegerField(array("verbose_name"=>"Email", "name"=>"contact_1","min_length"=>10,"max_length"=>10));
      $this->name_2 = $form->CharField(array("verbose_name"=>"Name", "name"=>"name_2","min_length"=>3,"max_length"=>50));
      $this->contact_2 = $form->IntegerField(array("verbose_name"=>"Email", "name"=>"contact_2","min_length"=>10,"max_length"=>10));
      $this->name_3 = $form->CharField(array("verbose_name"=>"Name", "name"=>"name_3","min_length"=>3,"max_length"=>50));
      $this->contact_3 = $form->IntegerField(array("verbose_name"=>"Email", "name"=>"contact_3","min_length"=>10,"max_length"=>10));
      $this->delivery_method = $form->HiddenCharField(array("name"=>"delivery_method","value"=>"phone"));
    }
  }
  
  class FacebookForm extends \Blink\Form {
    public $name_1;
    public $contact_1;
    public $name_2;
    public $contact_2;
    public $name_3;
    public $contact_3;

    public function Fields() {
      parent::Fields();
      
      $form = new \Blink\FormFields();
      $this->name_1 = $form->CharField(array("verbose_name"=>"Name", "name"=>"name_1","min_length"=>3,"max_length"=>50));
      $this->contact_1 = $form->IntegerField(array("verbose_name"=>"Email", "name"=>"contact_1","max_length"=>50));
      $this->name_2 = $form->CharField(array("verbose_name"=>"Name", "name"=>"name_2","min_length"=>3,"max_length"=>50));
      $this->contact_2 = $form->IntegerField(array("verbose_name"=>"Email", "name"=>"contact_2","max_length"=>50));
      $this->name_3 = $form->CharField(array("verbose_name"=>"Name", "name"=>"name_3","min_length"=>3,"max_length"=>50));
      $this->contact_3 = $form->IntegerField(array("verbose_name"=>"Email", "name"=>"contact_3","max_length"=>50));
      $this->delivery_method = $form->HiddenCharField(array("name"=>"delivery_method","value"=>"facebook"));
    }
  }
  
  class TwitterForm extends \Blink\Form {
    public $name_1;
    public $contact_1;
    public $name_2;
    public $contact_2;
    public $name_3;
    public $contact_3;
    
    public function Fields() {
      parent::Fields();
      
      $form = new \Blink\FormFields();
      $this->name_1 = $form->CharField(array("verbose_name"=>"Name", "name"=>"name_1","min_length"=>3,"max_length"=>50));
      $this->contact_1 = $form->EmailField(array("verbose_name"=>"Email", "name"=>"contact_1","max_length"=>50));
      $this->name_2 = $form->CharField(array("verbose_name"=>"Name", "name"=>"name_2","min_length"=>3,"max_length"=>50));
      $this->contact_2 = $form->EmailField(array("verbose_name"=>"Email", "name"=>"contact_2","max_length"=>50));
      $this->name_3 = $form->CharField(array("verbose_name"=>"Name", "name"=>"name_3","min_length"=>3,"max_length"=>50));
      $this->contact_3 = $form->EmailField(array("verbose_name"=>"Email", "name"=>"contact_3","max_length"=>50));
      $this->delivery_method = $form->HiddenCharField(array("name"=>"delivery_method","value"=>"twitter"));
    }
  }
  
  class PromotionProfileCreateForm extends \Blink\Form {
    public $name;
    public $email;
    public $delivery_message;
    public $delivery_date;
    public $expiring_date;

    public function Fields() {
      parent::Fields();
      
      $form = new \Blink\FormFields();
      $this->name = $form->CharField(array("verbose_name"=>"Name / Company Name","name"=>"first_name","min_length"=>3,"max_length"=>50));
      $this->email = $form->EmailField(array("verbose_name"=>"My Email","name"=>"email","min_length"=>3,"max_length"=>100));
      $this->delivery_message = $form->TextField(array("name"=>"delivery_message","blank"=>true));
      $this->delivery_date = $form->DateField(array("name"=>"delivery_date","blank"=>true,"min_value"=>date("m/d/Y")));
      $this->expiring_date = $form->DateField(array("name"=>"expiring_date","blank"=>true,"null"=>true,"default"=>NULL,"min_value"=>date("m/d/Y")));
    }
  }
  
  class PromotionProfileFacebookForm extends \Blink\Form {
    public $name;
    public $delivery_message;
    public $expiring_date;
    public $facebook_id;

    public function Fields() {
      parent::Fields();
      
      $form = new \Blink\FormFields();
      $this->name = $form->CharField(array("verbose_name"=>"Name / Company Name","name"=>"name","min_length"=>0,"max_length"=>50,"blank"=>true));
      $this->delivery_message = $form->TextField(array("name"=>"delivery_message","blank"=>true));
      $this->expiring_date = $form->DateField(array("name"=>"expiring_date","blank"=>true,"null"=>true,"default"=>NULL));
      $this->facebook_id = $form->HiddenCharField(array("name"=>"facebook_id","max_length"=>20));
    }
  }
  
  class ContactUsForm extends \Blink\Form {
    public $name;
    public $company;
    public $email;
    public $message;
    
    public function Fields() {
      parent::Fields();
      
      $form = new \Blink\FormFields();
      $this->name = $form->CharField(array("name"=>"name","max_length"=>100,"min_length"=>5));
      $this->company = $form->CharField(array("name"=>"company","max_length"=>100,"blank"=>true));
      $this->email = $form->EmailField(array("verbose_name"=>"Email Address","name"=>"email","max_length"=>100,"min_length"=>5,"blank"=>false));
      $this->message = $form->TextField(array("name"=>"message"));
    }
  }
}
