(function ($, Backbone, _, HandlebarsRender) {
  // Get the form values for these fields.
  var initial = {
    size: $('#initial-size').val(),
    color: $('#initial-color').val(),
    quantity: $('#initial-quantity').val(),
    category_id: $('#category_id').val(),
    product_id: $('#product_id').val()
  };
  
  // Initialize quantity to 1.
  if(!initial.quantity) {
    initial.quantity = 1;
  }
  
  var CategoryCollection = Backbone.Collection.extend({
    parse: function (resp, xhr) {
      return resp.category_list;
    }
  });

  var ProductCollection = Backbone.Collection.extend({
    parse: function (resp, xhr) {
      return resp.category.products;
    }
  });

  var ProductModel = Backbone.Model.extend({
    parse: function (resp, xhr) {
      return resp.product;
    }
  });

  var CategoryListView = Backbone.View.extend({
    el: '#garment-category-products',
    initialize: function () {
      // Load the categories.
      this.collection = new CategoryCollection();
      this.collection.url = '/wp/scalablepress/category/';
      this.collection.bind('reset add remove', this.render, this);
      this.collection.fetch();

      this.subview = new ProductListView();
    },
    render: function () {
      var category = function(text, render) {
        console.log(text);
        console.log('here?');
        return (String(text) === String(initial.category_id)) ? 'selected' : '';
      };
      
      HandlebarsRender.render('#template-category-products', '#garment-category-products', {categoryList: this.collection.toJSON(), category: category});
    },
    events: {
      'change #garment-category-list': 'selectedCategory',// Show the product list if a category list changes.
      'click #garment-products-selected': 'breadcrumbCategory'// Show the product list if a category list changes.
    },
    selectedCategory: function(e) {
      this.productList($(e.target).val());
    },
    breadcrumbCategory: function(e) {
      e.preventDefault();
      this.productList($(e.target).data('id'));
    },
    productList: function (categoryId) {
      // Get the selected category and fetch the list using the ProductListView subview.
      this.subview.collection.url = encodeURI('/wp/scalablepress/category/' + categoryId + '/');
      this.subview.collection.fetch();
    }
  });

  var ProductListView = Backbone.View.extend({
    el: '#garment-content',
    initialize: function () {
      // If we have a category, show the category, othwise show the hoodies.
      this.collection = new ProductCollection();
      var categoryId = $('#category_id').val();
      if (categoryId) {
        this.collection.url = '/wp/scalablepress/category/' + categoryId + '/';
      } else {
        this.collection.url = '/wp/scalablepress/category/hoodies/';
      }
      this.collection.bind('reset add remove', this.render, this);
      this.collection.fetch();

      // Set the product view.
      this.subview = new ProductView();
    },
    render: function () {
      // Show the breadcrumb for this category.
      var categoryId = $("#gargement-category-list").val();
      var name = $("#gargement-category-list option:selected").text();
      $("#garment-products").html('<a href="#" data-id="' + categoryId + '">' + name + '</a>');
      
      HandlebarsRender.render('#template-products', '#garment-content', {productList: this.collection.toJSON()});
    },
    events: {
      'click .garment-product': 'product'
    },
    product: function (e) {
      e.preventDefault();
      var productId = $(e.target).data('id');
      this.subview.model.urlRoot = '/wp/scalablepress/products/' + productId + '/';
      var that = this;
      this.subview.model.fetch({
        success: function (resp) {
          that.subview.render();
        }
      });
    }

  });

  var ProductView = Backbone.View.extend({
    el: '#garment-content',
    //template: _.template($('#template-product').html()),
    model: new ProductModel(),
    initialize: function () {
      // If we have a product_id set, render it.
      var productId = $('#product_id').val();
      if(productId) {
        this.model.urlRoot = '/wp/scalablepress/products/' + productId + '/';
        var that = this;
        this.model.fetch({
          success: function(resp) {
            that.render();
          }
        });
      }
      
    },
    render: function () {
      var model = this.model.toJSON();
      // Gather up the images.
      var imageList = [];
      if (!_.isUndefined(model.image)) {
        imageList.push({'label': model.image.label, 'url': model.image.url});
      }
      if (!_.isUndefined(model.additionalImages)) {
        _.map(model.additionalImages, function (item) {
          imageList.push({'label': item.label, 'url': item.url});
        });
      }

      // Gather the color list.
      var colorList = [];
      if (!_.isUndefined(model.colors)) {
        _.map(model.colors, function (color) {
          colorList.push(color);
        });
      }

      //this.$el.html(this.template());
      var data = {product: model, imageList: imageList, colorList: colorList};
      HandlebarsRender.render('#template-product', '#garment-content', data);
      MasterSliderShowcase2.initMasterSliderShowcase2();
    },
    events: {
      'click .garment-color-picker': 'renderSizes',
      'click .garment-size-picker': 'showShipping'
    },
    renderSizes: function (e) {
      var hexColor = $(e.target).data('hex');
      var model = this.model.toJSON();

      _.map(model.colors, function (color) {
        if (String(color.hex) === String(hexColor)) {
          $('.product-size').html('');

          var template = _.template('<li><input type="radio" id="size-{{size}}" name="size" value="{{size}}"><label for="size-{{size}}">{{size}}</label></li>');
          _.map(color.sizes, function (size) {
            $('.product-size').append(template({size: size}));
          });
        }
      });
    },
    showShipping: function (e) {
      // Show the shipping field.
      $("#garment-shipping").show();
    }
  });

  categoryListView = new CategoryListView();

  function garmentQuote() {
    var fieldList = $("form").serializeArray();
    
    _.map(fieldList, function(key, val) {
      if(!val.trim()) {
        $('#garment-quote-error').html('Field "' + key + ' must be filled in to get an estimate.');
        return;
      }
    });
    
    $.ajax({
      type: "POST",
    url: "/wp/scalablepress/quote/",
    dataType: "json"
  }).done(function(data) {
    
  });
  }
})($, Backbone, _, HandlebarsRender);