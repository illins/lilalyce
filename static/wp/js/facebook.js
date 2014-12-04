var friend_list = [];
var page_list = [];
var selected_friend_list = new Array();
var facebook_delivery_method_interval = null;
var facebook_delivery_method_interval = null;
var facebook_profile_interval = null;
var timeout = null;
var friends = "";
var facebook_page_id = "";
var facebook_id = "";

function facebook_show_friends(response) {
  friend_list = ($("#facebook_friends").val()) ? $("#facebook_friends").val().split(",") : [];

  $("#facebook-friend-list").html('');
  var template = _.template($("#template_facebook_friend").html());
  _.map(response.data, function(friend) {
    var checked = (friend_list.indexOf(String(response.data[i].id)) !== -1) ? 'checked' : '';
    $("#facebook-friend-list").append(template({fb_id: friend.id, photo_url: friend.picture.data.url, name: friend.name, checked: checked}));
  });

  // If any of the list is clicked, add or remove it.
  $(".facebook_friend").click(function() {
    var fb_id = $(this).attr('data-fbid');// Get the Facebook id.

    // If it is not in the list add it, otherwise remove it.
    if (friend_list.indexOf(fb_id) === -1) {
      friend_list.push(fb_id);
    } else {
      friend_list.splice(friend_list.indexOf(fb_id), 1);
    }

    // Reset the friends variable.
    $("#facebook_friends").val(friend_list.join(","));
  });
}
    
// Load the facebook friends.
function facebook_friends() {
  //"100007367021324"
  
  FB.getLoginStatus(function(response) {
    if (response.status === "connected") {
      FB.api('me/friends', {fields: "id,name,picture"}, function(response) {
        facebook_show_friends(response)
      });
    } else {
      $("#facebook-friend-list").html('<a class="btn btn-primary" href="/user/login/facebook/?return=/wp/ssf/">Login With Facebook</a>');
    }
  });
}

// Load the user's pages.
function facebook_pages() {
  FB.getLoginStatus(function(response) {
    if (response.status === "connected") {
      FB.api("/me/accounts", {fields: "id,name,picture"}, function(response) {
        if (response && !response.error) {

          $("#facebook-page-list").html('');
          var template = _.template($("#template_facebook_page").html());
          _.map(response.data, function(page) {
            var checked = (facebook_page_id === String(page.id)) ? 'checked' : '';
            $("#facebook-page-list").append(template({id: page.id, photo_url: page.picture.data.url, name: page.name, checked: checked}));
          });
        }
        
        facebook_fp_check_permission();
      });
    } else {
      $("#next_button").addClass("disabled");
      $("#facebook-page-list").html('<button class="btn btn-primary" id="facebook_login_button">Login With Facebook</button>');
        $("#facebook_login_button").click(function(e) {
          e.preventDefault();
          facebook_login({return_url: "/wp/fp/", scope: "publish_stream,publish_actions,manage_pages"});
        });
    }
  });
}

// Check if user is logged in and enable the login button.
function facebook_is_logged_in() {
  FB.getLoginStatus(function(response) {
    if (response.status === "connected") {
      FB.api('/me', {fields: "id,name,picture"}, function(user) {
        $("#next_button").removeClass("disabled");
        $("#facebook-login").html(_.template($("#template_facebook_user").html(), {name: user.name, photo_url: user.picture.data.url}));
        $("#facebook_id").val(user.id);
        facebook_aff_check_permission();
      });
    } else {
      $("#next_button").addClass("disabled");
//      $("#facebook-login").html('<a class="btn btn-primary" href="/user/login/facebook/?return=/wp/aff/">Login With Facebook</a>');
        $("#facebook-login").html('<button class="btn btn-primary" id="facebook_login_button">Login With Facebook</button>');
        $("#facebook_login_button").click(function(e) {
          e.preventDefault();
          facebook_login({return_url: "/wp/aff/"});
        });
    }
  });
}

