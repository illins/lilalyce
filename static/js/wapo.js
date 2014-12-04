var wepay_iframe_dialog = null;

function wapo_pay(url) {
  $.ajax({
    url: url
  }).done(function(data) {
    if (data['result'] === "yes") {
      $("#checkout").dialog({
        width: 650,
        height: 550,
        position: "center top",
        show: {
          effect: "blind",
          duration: 1000
        },
        hide: {
          effect: "explode",
          duration: 1000
        },
        open: function(event, ui) {
          wepay_iframe_dialog = setInterval(function() {
            if (parseInt($("#dialog").height()) !== parseInt($("#wepay_checkout_iframe").contents().height())) {
              $("#dialog").width($("#wepay_checkout_iframe").contents().width());
              $("#dialog").height($("#wepay_checkout_iframe").contents().height());
            }
          }, 1000);
        },
        close: function(event, ui) {
          clearInterval(wepay_iframe_dialog);
        }
      });
      
      WePay.iframe_checkout("checkout", data['checkout_uri']);
      //$("#wepay_checkout_iframe").height(500);
    } else {
      alert('Error occured processing payment');
    }
  });
}

















//
//
//
//
//var logged_in = false;
//
//var promotion = new Object();
//promotion.id = 0;
//promotion.marketplace = false;
//promotion.delivery_method = null;
//promotion.contact_id = null;
//promotion.email_list = new Array();
//promotion.phone_list = new Array();
//
//promotion.sender_method = null;
//promotion.sender = null;
//
//
//var brand = {first_name:"",last_name:"",email:"",message:""};
//var login = {loggedin:"",account:"local"};
//var PromotionId = 0;
//
//// Display the tooltip.
//$(document).tooltip();
//
//$(function() {
//  WpIsLoggedin();
//  
//  // If promotion is set, skip the first tab.
//  if(promotion.id) {
//    promotion.marketplace = true;
//    
//    WpPromotionShowPreview();// Show the promotion.
//    
//    $('#promotion a[href="#delivery-method"]').tab('show');// Skip promotion tab.
//    
////    // If logged in, load the delivery lists the user might have.
////    if(login.loggedin) {
////      WpDeliveryProfileList();
////    } else {
////      WpPromotionDeliveryLogin();
////    }
//    $('#delivery-method-options-email').attr("checked", true);
//  } else {
//    // Load the promotion list.
//    WpPromotionLoadList("/wapo/promotion/marketplace/");
//
//    // Show the first tab.
//    $('#promotion a[href="#promotion-list"]').tab('show');
//  }
//});
//
//// Show the login information for them to log in.
//function WpPromotionDeliveryLogin() {
//  $.ajax({
//    url: "/wapo/promotion/login/",
//    success: function(data) {
//      $('#promotion-delivery-method-contact-list').html(data);
//    },
//    error: function(data) {
//      $('#promotion-delivery-method-contact-list').html("");
//    }
//  });
//}
//
//function WpPromotionDeliveryLoginAjax() {
//  $.ajax({
//    url: "/user/login/ajax/",
//    async: false,
//    method: "post",
//    data : {username:$("#delivery-login-username").val(),password:$("#delivery-login-password").val()},
//    success: function(data) {
//      if(_.isObject(data)) {
//        // Do something here.
//        WpDeliveryProfileList();
//        $("#primary-delivery-login-modal").dialog('close');
//      } else {
//        $('#primary-delivery-login-modal').html(data);
//      }
//    },
//    error: function(data) {
//      $('#primary-delivery-login-modal').html("");
//    }
//  });
//}
//
//function WpPromotionProfileLoginAjax() {
//  $.ajax({
//    url: "/user/login/ajax/",
//    async: false,
//    method: "post",
//    data : {username:$("#profile-login-username").val(),password:$("#profile-login-password").val()},
//    success: function(data) {
//      if(_.isObject(data)) {
//        // Do something here.
//        WpDeliveryProfileList();
//        $("#profile-profile-login-modal").dialog('close');
//      } else {
//        $('#profile-profile-login-modal-message').html("Account not found. Please try again.");
//        $('#profile-profile-login-modal-message').show();
//      }
//    },
//    error: function(data) {
//      $('#profile-profile-login-modal').html("");
//    }
//  });
//}
//
//// Show the login information for them to log in.
//function WpPromotionProfileLogin() {
//  $.ajax({
//    url: "/wapo/promotion/profile/login/",
//    success: function(data) {
//      $('#promotion-profile-profile-list').html(data);
//    },
//    error: function(data) {
//      $('#promotion-profile-profile-list').html("");
//    }
//  });
//}
//
//function WpProfileProfileList() {
//  if(WpIsLoggedin()) {
//    $.ajax({
//      url: "/wapo/promotion/profile/list/",
//      success: function(data) {
//        $('#promotion-profile-profile-list').html(data);
//      },
//      error: function(data) {
//        $('#promotion-profile-profile-list').html("Profiles not found.");
//      }
//    });
//  }
//}
//
////"/wapo/promotion/marketplace/"
//function WpPromotionLoadList(url) {
//  $.ajax({
//    url: url,
//    success: function(data) {
//      $('#promotion-list').html(data);
//    },
//    error: function(data) {
//      $('#promotion-list').html("Promotion not found.");
//    }
//  });
//}
//
//function WpPromotionDelivery(promotion_id) {
//  promotion.id = promotion_id;
//  WpPromotionShowPreview();// Show the promotion.
//  $('#promotion a[href="#delivery-method"]').tab('show');
//  $('#delivery-method-options-email').attr("checked", true);
//  
//  // If logged in, load the delivery lists the user might have.
//  WpDeliveryProfileList();
//}
//
//// Get the promotion to display in the preview window.
//function WpPromotionShowPreview() {
//  $.get("/wapo/promotion/preview/", {promotion_id: promotion.id})
//  .done(function(data) {
//    $("#promotion-preview").html(data);
//  })
//  .fail(function() {
//    $("#promotion-preview").html("Promotion not found.");
//  });
//}
//
//function WpIsLoggedin() {
//  $.ajax({
//    url: "/user/loggedin/",
//    async: false,
//    dataType: "json",
//    success: function(data) {
//      if(data.loggedin === "yes") {
//        login.loggedin = "yes";
//        login.account = data.account;
//      } else {
//        login.loggedin = "";
//        login.account = "";
//      }
//    },
//    error: function(data) {
//      alert('promotion product not found.');
//    }
//  });
//  return login.loggedin;
//}
//
//function WpDeliveryProfileList() {
//  if(WpIsLoggedin()) {
//    $.ajax({
//      url: "/wapo/promotion/profile/list/",
//      success: function(data) {
//        $('#promotion-delivery-method-contact-list').html(data);
//      },
//      error: function(data) {
//        $('#promotion-delivery-method-contact-list').html("Profiles not found.");
//      }
//    });
//  }
//}
//
//function WpDeliveryContactList(url) {
//  if(WpIsLoggedin()) {
//    $.ajax({
//      url: url,
//      success: function(data) {
//        $('#promotion-delivery-method-contact-list').html(data);
//      },
//      error: function(data) {
//        $('#promotion-delivery-method-contact-list').html("Profiles not found.");
//      }
//    });
//  }
//}
//
//function WpPromotionDeliveryContactList(contact_id) {
//  promotion.delivery_method = "list";
//  promotion.contact_id = contact_id;
//  $('#promotion a[href="#profile"]').tab('show');
//  
//  $.ajax({
//    url: "/wapo/promotion/profile/selected/",
//    method: "get",
//    data: {contact_id:contact_id},
//    success: function(data) {
//      $('#profile').html(data);
//    },
//    error: function(data) {
//      $('#profile').html('Profile not found.');
//    }
//  });
//}
//
//function WpDeliveryMethodShow(id) {
//  //$('#promotion-delivery-method-contact-list').hide();
//  $('#promotion-delivery-method-email').hide();
//  $('#promotion-delivery-method-text').hide();
//  $('#promotion-delivery-method-facebook').hide();
//  $('#promotion-delivery-method-twitter').hide();
//  $('#' + id).show();
//}
//
//function WpDeliveryEmail() {
//  if(promotion.delivery_method) {
//    alert('Another delivery method already declared.');
//    return;
//  }
//  
//  var recipient_email_list = new Array();
//  for(var i = 1; i <= 3; i++) {
//    var name = _.template("#recipient-email-name-<%= count %>", {count:i});
//    var email = _.template("#recipient-email-email-<%= count %>", {count:i});
//    
//    if($(name).val() || $(email).val()) {
//      if($(name).val() === "") {
//        alert('Please enter name ' + 1);
//        return;
//      } else if($(email).val() === "") {
//        alert('Please enter email ' + 1);
//        return;
//      }
//      
//      var myemail = new Array();
//      myemail['name'] = $(name).val();
//      myemail['email'] = $(email).val();
//      recipient_email_list.push(myemail);
//    }
//    
//    if(recipient_email_list.length === 0) {
//      alert("Please enter recipient names and emails.");
//      return;
//    }
//    
//    promotion.delivery_method = "email";
//    promotion.email_list = recipient_email_list;
//  }
//  
//  WpProfileSetup();
//}
//
//function WpDeliveryText() {
//  if(promotion.delivery_method) {
//    alert('Another delivery method already declared.');
//    return;
//  }
//  
//  var recipient_phone_list = new Array();
//  for(var i = 1; i <= 3; i++) {
//    var name = _.template("#recipient-text-name-<%= count %>", {count:i});
//    var phone = _.template("#recipient-text-phone-<%= count %>", {count:i});
//    
//    if($(name).val() || $(phone).val()) {
//      if($(name).val() === "") {
//        alert('Please enter name ' + 1);
//        return;
//      } else if($(phone).val() === "") {
//        alert('Please enter phone ' + 1);
//        return;
//      }
//      
//      var myphone = new Array();
//      myphone['name'] = $(name).val();
//      myphone['phone'] = $(phone).val();
//      recipient_phone_list.push(myphone);
//    }
//    
//    if(recipient_phone_list.length === 0) {
//      alert("Please enter recipient names and phone.");
//      return;
//    }
//    
//    promotion.delivery_method = "text";
//    promotion.phone_list = recipient_phone_list;
//  }
//  
//  WpProfileSetup();
//}
//
//function WpProfileSetup() {
//  $.ajax({
//    url: "/wapo/promotion/profile/",
//    success: function(data) {
//      $('#profile').html(data);
//    },
//    error: function() {
//      $('#profile').html("Error.");
//    }
//  });
//  
//  $('#promotion a[href="#profile"]').tab('show');
//}
//
//function WpRecipientClear() {
//  promotion.recipient_method = null;
//  promotion.recipient = null;
//}
//
//function WpPromotionProfile(profile_id) {
//  $.ajax({
//    url: "/wapo/promotion/checkout/",
//    data: {profile_id:profile_id,promotion_id:promotion.id},
//    success: function(data) {
//      $('#checkout').html(data);
//    },
//    error: function() {
//      $('#checkout').html("Error.");
//    }
//  });
//  
//  if(promotion.delivery_method === "contact-list") {
//    WpCheckoutDeliveryMethodList();
//  }
//  
//  $('#promotion a[href="#checkout"]').tab('show');
//  $('#promotion-preview').html('');
//}
//
//function WpCheckoutDeliveryMethodList() {
//  $.ajax({
//    url: "/wapo/promotion/checkout/contact/",
//    data: {contact_id:promotion.contact_id},
//    success: function(data) {
//      $('#promotion-checkout-delivery').html(data);
//    },
//    error: function() {
//      $('#promotion-checkout-delivery').html("Error.");
//    }
//  });
//}
//
//function WpSenderBasicInfo() {
//  var name = "#sender-basic-name";
//  var email = "#sender-basic-email";
//  var password = "#sender-basic-password";
//  var password_confirm = "#sender-basic-password-confirm";
//  
//  if ($(name).val() === "") {
//    alert('Please enter your name ');
//    return;
//  } else if ($(name).val() === "") {
//    alert('Please enter your email ' + 1);
//    return;
//  }
//
//  var basic = new Array();
//  basic['name'] = $(name).val();
//  basic['email'] = $(email).val();
//  
//  if($('#recipient-basic-create-account').is(":checked")) {
//    if($(password).val() !== $(password_confirm).val()) {
//      alert("Your entered passwords do not match.");
//      return;
//    }
//    
//    basic['password'] = $(password).val();
//    basic['password_confirm'] = $(password_confirm).val();
//  }
//
//  promotion.sender_method = "basic";
//  promotion.sender = basic;  
//  
//  $('#mypromotion li:eq(3) a').tab('show');
//}
//
//function WpSenderBasicShowPassword() {
//  $("#form-group-recipient-basic-password").show();
//  $("#form-group-recipient-basic-password-confirm").show();
//}
//
//function WpRecipientList(id) {
//  promotion.recipient_method = "contact-list";
//  promotion.recipient = id;
//}
//
//function WpFacebookPostToWall() {
//  var params = {};
//  params['message'] = 'Message';
//  params['name'] = 'Name';
//  params['description'] = 'Description';
//  params['link'] = 'http://apps.facebook.com/summer-mourning/';
//  params['picture'] = 'http://summer-mourning.zoocha.com/uploads/thumb.png';
//  params['caption'] = 'Caption';
//
//  FB.api('/me/feed', 'post', params, function(response) {
//    if (!response || response.error) {
//      alert('Error occured');
//    } else {
//      alert('Published to stream - you might want to delete it now!');
//    }
//  });
//}
//
//function WpLogin() {
//  var myusername = $('#promotion-login-username').val();
//  var mypassword = $('#promotion-login-password').val();
//  
//  var mydata = {username:myusername, password:mypassword};
//  
//  $.ajax({
//    url: "/user/login/ajax/",
//    data: mydata,
//    success: function(data) {
//      if(data === 1) {
//        
//      } else {
//        alert('Please log in again.');
//      }
//    },
//    error: function(data) {
//      alert('promotion product not found.');
//    }
//  });
//}
//
//function WpDeliveryMethodLists(myurl) {
//  myurl = (_.isNull(myurl)) ? "/wapo/delivery-method/lists/?page=1" : myurl;
//  
//  $.ajax({
//    url: myurl,
//    data: mydata,
//    success: function(data) {
//      $("#delivery-method-list").html(data);
//    },
//    error: function(data) {
//      $("#delivery-method-list").html("Data not found.");
//    }
//  });
//}
//
//function WpProfileLists(myurl) {
//  myurl = (_.isNull(myurl)) ? "/wapo/profile/lists/?page=1" : myurl;
//  
//  $.ajax({
//    url: myurl,
//    success: function(data) {
//      $("#profile-list").html(data);
//    },
//    error: function(data) {
//      $("#profile-list").html("Profiles not found.");
//    }
//  });
//}
//
//function WpProfileCreateProfile(myurl) {
//  myurl = (_.isNull(myurl)) ? "/wapo/profile/lists/?page=1" : myurl;
//  
//  var first_name = $("#profile-first-name").val();
//  var last_name = $("#profile-last-name").val();
//  var stret = $("#profile-street").val();
//  var city = $("#profile-street").val();
//  var state = $("#profile-street").val();
//  var zip_code = $("#profile-street").val();
//  
//  var email = $("#profile-email").val();
//  var password = $("#profile-password").val();
//  var confirm_password = $("#profile-confirm-password").val();
//  
//  mydata = {};
//  $.ajax({
//    url: myurl,
//    data: mydata,
//    success: function(data) {
//      $("#profile-list").html(data);
//    },
//    error: function(data) {
//      $("#profile-list").html("Profiles not found.");
//    }
//  });
//}
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//var promotion_select_id;
//function setPromotion(id) {
//  promotion_select_id = id;
//  $("#customize_button").show();
//}
//
//function customizePromotion() {
//  document.location = '/wapo/promotion/customize/' + promotion_select_id + '/';
//}
//
//function WpInit() {
//  WpIsLoggedin();
//  WpIsLoggedin();
//  
//  // If set, then we have a promotion product.
//  if (promotion.id) {
//    $('#select-button').removeClass('btn-primary').addClass('btn-default');
//    $('#select').hide('slow');
//    $('#brand').show('slow');
//    $('#brand-button').removeClass('btn-default').addClass('btn-primary');
//
//    $.ajax({
//      url: "/wapo/promotion/customize/promotionproduct/" + promotion.id + "/",
//      success : function(data) {
//        $('#promotion').html(data);
//      },
//      error: function(data) {
//        alert('promotion product not found.');
//      }
//    });
//        
//    if(WpIsLoggedin()) {
//      console.log('yest');
//      $('#authentication').hide();
//      $('#brand-info').show();
//    } else {
//      $('#brand-info').hide();
//      $('#authentication').show();
//      console.log('nooo');
//    }
//  } else {/*If not,we display the promotions, then we disable the next button.*/
//    $.ajax({
//      url: "/wapo/promotion/customize/promotion/",
//      success : function(data) {
//        $('#select').html(data);
//      },
//      error: function(data) {
//        alert('promotion product not found.');
//      }
//    });
//    
//    $('#previous').hide();
//    $('#next').addClass('disabled');
//  }
//  
//  WpInitAuthentication();   
//}
//
//function WpInitAuthentication() {
//  /*Hide the forms*/
//  $("#create-account")
//          .button()
//          .click(function() {
//            //$("#login-user-form").dialog("close");
//            $("#create-account-form").dialog("open");
//          });
//          
//   $("#login-user")
//          .button()
//          .click(function() {
//            //$("#create-account-form").dialog("close");
//            $("#login-user-form").dialog("open");
//          });
//          
//   /*Hide the forms*/
//   $("#create-account-form").hide();
//   $("#login-user-form").hide();
//   
//   $("#create-account-form").dialog({
//    autoOpen: false,
//    height: 300,
//    width: 350,
//    modal: true,
//    buttons: {
//      "Create an account": function() {
//        // Do something here to create account.
//      },
//      Cancel: function() {
//        $(this).dialog("close");
//      }
//    },
//    close: function() {
//      //allFields.val("").removeClass("ui-state-error");
//    }
//  });
//  
//  $("#login-user-form").dialog({
//    autoOpen: false,
//    height: 300,
//    width: 350,
//    modal: true,
//    buttons: {
//      "Login": function() {
//        // Do something here to create account.
//      },
//      Cancel: function() {
//        $(this).dialog("close");
//      }
//    },
//    close: function() {
//      //allFields.val("").removeClass("ui-state-error");
//    }
//  });
//}
//
//function WpSelectPromotion(id) {
//  promotion.id = id;
//  promotion.promotionproduct = false;
//  
//  $.ajax({
//    url: "/wapo/promotion/customize/promotion/" + promotion.id + "/",
//    success: function(data) {
//      $('#promotion').html(data);
//    },
//    error: function(data) {
//      alert('promotion product not found.');
//    }
//  });
//  
//  $('#next').removeClass('disabled');
//  $('#next').click(function() {
//    WpBrand();
//  });
//}
//
//function WpBrand() {
//  $('#select-button').removeClass('btn-primary').addClass('btn-default');
//  $('#select').hide('slow');
//  $('#brand').show('slow');
//  $('#brand-button').removeClass('btn-default').addClass('btn-primary');
//
//  $('#previous').show();
//  $('#previous').removeClass('disabled');
//  
//  if(WpIsLoggedin()) {
////    if(login.account === "local") {
////      $('#login-local-account_tab').show();
////      $('#login-local-account').show();
////      $('#login-facebook-tab').hide();
////      $('#login-facebook').hide();
////    } else if(login.account === "facebook") {
////      $('#login-facebook-tab').show();
////      $('#login-facebook').show();
////      $('#login-local-account_tab').hide();
////      $('#login-local-account').hide();
////    }
////    
////    $('#login-local-account_tab').unbind('click');
////    $('#login-facebook-tab').unbind('click');
//
//    $('#authentication').hide();
//    $('#brand-info').show();
//    WpBrandGetInfo();
//  } else {
//    $('#authentication').show();
//    $('#brand-info').hide();
//    
//    $('#login-local-account_tab').click(function() {
//      WpBrandLocalAccount();
//    });
//    $('#login-facebook-tab').click(function() {
//      WpBrandFacebook();
//    });
//    WpBrandLocalAccount();
//  }
//}
//
//function WpBrandLocalAccount() {
//  $('#login-local-account_tab').addClass("active");
//  $('#login-local-account').show();
//  $('#login-facebook-tab').removeClass("active");
//  $('#login-facebook').hide();
//}
//
//function WpBrandFacebook() {
//  $('#login-facebook-tab').addClass("active");
//  $('#login-facebook').show();
//  $('#login-local-account_tab').removeClass("active");
//  $('#login-local-account').hide();
//}
//
//function WpBrandGetInfo() {
//  $.ajax({
//    url: "/wapo/promotion/customize/brand/getinfo/",
//    dataType: "json",
//    success: function(data) {
//      $('#name').val(data.company.company);
//    },
//    error: function(data) {
//      alert('Error getting info.');
//    }
//  });
//}