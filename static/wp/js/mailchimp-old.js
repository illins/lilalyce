var selected_emails = [];

function mailchimp_options(status) {
  // If they are logged in.
  if(status) {
    // Display the lists that they have.
    $.get("/wp/mailchimp/lists/", function(data) {
      if(data.data) {
        $('#mailchimp').html('<ul class="list-group"></ul>');
        _.map(data.data.data, function(row) {
          $('#mailchimp').find('ul').append('<a href="#" class="list-group-item" data-id="' + row.id + '">' + row.name + '</a>');
        });
        
        // Do the click event.
        $('#mailchimp').find('ul').find('a').click(function(e) {
          e.preventDefault();
          var id = $(e.target).data('id');
          
          // Get the members.
          $.get("/wp/mailchimp/lists/members/?id=" + id, function(d) {
            $('#mailchimp').html('<button id="back_to_list" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Email List</button><br /><br />');
//            $('#mailchimp').append('<button id="select_all" class="btn btn-primary"><i class="fa fa-check"></i> Select All</button>');
            $('#mailchimp').append('<ul class="list-group"></ul>');
            
            // Re-display the email lists.
            $('#back_to_list').click(function(e) {
              e.preventDefault();
              mailchimp_options(true);
            });
            
            // Select all the emails.
//            $('#select_all').click(function(e) {
//              e.preventDefault();
//              
//            });
            
            // Display the members for the user to pick.
            if(d.data) {
              _.map(d.data.data, function(email) {
                if(email.merges.FNAME) {
                  $('#mailchimp').find('ul').append('<a href="#" class="list-group-item" data-id="' + email.id + '">' + email.merges.FNAME + ' ' + email.merges.FNAME + '[' + email.email + ']</a>');
                } else {
                  $('#mailchimp').find('ul').append('<a href="#" class="list-group-item" data-id="' + email.id + '">' + email.email + '</a>');
                }
              });
            } else {
              $('#mailchimp').html('List has no members');
            }
          });
          
        });
      } else {
        $('#mailchimp').html('No lists to display.');
      }
    });
  } else {
    // Disable next button.
    $("#next_button").addClass("disabled");
    
    // Give them the option to log in using their mailchimp account.
    $("#mailchimp").html('<a class="btn btn-primary" href="/mailchimp/?return=/wp/mailchimp/&skip=1">Login With MailChimp</a>');
  }
}