// Post to user's wall or a page they created.
function facebook_post(message, feed) {
  FB.login(function() {
    FB.api(feed, 'POST', {message: message}, function(results) {
      // Update the Wapo with the post's id.
      $.ajax({
        url: '/wp/facebook/resource/',
        data: {resource: results.id},
        type: 'GET',
        dataType: 'json'
      }).done(function(data) {
        $("#next_button").click();
      });
    });
  }, {scope: 'publish_actions,manage_pages'});
}

// Send the Wapo.
function send_promotion() {
  $.ajax({
    url: '/wp/sendwapo/',
    type: 'GET',
    dataType: 'json'
  }).done(function(data) {
    if (data.error) {
      $("#error").removeClass('hidden').html(data.message);
      $("#loading").hide();
    } else {
      if (data.delivery === 'e') {
        $("#next_button").click();
      } else if (data.delivery === 'el') {
        $("#next_button").click();
      } else if (data.delivery === 'mailchimp') {
        $("#next_button").click();
      } else if (data.delivery === 'aff') {
        var feed = '/me/feed/';
        facebook_post(data.message, feed);
      } else if (data.delivery === 'fp') {
        var feed = '/' + data.facebook_page_id + '/feed/';
        facebook_post(data.message, feed);
      } else if(data.delivery === 'atf') {
        $("#next_button").click();
      } else if(data.delivery === 'stf') {
        $("#next_button").click();
      }
    }
  });
}

// Check if the user likes the output page.
// https://developers.facebook.com/docs/graph-api/reference/v2.0/user/likes
function facebook_page_likes() {
  // Check login.
  FB.getLoginStatus(function(response) {
    if (response.status === "connected") {
      FB.api("/me/likes/" + facebook_page_id, function(res) {
        // If object and no error, then they like this page.
        // res.data[0].created_time - time when user started following.
        if (res.data.length && !res.error) {
          $("#facebook_id").val(response.authResponse.userID);
          $("#next_button").click();
        } else {
          $("#facebook_page_like").html('Only individuals who have liked this page can download the Wapo.');
        }
      });
    } else {
      $("#next_button").addClass("disabled");
      $("#facebook_page_like").html('<button class="btn btn-primary" id="facebook_login_button">Login With Facebook</button>');
      $("#facebook_login_button").click(function(e) {
        e.preventDefault();
        facebook_login({return_url: "/wp/download/fp/", scope:"user_likes"});
      });
    }
  });
}

// Check if the user is a friend of the sender.
// https://developers.facebook.com/docs/graph-api/reference/v2.0/user/friends
function facebook_friend() {
  // Check login.
  FB.getLoginStatus(function(response) {
    if (response.status === "connected") {
      // Check that this user is friends with the friend that created this account.
      FB.api("/me/friends/" + facebook_id, function(res) {
        // If object and no error, then friendship exists.
        if (res && !res.error) {
          $("#facebook_id").val(response.authResponse.userID);
          $("#next_button").click();
        } else {
          $("#facebook_friend").html('Only Facebook Friends of ... can download the Wapo.');
        }
      });
    } else {
      // If not logged in, give them the options to log in.
      $("#next_button").addClass("disabled");
      $("#facebook_friend").html('<button class="btn btn-primary" id="facebook_login_button">Login With Facebook</button>');
      $("#facebook_login_button").click(function(e) {
        e.preventDefault();
        facebook_login({return_url: "/wp/download/aff/", scope:"user_friends"});
      });
    }
  });
}
//function facebook_search_friends() {
//  FB.getLoginStatus(function(response) {
//    if (response.status === "connected") {
//      
//      //var urlCall = "/search?q=" + keywords + "&type=page&access_token=";
//      FB.api('/search', {q: 'Live', type: 'user'}, function(res) {
//        console.log(res);
//      });
//    } else {
//      FB.login(function(response) {
//        $("#facebook_search").click();
//      }, {scope: 'friends_status,publish_stream,publish_actions'});
//    }
//  });
//}

