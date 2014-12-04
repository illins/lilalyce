var Lists = Backbone.Collection.extend({
  url: function() {
    return '/wp/mailchimp/lists/';
  },
  parse: function(resp, xhr) {
    return resp.data;
  }
});

var Emails = Backbone.Collection.extend({
  parse: function(resp, xhr) {
    return resp.data;
  }
});

var ListsView = Backbone.View.extend({
  initialize: function() {
    
  }
});