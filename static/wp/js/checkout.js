(function (Backbone, $, _) {
  // View to display if they have logged in to twitter.
  var twitterView = new (Backbone.View.extend({
    el: '#twitter',
    account: null,
    template: _.template($('#twitter-view').html()),
    render: function() {
      this.$el.find('.list-group-item-text').html(this.template({account: this.account}));
      $('#twitter-tab-a').html('Twitter <i class="fa fa-user"></i>');
    }
  }));
  
  // Check we have a Twitter announcement.
  if(parseInt($.cookie('twitter_announcement'))) {
    // Ping Twitter API to see if they are logged in.
    $.get('/twitter/authenticated/', function(data) {
      if(data.authenticated) {
        twitterView.account = data.account;
        twitterView.render();
        twitterView.$el.removeClass('hidden');
      }
    });
  }
  
})(Backbone, $, _);