function facebook_aff_check_permission() {
  FB.getLoginStatus(function(response) {
    if (response.status === "connected") {
      FB.api("/me/permissions", function(res) {
        
        // Set the required permissions.
        var required_permissions = ["public_profile", "publish_actions"];
        var permissions_string = "";
        var missing_permissions = [];

        // Put the current user's permissions in a string.
        _.map(res.data, function(permission) {
          if (permission.status === "granted") {
            permissions_string += permission.permission;
          }
        });
        
        // Check if all permissions are filled in.
        _.map(required_permissions, function(permission) {
          if (permissions_string.indexOf(permission) === -1) {
            missing_permissions.push(permission);
          }
        });
        
        if(missing_permissions.length) {
          $("#missing_facebook_permissions").removeClass('hidden');
          $("#missing_facebook_permissions").html('You are missing permissions required for this delivery method. Click <a id="facebook_request_permission" href="#">here</a> to request them.');
          $("#facebook_request_permission").click(facebook_aff_request_permission);
        }
      });
    }
  });
}

function facebook_aff_request_permission() {
  FB.login(function(response) {
    
  }, {scope: "public_profile,publish_actions"});
}


function facebook_fp_check_permission() {
  FB.getLoginStatus(function(response) {
    if (response.status === "connected") {
      FB.api("/me/permissions", function(res) {
        // Set the required permissions.
        var required_permissions = ["public_profile", "publish_actions","manage_pages"];
        var permissions_string = "";
        var missing_permissions = [];

        // Put the current user's permissions in a string.
        _.map(res.data, function(permission) {
          if (permission.status === "granted") {
            permissions_string += permission.permission;
          }
        });
        
        // Check if all permissions are filled in.
        _.map(required_permissions, function(permission) {
          if (permissions_string.indexOf(permission) === -1) {
            missing_permissions.push(permission);
          }
        });
        
        if(missing_permissions.length) {
          $("#missing_facebook_permissions").removeClass('hidden');
          $("#missing_facebook_permissions").html('You are missing permissions required for this delivery method. Click <a id="facebook_request_permission" href="#">here</a> to request them.');
          $("#facebook_request_permission").click(facebook_fp_request_permission);
        }
      });
    }
  });
}

function facebook_fp_request_permission() {
  FB.login(function(response) {
    
  }, {scope: "public_profile,publish_actions,manage_pages"});
}






































// Load friends if only they are logged in.
function LoadFriends() {
  FB.getLoginStatus(function(response) {
    if (response.status === "connected" && !friend_list.length) {
      FB.api('me/friends', {fields: "id,name,picture"}, function(response) {
        friend_list = response.data;
      });
    }
  });
}

function LoadFriendsInitial() {
  FB.api('me/friends', {fields: "id,name,picture"}, function(response) {
    friend_list = response.data;

    if (friend_list.length) {
      // If there is a friend already selected and there is a friend list, search for the friend and load their data.
      if ($('#facebook_id1').val()) {
        for (var i = 0; i < friend_list.length; i++) {
          if (friend_list[i].id === $('#facebook_id1').val()) {
            SetNameAndId(1, i);
          }
        }
      }

      if ($('#facebook_id2').val()) {
        for (var i = 0; i < friend_list.length; i++) {
          if (friend_list[i].id === $('#facebook_id3').val()) {
            SetNameAndId(2, i);
          }
        }
      }

      if ($('#facebook_id3').val()) {
        for (var i = 0; i < friend_list.length; i++) {
          if (friend_list[i].id === $('#facebook_id3').val()) {
            SetNameAndId(3, i);
          }
        }
      }
    }
  });
}

function SetNameAndId(id, index) {
  $("#user_result" + id).html("");
  $("#user" + id).val(friend_list[index].name);
  $("#facebook_id" + id).val(friend_list[index].id);
  $("#image" + id).attr('src', friend_list[index].picture.data.url);
  $("#image" + id).show();
}

