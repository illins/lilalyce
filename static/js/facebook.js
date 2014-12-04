var friend_list = new Array();
var selected_friend_list = new Array();
var facebook_delivery_method_interval = null;
var facebook_delivery_method_interval = null;
var facebook_profile_interval = null;
var timeout = null;
var friends = "";
var fb_is_ready = false;

window.fbAsyncInit = function() {
  FB.init({
    appId: '645940108781110', // App ID - 1409621355927400      -     645940108781110
    //channelUrl: '//schuckservices.com/wapo/channel.html', // Channel File.
    status: false, // check login status
    cookie: true, // enable cookies to allow the server to access the session
    xfbml: true  // parse XFBML
  });

  fb_is_ready = true;

  // Event trigger when authentication changes.
  FB.Event.subscribe('auth.authResponseChange', function(response) {
    if (response.status === 'connected') {

    } else if (response.status === 'not_authorized') {
      FB.login(function(response) {

      }, {scope: 'friends_status,publish_stream,publish_actions'});
    } else {
      FB.login(function(response) {

      }, {scope: 'friends_status,publish_stream,publish_actions'});
    }
  });
};

// Load the SDK asynchronously
(function(d) {
  var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
  if (d.getElementById(id)) {
    return;
  }
  js = d.createElement('script');
  js.id = id;
  js.async = true;
  js.src = "//connect.facebook.net/en_US/all.js";
  ref.parentNode.insertBefore(js, ref);
}(document));
    
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

function facebook_ready(callback) {
  if (fb_is_ready) {
    clearTimeout(timeout);
    callback();
  } else {
    timeout = setTimeout(function() {
      facebook_ready(callback)
    }, 500);
  }
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