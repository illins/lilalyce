(function (Backbone, $, _) {
  // Once the document is ready, do the following.
  $(function () {
    function cleanInteger(integer) {
      return (_.isNaN(integer)) ? 0 : integer;
    }

    // FFA TAB.

    // If there is an ffa quanitty, set it.
    if ($.cookie('ffa_quantity')) {
      $('#ffa_quantity').val(cleanInteger($.cookie('ffa_quantity')));
    }

    // If ffa quanitty changes, update the cookie.
    $('#ffa_quantity').keyup(function () {
      $.cookie('ffa_quantity', $('#ffa_quantity').val());
    });

    // EMAIL TAB.

    // Add event for each email form fie.d
    for (var i = 1; $('#email-' + i); i++) {
      console.log($('#email-' + i));
      $('#email-' + i).change(function () {
        $.cookie('#email-' + i, $('#email-' + i).val());
      });
    }


    // Display text/icon on the Twitter tab.
    var twitterTABView = new (Backbone.View.extend({
      el: '#twitter-tab-view',
      authenticated: false,
      render: function () {
        // If we are authenticated, display tab with user icon, otherwise, no icon.
        if (this.authenticated) {
          this.$el.html('Twitter <i class="fa fa-user"></i>');
        } else {
          this.$el.html('Twitter');
        }
      }
    }));

    // View to display login button if they have not logged in to Twitter.
    var twitterLOGINView = new (Backbone.View.extend({
      el: '#twitter-login-view',
      template: _.template($('#twitter-login-template').html()),
      render: function () {
        this.$el.html(this.template());
      }
    }));

    // View to display the ATF section.
    var twitterATFView = new (Backbone.View.extend({
      el: '#twitter-atf-view',
      account: null,
      template: _.template($('#twitter-atf-template').html()),
      render: function () {
        this.$el.html(this.template({account: this.account}));

        // Set the quantity based on the cookie.
        $('#twitter_atf_quantity').val(cleanInteger($.cookie('twitter_atf_quantity')));

        // Updae the quantity if it changes in the form field.
        $('#twitter_atf_quantity').keyup(function () {
          $.cookie('twitter_atf_quantity', $('#twitter_atf_quantity').val());
        });
      }
    }));

    // View to show the followers.
    var twitterSTFView = new (Backbone.View.extend({
      el: '#twitter-stf-view',
      template: _.template($('#twitter-stf-template').html()),
//    events: {
//      'click': 'checked'
//    },
//    checked: function(e) {
//      
//    },
      render: function () {
        this.$el.html('');// Clear the section.

        // Add the list of followers.
        _.map(this.follower_list, function (follower) {
          this.$el.append(this.template({follower: follower}));
        });
      }
    }));

    // Ping Twitter API to see if they are logged in.
    $.get('/twitter/authenticated/', function (data) {
      if (data.authenticated) {
        twitterTABView.authenticated = true;
        twitterTABView.render();

        twitterATFView.account = data.account;
        twitterATFView.render();
      } else {
        twitterTABView.authenticated = true;
        twitterTABView.render();

        twitterLOGINView.render();
      }
    });

    // MAILCHIMP TAB.

    // Display text/icon on the Twitter tab.
    var mailchimpTABView = new (Backbone.View.extend({
      el: '#mailchimp-tab-view',
      authenticated: false,
      render: function () {
        // If we are authenticated, display tab with user icon, otherwise, no icon.
        if (this.authenticated) {
          this.$el.html('Mailchimp <i class="fa fa-user"></i>');
        } else {
          this.$el.html('Mailchimp');
        }
      }
    }));

    // View to display login button if they have not logged in to Mailchimp.
    var mailchimpLOGINView = new (Backbone.View.extend({
      el: '#mailchimp-login-view',
      template: _.template($('#mailchimp-login-template').html()),
      render: function () {
        this.$el.html(this.template());
      }
    }));

    // View to display a user's list of emails from a list.
    var mailchimpEMAILView = new (Backbone.View.extend({
      el: '#mailchimp-list-view',
      template: _.template($('#mailchimp-list-template').html()),
      lemail_list: [],
      render: function () {
        this.$el.html('');// Clear the section.

        // Add the list of lists.
        _.map(this.email_list, function (email) {
          this.$el.append(this.template({email: email}));
        });
      }
    }));

    // View to display a user's lists of emails.
    var mailchimpLISTView = new (Backbone.View.extend({
      el: '#mailchimp-list-view',
      template: _.template($('#mailchimp-list-template').html()),
      list_list: [],
      subview: mailchimpEMAILView,
      events: {
        'change input[name=mailchimp_list_id]': 'listChanged'
      },
      listChanged: function (e) {
        // Fetch the user's subscription list.
        $.get('/mailchimp/list/' + $(e.target).val() + '/subscribed/', function (data) {
          this.subview.email_list = data.list;
          this.subview.render();
        });
      },
      render: function () {
        this.$el.html('');// Clear the section.

        // Add the list of lists.
        _.map(this.list_list, function (list) {
          this.$el.append(this.template({list: list}));
        });
      }
    }));

    // Ping Mailchimp API to see if they are logged in.
    $.get('/mailchimp/authenticated/', function (data) {
      if (data.authenticated) {
        mailchimpTABView.authenticated = true;
        mailchimpTABView.render();

        // Fetch the mailchimp list.
        $.get('/mailchimp/list/', function (data) {
          mailchimpLISTView.list_list = data.list_list;
          mailchimpLISTView.render();
        });

      } else {
        mailchimpTABView.authenticated = true;
        mailchimpTABView.render();

        mailchimpLOGINView.render();
      }
    });

  });
})(Backbone, $, _);