function ClearNameAndId(id) {
  $("#user_result" + id).html("");
  $("#facebook_id" + id).val("");
}

function facebook_delivery_method_login() {
  var html = $("friend_list").html();
  FB.login(function(response) {
    if(response.authResponse) {
      facebook_delivery_method_friend_list();
    } else {
      $("#friend-list").html(html);
      $("#delivery-method-facebook-login-error").html("Error occured during log in. Make sure you logged in and allowed the app access to continue.");
      $("#delivery-method-facebook-login").html("Login with Facebook.");
    }
  }, {scope: 'friends_status,publish_stream,publish_actions'});
}

function facebook_delivery_method_friend_list() {
  FB.getLoginStatus(function(response) {
    if (response.status === "connected") {
      FB.api('me/friends', {fields: "id,name,picture"}, function(response) {
        friend_list = response.data;

        // If any previously selected friends, load them in selected_friend_list
        if ($("#facebook_ids").val()) {
          selected_friend_list = $("#facebook_ids").val().split(",");
        }

        // Load the friend list.
        var html = '<table class="table table-bordered">';

        // Load the friend list.
        for (var i = 0; i < friend_list.length; i++) {
          if (selected_friend_list.indexOf(String(friend_list[i].id)) >= 0) {
            html += '<tr>';
            html += '<td style="width: 30px;"><input type="checkbox" class="facebook-friend" value="' + friend_list[i].id + '" checked="checked" /></td>';
            html += '<td style="width: 50px;"><img class="img img-rounded" style="max-width: 30px; max-height: 30px;" src="' + friend_list[i].picture.data.url + '" alt="" /></td>';
            html += '<td>' + friend_list[i].name + '</td>';
            html += '</tr>';
          } else {
            html += '<tr>';
            html += '<td style="width: 30px;"><input type="checkbox" class="facebook-friend" value="' + friend_list[i].id + '" /></td>';
            html += '<td style="width: 50px;"><img class="img img-rounded" style="max-width: 30px; max-height: 30px;" src="' + friend_list[i].picture.data.url + '" alt="" /></td>';
            html += '<td>' + friend_list[i].name + '</td>';
            html += '</tr>';
          }
        }

        html += '</table>'

        $('#friend-list').html(html);

        $(".facebook-friend").click(function() {
          var checkbox = this;
          
          // If user already in list, remove them.
          if (selected_friend_list.indexOf(checkbox.value) >= 0) {
            selected_friend_list.splice(selected_friend_list.indexOf(checkbox.value), 1);
            checkbox.checked = false;
          } else {
            $.ajax({
              url: "/user/loggedin/",
              dataType: 'json'
            }).done(function(data) {
              if (data.loggedin === "yes") {
                selected_friend_list.push(checkbox.value);
                checkbox.checked = true;
                $("#facebook_ids").val(selected_friend_list.join());
              } else {
                checkbox.checked = false;
                if (selected_friend_list.length === 1) {
                  $("#one-facebook-friend-dialog").dialog({
                    resizable: false,
                    width: 400,
                    height: 200,
                    modal: true,
                    buttons: {
                      Login: function() {
                        document.location.href = '/user/login/';
                      },
                      Cancel: function() {
                        $("#one-facebook-friend-dialog").dialog("close");
                      }
                    }
                  });
                } else {
                  selected_friend_list.push(checkbox.value);
                  checkbox.checked = true;
                  $("#facebook_ids").val(selected_friend_list.join());
                }
              }
            });
          }
        });
      });
    } else {
      //alert("You are not logged in.");
    }
  }, {scope: 'friends_status,publish_stream,publish_actions'});
}

// Load friends when FB API is ready.
function facebook_delivery_method_init() {
  if(fb_is_ready) {
    window.clearInterval(facebook_delivery_method_interval);
    facebook_delivery_method_friend_list();
  } else {
    ;
  }
}

