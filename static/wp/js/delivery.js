(function (Backbone, $, _) {
  $(function() {
    // FFA.
    
    // If ffa quanitty changes, update the cookie.
    $('#ffa_quantity').keyup(function () {
      $.cookie('ffa_quantity', $('#ffa_quantity').val());
    });
    
    // If there is an ffa quanitty, set it.
    if($.cookie('ffa_quantity')) {
      $('#ffa_quantity').val($.cookie('ffa_quantity'));
    }

  });
  
  
  // Set the announcement cookie whenever value changes.
  $('#announcement').keyup(function () {
    $.cookie('announcement', $('#announcement').val());
  });
  
  // View to display if they have not logged in to twitter.
  var twitterLoginView = new (Backbone.View.extend({
    el: '#twitter',
    view: null,
    template: _.template($('#twitter-login-view').html()),
    render: function() {
      this.$el.html(this.template());
    }
  }));
  
  // View to display if they have logged in to twitter.
  var twitterView = new (Backbone.View.extend({
    el: '#twitter',
    view: null,
    account: null,
    template: _.template($('#twitter-view').html()),
    render: function() {
      this.$el.html(this.template({account: this.account}));
      $('#twitter-tab-a').html('Twitter <i class="fa fa-user"></i>');
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
  
})(Backbone, $, _);