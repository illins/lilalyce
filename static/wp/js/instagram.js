var follower_list = [];

function instagram_followers() {
  $.ajax({
    url: "/user/login/instagram/is/loggedin/"
  }).done(function(data) {
    if (data.account) {
      $.ajax({
        url: "/wp/instagram/followers/"
      }).done(function(followers) {
        follower_list = (follower_list.length) ? $("#instagram_followers").val().split(",") : [];
        
        $("#instagram-follower-list").html('');
        var template = _.template($("#template_instagram_follower").html());

        _.map(followers.followers.data, function(follower) {
          var checked = (follower_list.indexOf(String(follower.id)) !== -1) ? 'checked' : '';
          $("#instagram-follower-list").append(template({i_id: follower.id, photo_url: follower.profile_picture, name: follower.name, username: follower.username, checked: checked}));
        });        
        
        // If any of the list is clicked, add or remove it.
        $(".instagram_follower").click(function() {
          var t_id = $(this).attr('data-iid');// Get the Facebook id.

          // If it is not in the list add it, otherwise remove it.
          if (follower_list.indexOf(t_id) === -1) {
            follower_list.push(t_id);
          } else {
            follower_list.splice(follower_list.indexOf(t_id), 1);
          }

          // Reset the friends variable.
          $("#instagram_followers").val(follower_list.join(","));
        });
      });
    } else {
      $("#instagram-follower-list").html('<a class="btn btn-primary" href="/user/login/instagram/?return=/wp/sif/">Login With Instagram</a>');
    }
  });
}

function instagram_loggedin() {
  $("#next_button").addClass("disabled");
  
  $.ajax({
    url: "/user/login/instagram/is/loggedin/"
  }).done(function(data) {
    if (data.account) {
      var template = _.template($("#template_instagram_user").html());
      $("#instagram_user").html(template({i_id: data.account.data.id, photo_url: data.account.data.profile_picture, name: data.account.data.full_name, username: data.account.data.username}));
      $("#next_button").removeClass("disabled");
    } else {
      $("#instagram_user").html('<a class="btn btn-primary" href="/user/login/instagram/?return=/wp/aif/">Login With Instagram</a>');
    }
  });
}


