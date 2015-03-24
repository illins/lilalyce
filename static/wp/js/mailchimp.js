(function (Backbone, $, _) {
  // Once the document is ready, do the following.
  $(function () {
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