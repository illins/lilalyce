<?php

namespace Wp {

  require_once("apps/wp/config.php");
  require_once("apps/wapo/model.php");
  require_once("apps/wapo/helper.php");
  require_once("apps/wp/form.php");

  use Wapo\PromotionCategory;
  use Wapo\Promotion;
  use Wapo\Distributor;
  use Wapo\Profile;
  use Wapo\Wapo;
  use Wapo\WapoRecipient;
  use Wapo\Contact;

  /**
   * Given a selected module, and an initial definition, return the pipeline for
   * the given module.
   * @param \Blink\Request $request
   * @throws \Exception
   * @return array
   */
  function definition($request) {
    /*****Validate data********************************************************/
    // Check that the request is an instance of \Blink\Request class.
    if (!($request instanceof \Blink\Request)) {
      \Blink\raise500("Invalid Request Object.");
    }

    /*     * ***'Negotiate' what module has been selected.************************** */

    // Decide what module we're in. First see if we have a module selected in
    // the cookie.
    $module_id = $request->cookie->find("module_id", null);

    // Get the current step variable (current_step) is not defined at this point.
    $step = $request->get->find("step", "modules");

    // If we are in the modules step, check for module_id submission.
    if ($step == "modules") {
      $module_id = $request->post->find("module_id", $module_id);
    }

    // Get the module object.
    $module = null;
    if ($module_id) {
      $module = \Wapo\Module::get_or_404(array("id" => $module_id), "Module not found.");
    } else {
      $module = \Wapo\Module::get_or_404(array("tag" => "gift"), "Module not found.");
    }

    /*     * ***Determine the definition list*************************************** */

    // MODULES STEP.
    // First step is always the modules.
    $definition = array(
        "modules" => array(
            "title" => "Modules",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/modules.twig"),
            "form" => "\Wp\WapoModuleForm"
        )
    );

    // PROFILE STEP.
    
    // If the current step is the profile and this is a post request.
    if ($step == "profile" && $request->method == "post") {
      // If we are logged in and we are posting a name, then this is probably a 
      // new profile, so get the new profile step. Otherwise get the profiles 
      // step.
      if ($request->user && $request->post->find("name")) {
        $definition["new_profile"] = array(
            "title" => "New Profile",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/new_profile.twig"),
            "form" => "\Wp\NewProfileForm"
        );
      } else if ($request->user) {
        // If they have profiles, give them the list, otherwise they create a new one here...
        if (Profile::exists(array("wapo_distributor.user" => $request->user))) {
          $definition["profiles"] = array(
              "title" => "Profile",
              "template" => ConfigTemplate::DefaultTemplate("pipeline/profiles.twig"),
              "form" => "\Wp\ProfilesForm"
          );
        } else {
          $definition["new_profile"] = array(
              "title" => "New Profile",
              "template" => ConfigTemplate::DefaultTemplate("pipeline/new_profile.twig"),
              "form" => "\Wp\NewProfileForm"
          );
        }
      } else {
        // If not logged in, then they are creating a new profile.
        $definition["new_profile"] = array(
            "title" => "New Profile",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/new_profile.twig"),
            "form" => "\Wp\NewProfileForm"
        );
      }
    } else {
      // If they are logged in, then we either show their profiles (if any) or 
      // show the new profile page.
      if ($request->user) {
        if (Profile::exists(array("wapo_distributor.user" => $request->user))) {
          $definition["profiles"] = array(
              "title" => "Profile",
              "template" => ConfigTemplate::DefaultTemplate("pipeline/profiles.twig"),
              "form" => "\Wp\ProfilesForm"
          );
        } else {
          $definition["new_profile"] = array(
              "title" => "New Profile",
              "template" => ConfigTemplate::DefaultTemplate("pipeline/new_profile.twig"),
              "form" => "\Wp\NewProfileForm"
          );
        }
      } else {// If not logged in, then they are creating a new profile.
        $definition["new_profile"] = array(
            "title" => "New Profile",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/new_profile.twig"),
            "form" => "\Wp\NewProfileForm"
        );
      }
    }

    // MARKETPLACE STEP.

    // Skip marketplace if this is an announcement.
    $promotioncategory = null;
    if (!in_array($module->tag, array("announcement"))) {
      $definition["marketplace"] = array(
          "title" => "Marketplace",
          "template" => ConfigTemplate::DefaultTemplate("pipeline/marketplace.twig"),
          "form" => "\Blink\Form"
      );

      /**
       * Marketplace is now driven by promotion category.
       * The promotion category, like Wapo, 'Tango Card', 'Scallable Press', 'ifeelgoods',
       * determines which marketplace is displayed (via include in the template).
       * Also, the promotion category determines which form is being used.
       */
      $promotioncategory_id = $request->get->find("promotioncategory_id", null);
      if (!$promotioncategory_id) {
        $promotioncategory_id = $request->cookie->find("promotioncategory_id", null);
      }

      // If none is set, use the default Wapo, otherwise get the requested promotion.
      if (!$promotioncategory_id) {
        $promotioncategory = PromotionCategory::get_or_404(array("name" => "Wapo"), "Promotion Category not found.");
      } else {
        $promotioncategory = PromotionCategory::get_or_404(array("id" => $promotioncategory_id), "Promotion Category not found.");
      }

      // Now that we know which marketplace we are looking at, determine the form and other additional info.
      // Wapo is the default form.
      if ($promotioncategory->name == "Tango Card") {
        $definition['marketplace']['form'] = "\Wp\TangoCardMarketplaceForm";
        $promotion = Promotion::get_or_404(array("promotioncategory" => $promotioncategory), "Tango not configured correctly.");
      } else if ($promotioncategory->name == "I Feel Goods") {
        $definition['marketplace']['form'] = "\Wp\IfgMarketplaceForm";
        $promotion = Promotion::get_or_404(array("promotioncategory" => $promotioncategory), "IfeelGoods not configured correctly.");
      } else if ($promotioncategory->name == "Scalable Press") {
        $definition['scalable'] = array(
            "title" => "Scalable Press",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/marketplace/scalable.twig"),
            "form" => "\Wp\ScalableMarketplaceForm"
        );
        $definition["garment-pick"] = array(
            "title" => "Garments",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/garment-pick.twig"),
            "form" => "\Wp\GarmentPickForm"
        );
        $definition["garment-quote"] = array(
            "title" => "Garments",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/garment-quote.twig"),
            "form" => "\Wp\GarmentQuoteForm"
        );
        $definition['marketplace']['form'] = "\Wp\ScalableMarketplaceForm";
      } else {
        $definition['marketplace']['form'] = "\Wp\WapoMarketplaceForm";
      }
    }
    
    // MESSAGE STEP.
    if ($module->tag == "announcement") {
      $definition["announcement"] = array(
          "title" => "Announcement",
          "template" => ConfigTemplate::DefaultTemplate("pipeline/announcement.twig"),
          "form" => "\Wp\WapoAnnouncementForm"
      );
    }
    
    

    // DELIVERY STEP.
    
    // Set the main delivery page.
    $definition["delivery"] = array(
        "title" => "Delivery",
        "template" => ConfigTemplate::DefaultTemplate("pipeline/delivery.twig"),
        "form" => "\Blink\Form"
    );
    
    // If announcement, just output a blank form.
    if ($module->tag == "1announcement") {
      $delivery = $request->cookie->find("delivery", null);
      $delivery = $request->post->find("delivery", $delivery);

      

      // Define the conditional steps based on the delivery method.
      if ($delivery == "ffa") {
        $definition["ffa"] = array(
            "title" => "Free For All",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/free_for_all.twig"),
            "form" => "\Wp\FreeForAllForm"
        );
      } else if ($delivery == "e") {
        $definition["e"] = array(
            "title" => "Email",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/email.twig"),
            "form" => "\Blink\Form"
        );
      } else if ($delivery == "text") {
        $definition["text"] = array(
            "title" => "Text",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/text.twig"),
            "form" => "\Wp\TextForm"
        );
      } else if ($delivery == "el") {
        $definition["el"] = array(
            "title" => "Email List",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/email_list.twig"),
            "form" => "\Wp\EmailListForm"
        );
      } else if ($delivery == "mailchimp") {
        $definition["mailchimp"] = array(
            "title" => "MailChimp",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/mailchimp.twig"),
            "form" => "\Wp\MailChimpForm"
        );
      } else if ($delivery == "aff") {
        $definition["aff"] = array(
            "title" => "Any Facebook Friend",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/any_facebook_friends.twig"),
            "form" => "\Wp\AnyFacebookFriendsForm"
        );
      } else if ($delivery == "sff") {
        $definition["sff"] = array(
            "title" => "Facebook Friends",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/select_facebook_friends.twig"),
            "form" => "\Wp\FacebookFriendsForm"
        );
      } else if ($delivery == "fp") {
        $definition["fp"] = array(
            "title" => "Facebook Page Followers",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/facebook_page.twig"),
            "form" => "\Wp\FacebookPageForm"
        );
      } else if ($delivery == "atf") {
        $definition["atf"] = array(
            "title" => "Any Twitter Followers",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/any_twitter_followers.twig"),
            "form" => "\Wp\GenericQuantityForm"
        );
      } else if ($delivery == "stf") {
        $definition["stf"] = array(
            "title" => "Select Twitter Followers",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/select_twitter_followers.twig"),
            "form" => "\Wp\SelectTwitterFollowersForm"
        );
      } else if ($delivery == "aif") {
        $definition["aif"] = array(
            "title" => "Any Instagram Followers",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/any_instagram_followers.twig"),
            "form" => "\Wp\GenericQuantityForm"
        );
      } else if ($delivery == "sif") {
        $definition["sif"] = array(
            "title" => "Select Instagram Followers",
            "template" => ConfigTemplate::DefaultTemplate("pipeline/select_instagram_followers.twig"),
            "form" => "\Wp\SelectInstagramFollowersForm"
        );
      }
    }
    
    // DETAILS STEP.
    $definition["details"] = array(
        "title" => "Details",
        "template" => ConfigTemplate::DefaultTemplate("pipeline/details.twig"),
        "form" => "\Wp\DetailsForm"
    );

    // CHECKOUT STEP.
    
    // Checkout (ready to go pay).
    $definition["checkout"] = array(
        "title" => "Checkout",
        "template" => ConfigTemplate::DefaultTemplate("pipeline/checkout.twig"),
        "form" => "Wp\PaymentMethodForm"
    );
    
    // If this is an announcement, then we put a blank form.
    if($module->tag == "announcement") {
      $definition['checkout']['form'] = "\Blink\Form";
    }

    // Create step (once they have paid).
    $definition["create"] = array(
        "title" => "Create",
        "template" => ConfigTemplate::DefaultTemplate("pipeline/create.twig"),
        "form" => "Blink\Form"
    );

    $delivery = $request->cookie->find("delivery", "");
    if (!in_array($delivery, array("ffa"))) {
      // Send step.
      $definition["send"] = array(
          "title" => "Send",
          "template" => ConfigTemplate::DefaultTemplate("pipeline/send.twig"),
          "form" => "Blink\Form"
      );
    }

    // Add the done step.
    $definition["done"] = array(
        "title" => "Confirmation",
        "template" => ConfigTemplate::DefaultTemplate("pipeline/confirmation.twig"),
        "form" => "Blink\Form"
    );

    return array($definition, $promotioncategory, $module);
  }

}
