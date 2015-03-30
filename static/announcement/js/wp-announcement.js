(function (Backbone, $, _) {
  $(function() {
    // Set the announcement cookie whenever value changes.
    $('#announcement').keyup(function () {
      $.cookie('announcement', $('#announcement').val());
    });

    // View to display if they have not logged in to twitter.
    var twitterLoginView = new (Backbone.View.extend({
      el: '#twitter-login-view',
      view: null,
      template: _.template($('#twitter-login-template').html()),
      render: function() {
        this.$el.html(this.template());
        this.$el.show();
      }
    }));

    // View to display if they have logged in to twitter.
    var twitterView = new (Backbone.View.extend({
      el: '#twitter-view',
      view: null,
      account: null,
      template: _.template($('#twitter-template').html()),
      render: function() {
        this.$el.html('<h3>Twitter Account</h3>');
        this.$el.append(this.template({account: this.account}));
        this.$el.show();
        $('#twitter-tab-view').html('Twitter <i class="fa fa-twitter"></i>');
      }
    }));

    // Point each view to the other.
    twitterLoginView.view = twitterView;
    twitterView.view = twitterLoginView;

    // Ping Twitter API to see if they are logged in.
    $.get('/twitter/authenticated/', function(data) {
      if(data.authenticated) {
        twitterView.account = data.account;
        twitterView.render();
      } else {
        twitterLoginView.render();
        $('#twitter-tab-a').html('Twitter');
      }
    });

    // FACEBOOK SECTION.

    // View to display if they have not logged in to facebook.
    var facebookLoginView = new (Backbone.View.extend({
      el: '#facebook-login-view',
      facebookview: null,
      facebookpageview: null,
      template: _.template($('#facebook-login-template').html()),
      render: function() {
        this.$el.html(this.template());
        this.$el.show();
      }
    }));
    
    // View to display if they have logged in to facebook.
    var facebookView = new (Backbone.View.extend({
      el: '#facebook-view',
      view: null,
      profile: null,
      picture: null,
      template: _.template($('#facebook-template').html()),
      render: function() {
        this.$el.html('<h3>Facebook Account</h3>');
        this.$el.append(this.template({profile: this.profile, picture: this.picture}));
        this.$el.show();
        
        $('#facebook-tab-view').html('Facebook <i class="fa fa-facebook"></i>');
      }
    }));

    // View to display if they have logged in to facebook.
    var facebookPageView = new (Backbone.View.extend({
      el: '#facebook-page-view',
      view: null,
      page_list: null,
      template: _.template($('#facebook-page-template').html()),
      render: function() {
        var that = this;
        this.$el.html('<h3>Facebook Page(s)</h3>');
        
        // Fetch previously selected pages.
        var facebook_page_id_list = [];
        if($.cookie('facebook_page_announcement')) {
          facebook_page_id_list = $.cookie('facebook_page_announcement').split(',');
        }

        // Render the pages and add them, selecting the one selected.
        _.each(this.page_list, function(page) {
          var fp = $.extend({checked: ''}, page);
          fp.checked = (_.contains(facebook_page_id_list, String(page.id))) ? 'checked' : '';
          that.$el.append(that.template({page: fp}));
        });
        this.$el.show();
        
        $('#facebook-tab-view').html('Facebook <i class="fa fa-user"></i>');
      }
//      display: function(value) {
//        if(value) {
//          this.$el.show();
//        } else {
//          this.$el.hide();
//        }
//      }
    }));
    
    // Point each view to the other.
    facebookLoginView.facebookview = facebookView;
    facebookLoginView.facebookpageview = facebookPageView;
    facebookView.view = facebookLoginView;
    facebookPageView.view = facebookLoginView;

    // Ping Twitter API to see if they are logged in.
    $.get('/facebook/authenticated/', function(data) {
      if(data.authenticated) {
        facebookView.profile = data.profile;
        facebookView.picture = data.picture;
        facebookView.render();

        // Get their pages.
        $.get('/facebook/pages/', function(data) {
          facebookPageView.page_list = data.page_list;
          facebookPageView.render();
        });
      } else {
        facebookLoginView.render();
      }
    });
    
    // When a tab is clicked, set it as the current tab.
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
      $.cookie('announcement-current-tab', $(e.target).attr('id'));
    });
    
    // During loading of the page, load the last tab the user clicked on or default.
    if($.cookie('announcement-current-tab')) {
      $('#' + $.cookie('announcement-current-tab')).click();
    }
  });
})(Backbone, $, _);