var follower_list = [];

function twitter_followers() {
  $.ajax({
    url: "/user/login/twitter/is/loggedin/"
  }).done(function(data) {
    if (!_.isUndefined(data.account)) {
      $.ajax({
        url: "/wp/twitter/followers/"
      }).done(function(followers) {
        follower_list = (follower_list.length) ? $("#twitter_followers").val().split(",") : [];
        
        $("#twitter-follower-list").html('');
        var template = _.template($("#template_twitter_follower").html());

        _.map(followers.followers.users, function(follower) {
          var checked = (follower_list.indexOf(follower.screen_name) !== -1) ? 'checked' : '';
          $("#twitter-follower-list").append(template({photo_url: follower.profile_image_url_https, name: follower.name, screen_name: follower.screen_name, checked: checked}));
        });        
        
        // If any of the list is clicked, add or remove it.
        $(".twitter_follower").click(function() {
          var screen_name = $(this).attr('data-sn');// Get the screen name.

          // If it is not in the list add it, otherwise remove it.
          if (follower_list.indexOf(screen_name) === -1) {
            follower_list.push(screen_name);
          } else {
            follower_list.splice(follower_list.indexOf(screen_name), 1);
          }

          // Reset the friends variable.
          $("#twitter_followers").val(follower_list.join(","));
        });
      });
    } else {
      $("#twitter-follower-list").html('<a class="btn btn-primary" href="/user/login/twitter/?return=/wp/atf/&skip=1">Login With Twitter</a>');
    }
  });
}

function twitter_loggedin() {
  $("#next_button").addClass("disabled");
  
  $.ajax({
    url: "/user/login/twitter/is/loggedin/"
  }).done(function(data) {
    if (!_.isUndefined(data.account)) {
      var template = _.template($("#template_twitter_user").html());
      $("#twitter_user").html(template({t_id: data.account.id, photo_url: data.account.profile_image_url_https, name: data.account.name, screen_name: data.account.screen_name}));
      $("#next_button").removeClass("disabled");
    } else {
      $("#twitter_user").html('<a class="btn btn-primary" href="/user/login/twitter/?return=/wp/atf/&skip=1">Login With Twitter</a>');
    }
  });
}

function twitter_relationship() {
  // Check if they are logged in through twitter.
  $.ajax({
    url: "/user/login/twitter/is/loggedin/"
  }).done(function(account) {
    if (!_.isUndefined(account.account)) {
      $.ajax({
        url: "/wp/download/twitter/relationship/"
      }).done(function(data) {
        // Check if they are a follower.
        if (!_.isUndefined(data.follower)) {
          if(data.follower) {
            $("#next_button").click();
          } else {
            $("#twitter_relationship").html('You are not selected to receive the Wapo.');
            $("#next_button").addClass('disabled');
          }
        } else {
          $("#twitter_relationship").html('Session expired. Please try again.');
        }
      });
    } else {
      // Show them login link.
      $("#twitter_relationship").html('<a class="btn btn-primary" href="/user/login/twitter/?return=/wp/atf/&skip=1">Login With Twitter</a>');
    }
  });
}

function twitter_download_loggedin() {
  //$("#next_button").addClass("disabled");
  
  $.ajax({
    url: "/user/login/twitter/is/loggedin/"
  }).done(function(data) {
    if (!_.isUndefined(data.account)) {
      var template = _.template($("#template_twitter_user").html());
      $("#twitter_user").html(template({t_id: data.account.id, photo_url: data.account.profile_image_url_https, name: data.account.name, screen_name: data.account.screen_name}));
      //$("#next_button").removeClass("disabled");
    } else {
      $("#twitter_user").html('<a class="btn btn-primary" href="/user/login/twitter/?return=/wp/download/twitter/&skip=1">Login With Twitter</a>');
    }
  });
}