// Init the facebook profile page.
function facebook_profile_init() {
  if(fb_is_ready) {
    window.clearInterval(facebook_profile_interval);
    FB.getLoginStatus(function(response) {
      if (response.status === "connected") {
        FB.api("/me", function(response) {
          if(response.id !== null) {
            $("#facebook_id").val(response.id);
            $("#name").val(response.name);
          }
        });
      }
    });
  } else {
    ;
  }
}

function facebook_profile_login() {
  FB.login(function(response) {
    if(response.authResponse) {
      facebook_profile_init();
    } else {
      //$("#friend-list").html("Error occured during log in. Make sure you logged in and allowed the app access to continue.");
    }
  }, {scope: 'friends_status,publish_stream,publish_actions'});
}


function facebook_delivery_method() {
// Declare dialog.
  $("#facebook-friend-dialog").dialog({
    autoOpen: false,
    width: 400,
    height: 300
  });

  // If the select friends button is clicked.
  $("#select-friends").click(function() {
    // Open the friend list dialog.
    $("#facebook-friend-dialog").dialog("open");

    // If any previously selected friends, load them in selected_friend_list
    if($("#facebook_ids").val()) {
      selected_friend_list = $("#facebook_ids").val().split(",");
    }
    
    // Load the friend list.
    var html = '<ul style="list-style: none;">';

    // Load the friend list.
    for (var i = 0; i < friend_list.length; i++) {
      if (selected_friend_list.indexOf(String(friend_list[i].id)) >= 0) {
        html += '<li style="border-bottom: 1px gray solid;"> <input type="checkbox" class="facebook-friend" checked="checked" value="' + friend_list[i].id + '" /> <img src="' + friend_list[i].picture.data.url + '" alt="" style="max-height: 30px;" /> ' + friend_list[i].name + '</li>';
      } else {
        html += '<li style="border-bottom: 1px gray solid;"> <input type="checkbox" class="facebook-friend" value="' + friend_list[i].id + '" /> <img src="' + friend_list[i].picture.data.url + '" alt="" style="max-height: 30px;" /> ' + friend_list[i].name + '</li>';
      }
    }

    // Add the list to the dialog.
    $("#facebook-friend-dialog").html(html);

    // Add click event to the checkbox.
    $(".facebook-friend").click(function() {
      // If user already in list, remove them.
      if (selected_friend_list.indexOf(this.value) >= 0) {
        selected_friend_list.splice(selected_friend_list.indexOf(this.value), 1);
        this.checked = false;
      } else {
        
        
        
      }
      $("#facebook_ids").val(selected_friend_list.join());
    });

    html += '</ul>';
  });
}

// Load friends when FB API is ready.
function facebook_display_user_list_init(friends) {
  if(fb_is_ready) {
    window.clearInterval(facebook_display_user_list_interval);
    facebook_display_user_list(friends);
  } else {
    ;
  }
}

function facebook_display_user_list() {
  FB.getLoginStatus(function(response) {
    if (response.status === "connected" && !friend_list.length) {
      FB.api('me/friends', {fields: "id,name,picture"}, function(response) {
        friend_list = response.data;
        
        var selected_friend_list = friends.split(",");
        var html = '<table class="table">';

        for (var i = 0; i < friend_list.length; i++) {
          if (selected_friend_list.indexOf(String(friend_list[i].id)) >= 0) {
            html += '<tr><td><img src="' + friend_list[i].picture.data.url + '" alt="" style="max-height: 30px;" /> ' + friend_list[i].name + '</td></tr>';
          }
        }
        html += '</table>';

        $("#fb_user_list").html(html);
      });
    }
  });
}


