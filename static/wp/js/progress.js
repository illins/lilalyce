(function (Backbone, $, _) {
  // Define a module view.
  var GenericView = Backbone.View.extend({
    el: null,
    template: null,
    data: null,
    initialize: function(options) {
      this.el = options.el;
      this.template = _.template($(options.template_id).html());
      this.data = options.data;
      this.render();
    },
    render: function() {
      this.$el.html(this.template({data: this.data}));
    }
  });
  
  $(function() {
    // Check if the module progress is defined.
    if($('#progress-module-view') && $.cookie('module_id')) {
      // Fetch the module.
      var url = '/wp/progress/module/' + $.cookie('module_id') + '/';
      $.get(url, function (data) {
        new GenericView({el: '#progress-module-view',template_id:'#progress-module-template',data:data.module});
      });
    }
    
    // Check if the profile progress is defined.
    if($('#progress-profile-view') && $.cookie('profile_id')) {
      // Fetch the module.
      var url = '/wp/progress/profile/' + $.cookie('profile_id') + '/';
      $.get(url, function (data) {
        new GenericView({el: '#progress-profile-view',template_id:'#progress-profile-template',data:data.profile});
      });
    }
  });
  
})(Backbone, $, _);