function fb_test() {
  
  FB.getLoginStatus(function(response) {
    if (response.status === "connected") {
      var urlCall = "/search?q=South+Bend+Indiana&type=page&access_token=";
      console.log(urlCall);
      FB.api(urlCall, function(res) {
        console.log(res.data.length);
        for(var i = 0; i < res.data.length; i++) {
          console.log(res.data[i]);
          if(res.data[i].category === "City" || res.data[i].category === "Local business" || res.data[i].category === "Place") {
            console.log(res.data[i]);
          }
        }
      });
    }
  });
    
  
  
  
  
  return;
  var options = {
      message : "Message",
      tags: 100007359372168,
      place: "https://foursquare.com/v/computer-history-museum/4abd2857f964a520c98820e3"
    };
    
    FB.getLoginStatus(function(response) {
      if (response.status === "connected") {
        FB.api("/me/feed", "post", options, function(response) {
          console.log(response);
          if (response.id) {
            console.log('I see you.');
          } else {
            console.log('Post was not published.');
          }
        });
      }
    });
    
    return;
  
  FB.getLoginStatus(function(response) {
    if (response.status === "connected") {
      FB.api(
              "/me/objects/place",
              "POST",
              {
                object : {
                  "fb:app_id": "645940108781110",
                  "og:type": "place",
                  "og:url": "https://cbfweb.com/wapo/profile/location/16/",
                  "og:title": "Testing",
                  "og:image": "https:\/\/s-static.ak.fbcdn.net\/images\/devsite\/attachment_blank.png",
                  "place:location:latitude": 38.581572,
                  "place:location:longitude": -121.494400
                }
              },
      function(response) {
        console.log(response);
        if (response && !response.error) {
          /* handle the result */
        }
      }
      );
    }
  });
  
  
  
  
  return;
  var options = {
      message : "Message",
      tags: 100007359372168,
      place: "https://cbfweb.com/wapo/profile/location/16/"
    };
    
    FB.getLoginStatus(function(response) {
      if (response.status === "connected") {
        FB.api("/me/feed", "post", options, function(response) {
          console.log(response);
          if (response.id) {
            console.log('I see you.');
          } else {
            console.log('Post was not published.');
          }
        });
      }
    });
}

function facebook_send(url) {
  console.log(url);
  var mydata = null;
  $.ajax({
    url: url,
    dataType: "json",
    async: false
  }).done(function(data) {
    mydata = data;
  });
  
  console.log(mydata);
  
  // Check that data is filled in.
  if(!mydata) {
    alert("Error posting to Facebook.");
    return;
  }
  
  if (mydata.loggedin === "yes") {
    var options = {
      message: mydata.post,
      tags: mydata.facebook_ids,
      place: mydata.location //111957282154793
    };
    
    FB.getLoginStatus(function(response) {
      console.log(options);
      console.log(response);
      if (response.status === "connected") {
        FB.api("/me/feed", "post", options, function(res) {
          if (res.id) {
            document.location.href = mydata.url;
          } else {
            alert('Post was not published.');
          }
        });
      }
    });
    
  } else {
    FB.ui({
      to: parseInt(mydata.facebook_ids),
      method: 'feed',
      name: mydata.name,
      link: mydata.link,
      picture: mydata.picture,
      caption: mydata.caption,
      description: mydata.description + "  Click on title to download or paste url to browser: " + mydata.link
    },
    function(response) {
      if (response && response.post_id) {
        document.location.href = mydata.url;
      } else {
        alert('Post was not published.');
      }
    });
  }
}

function facebook_download() {
  FB.getLoginStatus(function(response) {
    if (response.status === "connected") {
      var url = "/wapo/download/facebook/confirm/?facebook_id=" + response.authResponse.userID;
      $.ajax({
        url: url
      }).done(function(data) {
        if(data === "expired") {
          $("#facebook-download").html('Wp expired.');
        } else if(data === "downloaded") {
          $("#facebook-download").html('Wp already downloaded.');
        } else if(data === "error") {
          $("#facebook-download").html('Wp not found.');
        } else {
          $("#facebook-download").html('Click to Download');
          $("#facebook-download").attr('href', data);
        }
      });
    } else {
      $("#facebook-download").click(function() {
        FB.login(function(response) {
          facebook_download();
        });
      });
    }
  });
}