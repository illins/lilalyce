var wapoApp = angular.module('wapoApp', ['ngRoute', 'ngResource', 'ngFileUpload', 'ui.bootstrap', 'ngCookies'], function ($httpProvider) {
  // Use x-www-form-urlencoded Content-Type
  $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';

  /**
   * The workhorse; converts an object to x-www-form-urlencoded serialization.
   * @param {Object} obj
   * @return {String}
   */
  var param = function (obj) {
    var query = '', name, value, fullSubName, subName, subValue, innerObj, i;

    for (name in obj) {
      value = obj[name];

      if (value instanceof Array) {
        for (i = 0; i < value.length; ++i) {
          subValue = value[i];
          fullSubName = name + '[' + i + ']';
          innerObj = {};
          innerObj[fullSubName] = subValue;
          query += param(innerObj) + '&';
        }
      }
      else if (value instanceof Object) {
        for (subName in value) {
          subValue = value[subName];
          fullSubName = name + '[' + subName + ']';
          innerObj = {};
          innerObj[fullSubName] = subValue;
          query += param(innerObj) + '&';
        }
      }
      else if (value !== undefined && value !== null)
        query += encodeURIComponent(name) + '=' + encodeURIComponent(value) + '&';
    }

    return query.length ? query.substr(0, query.length - 1) : query;
  };

  // Override $http service's default transformRequest
  $httpProvider.defaults.transformRequest = [function (data) {
      return angular.isObject(data) && String(data) !== '[object File]' ? param(data) : data;
    }];
});

wapoApp.controller('MainCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', '$cookies', '$interval', function ($rootScope, $scope, $location, $http, $routeParams, $cookies, $interval) {
    $scope.progress = 0;
    
    $rootScope.ready = false;

    $rootScope.user = null;
    $rootScope.wapo = null;

    $rootScope.module_list = [];
    $rootScope.profile_list = [];
    $rootScope.tangocards_list = [];

    $rootScope.subscription_list = [];
    $rootScope.subscription_email_list = [];

    $rootScope.followers = [];
    $rootScope.pages = [];
    
    $rootScope.promotioncategory_list = [];

    $rootScope.setPath = function (path, href) {
      $cookies.put('path', path);
      window.location.href = href;
    };

    $rootScope.mainInit = function (callback) {
      var callback = callback || null;
      
//      var path = $cookies.get('path');
//      if (path) {
//        $cookies.remove('path');
//        $location.path(path);
//        return;
//      }

      if (!$rootScope.wapo) {
        $http.get('/wp/wapo/data/').success(function (response) {
          $rootScope.user = response.blink.request.user;
          $rootScope.wapo = response.wapo;
          $scope.uobj = $rootScope.user;
          console.log('wapo', $rootScope.wapo);
          console.log('user', $rootScope.user);

          if (callback) {
            console.log('callback with server');
            callback();
          }
        });
      } else {
        if (callback) {
          console.log('callback wtih no server!');
          callback();
        }
      }
    };

    $rootScope.next_path = null;
    $rootScope.next = function (path) {
      var path = path || $rootScope.next_path;
      $location.path(path);
    };

    $rootScope.previous_path = null;
    $rootScope.previous = function () {
      $location.path($rootScope.previous_path);
    };

    $rootScope.showDialog = function (title, text) {
      alert(text);
    };

    $rootScope.setProgress = function (progress) {
      $scope.progress = progress;
    };
    
    $rootScope.setTitle = function(title, sub_title) {
      $('#main-content-header').find('.title').html(title + '<small>' + sub_title + '</small>');
    };
    
    $rootScope.resizeImages = function() {
      var col = new jColumn();
      col.jcolumn('marketplace-item-container');
    };
    
    $rootScope.initMarketplaceImages = function() {
      $(window).unbind('resize');
      
      $rootScope.interval = $interval(function() {
        if($('.marketplace-image-container').width()) {
          $rootScope.resizeImages();
          $interval.cancel($rootScope.interval);
        }
      }, 1000);
      
      $(window).resize(function() {
        $rootScope.resizeImages();
      });
      
      console.log(location.hash);
    };
    
    
    $rootScope.subTotal = function(quantity) {
      if($rootScope.wapo.promotion) {
        return $rootScope.wapo.promotion.price * quantity;
      } else if($rootScope.wapo.tangocards) {
        return $rootScope.wapo.unit_price * quantity / 100;
      }
    };
    
    $rootScope.webSignOut = function() {
      $http.post('/user/api/web-sign-out/', {}).success(function(response) {
        $rootScope.user = null;
        
        // Clear profile if any.
        $http.post('/wp/wapo/clear/profile/', {}).success(function(response) {
          $rootScope.wapo = response.wapo;
//          window.location.reload();
        });
      }).error(function(errorResponse) {
        
      });
    };
    
    $rootScope.goto = function(path) {
      $location.path(path);
    };
    
    $rootScope.initMarketplaceCategories = function(callback) {
      var callback = callback || null;
      
      if(!$rootScope.promotioncategory_list.length) {
        $http.get('/wp/wapo/promotioncategory/').success(function(response) {
          $rootScope.promotioncategory_list = response;
          
          if(callback) {
            callback();
          }
        });
      } else {
        if (callback) {
          callback();
        }
      }
    };
  }]);

wapoApp.controller('ModuleCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.previous_path = null;
    $rootScope.next_path = '/profile-new';
    
    $rootScope.setTitle('Module', 'Select module');
    $rootScope.setTitle('', '');

    $scope.md_group_list = [];

    $scope.init = function () {
      if ($rootScope.wapo.module) {
        console.log($rootScope.next_path);
        $location.path($rootScope.next_path);
      } else if (!$rootScope.module_list.length) {
        $http.get('/wp/wapo/module/').success(function (response) {
          $rootScope.module_list = response.module_list;
          if (response.module_list.length == 1) {
            $scope.setModule(response.module_list[0]);
          }

          for (var x = 0; x < $rootScope.module_list.length; x++) {
            if ($rootScope.module_list[x].tag == 'gift') {
              $scope.setModule($rootScope.module_list[x]);
              break;
            }
          }

          $scope.md_group_list = _.chunk($rootScope.module_list, 3);
        });
      } else {
        $scope.md_group_list = _.chunk($rootScope.module_list, 3);
      }
      
      if($rootScope.user) {
        $rootScope.next_path = '/profile';
      }
    };
    $rootScope.mainInit(function () {
      $scope.init();
    });

    $scope.setModule = function (module) {
      $http.post('/wp/wapo/set/module/', {module_id: module.id}).success(function (response) {
        $rootScope.wapo = response.wapo;
        console.log($rootScope.next_path);
        $location.path($rootScope.next_path);
      }).error(function (errorResponse) {
        alert(errorResponse.message);
      });
    };

    $scope.css = function (tag, name) {
      return (tag === name);
    };
  }]);

wapoApp.controller('ProfileCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.setTitle('Profile Selector', 'Select one of your profiles');
    $rootScope.setProgress(1);
    
    $rootScope.previous_path = null;
    $rootScope.next_path = '/marketplace';

    $scope.profile = null;
    $scope.profile_chunk_list = [];
    $scope.data = {};

    $scope.init = function () {
      $scope.profile = $rootScope.wapo.profile.profile;

      if (!$rootScope.profile_list.length) {
        $http.get('/wp/wapo/profile/').success(function (response) {
          $rootScope.profile_list = response.profile_list;
          $scope.profile_chunk_list = _.chunk($rootScope.profile_list, 3);
          $rootScope.ready = true;
        });
      } else {
        $scope.profile_chunk_list = _.chunk($rootScope.profile_list, 3);
      }
    };
    $rootScope.mainInit(function () {
      $scope.init();
    });

    $scope.selectProfile = function (profile) {
      $scope.profile = profile;
    };

    $scope.next = function () {
      if (!$scope.profile) {
        alert('Please select a profile!');
        return;
      }

      $http.post('/wp/wapo/set/profile/', {profile_id: $scope.profile.id}).success(function (response) {
        $rootScope.wapo = response.wapo;
        $location.path($rootScope.next_path);
      });
    };
    
    $scope.webSignIn = function() {
      if(!$scope.data.identifier || !$scope.data.password) {
        toastr.error('Missing username/password');
        return;
      }
      
      $http.post('/user/api/web-sign-in/', $scope.data).success(function(response) {
        $rootScope.user = response.user;
        $scope.init();
      }).error(function(errorResponse) {
        toastr.error(errorResponse.message);
      });
    };
    
    $scope.webSignUp = function() {
      if(!$scope.data.email) {
        toastr.error('Missing email!');
        return;
      }
      
      if(!$scope.data.rpassword) {
        toastr.error('Missing password!');
        return;
      }
      
      if($scope.data.rpassword.length < 8) {
        toastr.error('Password must be at least 8 characters long!');
        return;
      }
      
      var send = {
        'first_name': $scope.data.first_name,
        'last_name': $scope.data.last_name,
        'email': $scope.data.email,
        'username': $scope.data.username,
        'password': $scope.data.rpassword,
        'confirm_password': $scope.data.rpassword
      };
      
      $http.post('/wapo/sign-up/', send).success(function(response) {
        $scope.data.identifier = $scope.data.email;
//        $scope.webSignIn();
        $http.post('/user/api/web-sign-in/', {identifier: $scope.data.email, password: $scope.data.rpassword}).success(function(response) {
          $rootScope.user = response;
          $scope.init();
        }).error(function(errorResponse) {
          toastr.error(errorResponse.message);
        });
      }).error(function(errorResponse) {
        toastr.error(errorResponse.message);
      });
    };
  }]);

wapoApp.controller('ProfileNewCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', 'Upload', function ($rootScope, $scope, $location, $http, $routeParams, Upload) {
    $rootScope.setTitle('Profile Creator', 'Create a new profile');
    $rootScope.setProgress(1);

    $rootScope.previous_path = null;
    $rootScope.next_path = '/marketplace';

    $scope.profile = {};
    $scope.delete_image;

    $scope.$watch('delete_image', function (newValue, oldValue) {
      console.log(newValue);
    });

    $scope.init = function () {
      $scope.profile = $rootScope.wapo.profile.new;
    };
    $rootScope.mainInit(function () {
      $scope.init();
    });

    $scope.clear_image = function () {
      $scope.file = null;
    };

    $scope.next = function () {
      var data = angular.merge({}, $scope.profile);

      if (!data.name.trim() || !data.email.trim()) {
        $rootScope.showDialog('Error!', 'Please enter missing data!');
      }

      if ($scope.file) {
        Upload.upload({
          url: '/wp/wapo/set/profile/new/',
          data: {image: $scope.file, name: data.name, email: data.email}
        }).then(function (resp) {
          $rootScope.wapo = resp.data.wapo;
          $location.path($rootScope.next_path);
        }, function (resp) {
          $rootScope.showDialog('Upload Error!', resp.data.message);
        }, function (evt) {
          var progressPercentage = parseInt(100.0 * evt.loaded / evt.total);
//            console.log('progress: ' + progressPercentage + '% ' + evt.config.data.file.name);
        });
      } else {
        if (angular.element('#delete_image').length && angular.element('#delete_image')[0].checked) {
          data.delete = 1;
        }

        $http.post('/wp/wapo/set/profile/new/', data).success(function (response) {
          $rootScope.wapo = response.wapo;
          $location.path($rootScope.next_path);
        });
      }
    };

    $scope.clear = function () {
      $scope.profile = {};
      $scope.clear_image();
    };
  }]);

wapoApp.controller('MarketplaceCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.setProgress(2);
    $rootScope.setTitle('Marketplace', '');

    // Redirect to the correct marketplace.
    $scope.init = function () {
      var marketplace = ($rootScope.wapo.marketplace) ? $rootScope.wapo.marketplace : 'tangocards';
      if(marketplace == "promotion") {
        var id = ($rootScope.wapo.promotion.promotioncategory.id) ? $rootScope.wapo.promotion.promotioncategory.id : $rootScope.wapo.promotion.promotioncategory;
        if(id) {
          marketplace += '/' + id;
        }
      }
      
      $location.path('/marketplace/' + marketplace);
    };
    $rootScope.mainInit(function () {
      $scope.init();
    });
  }]);

wapoApp.controller('TangoCardsCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', 'filterFilter', function ($rootScope, $scope, $location, $http, $routeParams, filterFilter) {
    $rootScope.setProgress(2);
    $rootScope.setTitle('Rewards', 'Select a gift card!');

    $rootScope.previous_path = '/profile';
    $rootScope.next_path = '/delivery';

    $scope.tangocards;
    $scope.unit_price;
    $scope.tangocards_group_list = [];
    $scope.brand_list = [];

    $scope.selected_brand_description;

    $scope.$watch('selected_brand_description', function (newValue, oldValue) {
      if (newValue) {
        $scope.applyFilter();
      }
    });

    $scope.initFilters = function () {
      if(!$rootScope.tangocards_list.length) {
        return;
      }
      
      // Filter out anything that has 'unit_price' not equal to -1.
      var brand_list = _.filter($rootScope.tangocards_list, function (item) {
        return true;
//        return item.unit_price != -1;
      });

      // Get the unique brand descriptions.
      $scope.brand_list = _.unique(brand_list, function (item) {
        return item.brand_description;
      });

      // Get the 'selected brand description'.
      if ($rootScope.wapo.tangocards) {
        $scope.selected_brand_description = $rootScope.wapo.tangocards.brand_description;
      } else {
        $scope.selected_brand_description = $scope.brand_list[0].brand_description;
      }
    };

    $scope.init = function () {
      $scope.tangocards = $rootScope.wapo.tangocards;
      $scope.unit_price = $rootScope.wapo.unit_price;
      $rootScope.initMarketplaceCategories();
      
      if (!$rootScope.tangocards_list.length) {
        $http.get('/wp/wapo/tangocards/').success(function (response) {
          var tangocards_list = response.tangocardrewards_list;
          
          // And ranges.
          var tc_list = [];
          _.each(tangocards_list, function(item) {
            if(item.unit_price == -1) {
              for(var i = 0; i < 5; i++) {
                var copy = angular.copy(item);
                copy.unit_price = copy.min_price + (i * 100 * 5);
                
                // Skip if over max-price.
                if(copy.unit_price < copy.max_price) {
                  tc_list.push(copy);
                }
              }
            } else {
              tc_list.push(item);
            }
          });
          
          $rootScope.tangocards_list = tc_list;
      
          $scope.initFilters();
        });
      } else {
        $scope.initFilters();
      }

      if ($rootScope.wapo.tangocards) {
        $rootScope.next_path = '/delivery';
      }
    };
    $rootScope.mainInit(function () {
      $scope.init();
    });

    // Filter based on brand.
    $scope.applyFilter = function () {
      var filtered_tangocards_list = filterFilter($rootScope.tangocards_list, $scope.selected_brand_description);
      $scope.tangocards_group_list = _.chunk(filtered_tangocards_list, 3);
      $rootScope.initMarketplaceImages();
    };

    $scope.selectTangoCards = function (tangocards) {
      $scope.unit_price = tangocards.unit_price;
      $scope.tangocards = tangocards;
    };

    $scope.next = function () {
      if (!$scope.tangocards) {
        alert('Please select a reward!');
        return;
      }

      $http.post('/wp/wapo/set/tangocards/', {tangocards_id: $scope.tangocards.id, unit_price: $scope.tangocards.unit_price}).success(function (response) {
        $rootScope.wapo = response.wapo;
        $location.path($rootScope.next_path);
      }).error(function (errorResponse) {
        alert(errorResponse.message);
      });
    };
    
//    $rootScope.initMarketplaceImages();
    
    
  }]);

wapoApp.controller('PromotionCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', 'filterFilter', function ($rootScope, $scope, $location, $http, $routeParams, filterFilter) {
    $rootScope.setProgress(2);
    $rootScope.setTitle('Reward', 'Select a Gift!');

    $rootScope.previous_path = '/profile';
    $rootScope.next_path = '/delivery';

    $scope.promotioncategory = {};
    
    $scope.promotion = null;
    $scope.promotion_list = [];
    
    $scope.promotiontype = null;
    $scope.promotiontype_list = [];
    
    $scope.chunked_promotion_list = [];
    
    $scope.changePromotionType = function() {
      var filter_list = _.filter($scope.promotion_list, function(item) {
        return (item.promotiontype.id == $scope.promotiontype.id || item.promotiontype == $scope.promotiontype.id);
      });
      $scope.chunked_promotion_list = _.chunk(filter_list, 3);
      $rootScope.initMarketplaceImages();
    };
    
    $scope.getPromotions = function() {
      var url = '/wp/wapo/promotions/?promotioncategory=';
      
      if($routeParams.promotioncategory_id) {
        url += $routeParams.promotioncategory_id;
        
        $scope.promotioncategory = _.find($scope.promotioncategory_list, function (item) {
          return (item.id == $routeParams.promotioncategory_id || item.id == $routeParams.promotioncategory_id);
        });
          
      } else {
        if($scope.promotion) {
          $scope.promotioncategory = _.find($scope.promotioncategory_list, function(item) {
            return (item.id == $scope.promotion.promotioncategory || item.id == $scope.promotion.promotioncategory.id);
          });
//          $scope.promotioncategory = $scope.promotion.promotioncategory;
          url += $scope.promotioncategory.id;
        } else {
          $scope.promotioncategory = $rootScope.promotioncategory_list[0];
          url += $scope.promotioncategory.id;
        }
      }
      
//      url = '/wp/wapo/promotions/';
      $http.get(url).success(function(response) {
        $scope.promotion_list = response;
        
        $scope.promotiontype_list = [];
        var added_id_list = [];
        _.each($scope.promotion_list, function(item) {
          if(!_.contains(added_id_list, item.promotiontype.id)) {
            $scope.promotiontype_list.push(item.promotiontype);
            added_id_list.push(item.promotiontype.id);
          }
        });
        
        if(!$scope.promotiontype) {
          $scope.promotiontype = $scope.promotiontype_list[0];
        }
        
        $scope.changePromotionType();
      });
    };


    $scope.init = function () {
      $rootScope.initMarketplaceCategories(function() {
        $scope.promotion = $rootScope.wapo.promotion;
        
        if($scope.promotion) {
          $scope.promotiontype = {id: $scope.promotion.promotiontype};
        }
        
        $scope.getPromotions();
      });
      
//      if($rootScope.promotioncategory_list.length) {
////        $scope.promotioncategory = _.find($scope.promotioncategory_list, function(item) {
////          return (item.id == $scope.promotion.promotioncategory || item.id == $scope.promotion.promotioncategory.id);
////        });
//        
//        $scope.getPromotions();
//      } else {
//        $http.get('/wp/wapo/promotioncategories/').success(function(response) {
//          $rootScope.promotioncategory_list = response;
//          
////          $scope.promotioncategory = _.find($scope.promotioncategory_list, function(item) {
////            return (item.id == $scope.promotion.promotioncategory || item.id == $scope.promotion.promotioncategory.id);
////          });
//          
//          if($rootScope.promotioncategory_list.length) {
//            $scope.getPromotions();
//          }
//        });
//      }

      if ($rootScope.wapo.promotion) {
        $rootScope.next_path = '/delivery';
      }
    };
    
    $rootScope.mainInit(function () {
      $scope.init();
    });
    
    $scope.selectPromotion = function(promotion) {
      $scope.promotion = promotion;
      $rootScope.next_path = '/delivery';
    };
    
    $scope.next = function () {
      if (!$scope.promotion) {
        alert('Please select a reward!');
        return;
      }

      $http.post('/wp/wapo/set/promotion/', {promotion_id: $scope.promotion.id}).success(function (response) {
        $rootScope.wapo = response.wapo;
        $location.path($rootScope.next_path);
      }).error(function (errorResponse) {
        alert(errorResponse.message);
      });
    };
    
//    $rootScope.initMarketplaceImages();
  }]);

wapoApp.controller('DeliveryCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.setProgress(3);
    $rootScope.setTitle('Delivery', 'Select a delivery method');

    $scope.delivery = null;
    $scope.main_delivery = 'free-for-all';
    $scope.enabled_list = [];

    $rootScope.next_path = '/checkout';
    $rootScope.previous_path = '/marketplace';
    
    $scope.count = 0;
    
    var delivery_list = ['email', 'email-list', 'text'];
    
    $scope.initAccordion = function() {
      $('#email').collapse('hide');
      $('#email-list').collapse('hide');
      $('#text').collapse('hide');
    };
    
    $scope.setDelivery = function (delivery) {
      if(delivery === $scope.delivery) {
        return;
      }
      
      $scope.delivery = delivery;
      var id;
      
      // Hide everything.
      _.each(delivery_list, function(item) {
        if(delivery !== item) {
          id = '#' + item;
          $(id).collapse('hide');
        }
      });
      
      if($scope.delivery == "email") {
        $scope.initSingleEmail();
      } else if($scope.delivery == "email-list") {
        $scope.initEmailList();
      } else if($scope.delivery == "text") {
        $scope.initText();
      }
      
      id = '#' + delivery;
      $(id).collapse('show');
      console.log('set-delivery', id);
    };

    // SINGLE EMAIL LIST
    
    $scope.initSingleEmail = function() {
      $scope.count = 0;
      $scope.max_count = $rootScope.wapo.email.max;
      $scope.email_list = $rootScope.wapo.email.email_list;

      $scope.delivery_message = $rootScope.wapo.delivery_message;

      for (var x = $scope.email_list.length; x < $scope.max_count; x++) {
        $scope.email_list.push('');
      }
    };
    
    $scope.singleEmailNext = function () {
      var email_list = [];

      _.map($scope.email_list, function (email) {
        if (email.trim()) {
          email_list.push(email);
        }
      });

      if (!email_list.length) {
        alert("Please enter at least one email!");
        return;
      }

      $http.post('/wp/wapo/set/delivery/email/', {emails: email_list.join(','), delivery_message: $('#email-delivery-message').val()}).success(function (response) {
        $rootScope.wapo = response.wapo;
        $location.path($rootScope.next_path);
      });
    };
    
    // EMAIL LIST
    $scope.keyUpEmailList = function(event) {
      if(event.keyCode == 188 || event.keyCode == 32) {
        $scope.changeEmailList();
      }
    };
    
    $scope.changeEmailList = function () {
      var email_list = [];

      // Clean the emails.
      if(!$('#emails').length) {
        return;
      }
      
      var input_email_list = $('#emails').val().split(',');
//      var input_email_list = $scope.emails.split(',');
      _.map(input_email_list, function (email) {
        if (email.trim()) {
          email_list.push(email.trim());
        }
      });

      // Filter unique.
      email_list = _.unique(email_list, function (item) {
        return item;
      });

      $scope.count = email_list.length;
      console.log($scope.count, email_list.length);
      console.log(email_list);
      
      return email_list;
    };

    $scope.initEmailList = function () {
      $scope.count = $rootScope.wapo.email_list.email_list.length;
      $scope.emails = $rootScope.wapo.email_list.email_list.join(',');
      $scope.delivery_message = $rootScope.wapo.delivery_message;
      $scope.changeEmailList();
    };

    $scope.emailListNext = function () {
      var email_list = $scope.changeEmailList();

      // Validate the max count.
      if (email_list.length > $rootScope.wapo.email_list.max) {
        $rootScope.showDialog('Email Count Error', 'You have reached the max number of emails allowed!');
        return;
      } else if (!email_list.length) {
        $rootScope.showDialog('Email Count Error', 'Please enter at least 1 email!');
        return;
      }

      $scope.emails = email_list.join(',');
      $http.post('/wp/wapo/set/delivery/email-list/', {emails: email_list.join(','), delivery_message: $('#emails-delivery-message').val()}).success(function (response) {
        $rootScope.wapo = response.wapo;
        $location.path($scope.next_path);
      });
    };
    
    // TEXT
    $scope.keyUpText = function(event) {
      if(event.keyCode == 188 || event.keyCode == 32) {
        $scope.changeText();
      }
    };
    
    $scope.changeText = function () {
      var number_list = [];

      // Clean the numbers.
      if(!$('#numbers').length) {
        return;
      }
      
      var input_number_list = $('#numbers').val().split(',');
//      var input_number_list = $scope.numbers.split(',');
      _.map(input_number_list, function (number) {
//        var regexe = new RegExe('(\(|\)|\-|\s)', 'g')
        var cleaned = number.trim().replace(')', '').replace('(', '').replace('-', '').replace('-', '').replace(' ', '');
        if(cleaned.length !== 10) {
          $scope.error_number = number;
        }
        
        if (number.trim()) {
          number_list.push(number.trim());
        }
      });

      // Filter unique.
      number_list = _.unique(number_list, function (item) {
        return item;
      });

      $scope.count = number_list.length;
      
      return number_list;
    };

    $scope.initText = function () {
      $scope.count = $rootScope.wapo.text.number_list.length;
      $scope.numbers = $rootScope.wapo.text.number_list.join(',');
      $scope.delivery_message = $rootScope.wapo.delivery_message;
      $scope.changeText();
    };

    $scope.textNext = function () {
      $scope.error_number = '';
      
      var number_list = $scope.changeText();
      
      if($scope.error_number) {
        alert('Invalid number!', $scope.error_number);
        return;
      }

      // Validate the max count.
      if (number_list.length > $rootScope.wapo.text.max) {
        alert('You have entered more than the allowed numbers!');
        return;
      } else if (!number_list.length) {
        alert('Please enter at least one number!');
        return;
      }

      $scope.numbers = number_list.join(',');
      $http.post('/wp/wapo/set/delivery/text/', {numbers: number_list.join(','), delivery_message: $('#text-delivery-message').val()}).success(function (response) {
        $rootScope.wapo = response.wapo;
        $location.path($scope.next_path);
      }).error(function (errorResponse) {
        alert(errorResponse.message);
      });
    };
    
    $scope.init = function () {
      var delivery = $rootScope.wapo.delivery;
      console.log(delivery);
      
      if(delivery) {
        $scope.setDelivery(delivery);
      } else {
        $scope.setDelivery('email');
      }
    };
    $rootScope.mainInit(function () {
      $scope.init();
    });
    
    $scope.next = function() {
      if($scope.delivery == "email") {
        $scope.singleEmailNext();
      } else if($scope.delivery == "email-list") {
        $scope.emailListNext();
      } else if($scope.delivery == "text") {
        $scope.textNext();
      }
    };

  }]);

wapoApp.controller('FFACtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.setProgress(3);
    $rootScope.setTitle('Free for All', 'Enter Quantity and Delivery Message');

    $rootScope.previous_path = '/delivery';
    $rootScope.next_path = '/checkout';

    $scope.quantity = 0;

    $scope.next = function () {
      $scope.setFFA();
    };

    $scope.init = function () {
      $scope.quantity = $rootScope.wapo.quantity;
      $scope.delivery_message = $rootScope.wapo.delivery_message;
    };
    $rootScope.mainInit(function () {
      $scope.init();
    });

    $scope.setFFA = function () {
      if ($scope.quantity < 1) {
        $rootScope.showDialog('Quantity Error', 'Quantity must be greater than 0!');
        return;
      }

      $http.post('/wp/wapo/set/delivery/free-for-all/', {quantity: $scope.quantity, delivery_message: $scope.delivery_message}).success(function (response) {
        $rootScope.wapo = response.wapo;
        $location.path($rootScope.next_path);
      }).error(function (errorResponse) {
        $rootScope.showDialog('Error', errorResponse.message);
      });
    };
  }]);

wapoApp.controller('EmailCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.setProgress(3);
    $rootScope.setTitle('Email', 'Enter up to 4 emails to send to!');

    $scope.email_list = [];
    $scope.max_count = 1;
    $scope.delivery_message = "";

    $rootScope.previous_path = '/delivery';
    $rootScope.next_path = '/checkout';

    $scope.init = function () {
      console.log($rootScope.wapo.email.max);
      $scope.max_count = $rootScope.wapo.email.max;
      $scope.email_list = $rootScope.wapo.email.email_list;

      $scope.delivery_message = $rootScope.wapo.delivery_message;

      for (var x = $scope.email_list.length; x < $scope.max_count; x++) {
        $scope.email_list.push('');
      }
    };
    $rootScope.mainInit(function () {
      $scope.init();
    });

    $scope.addEmail = function () {
      console.log($scope.email_list);
      if ($scope.email_list.length < $scope.max_count) {
        $scope.email_list.push('');
      }
    };

    $scope.next = function () {
      var email_list = [];

      _.map($scope.email_list, function (email) {
        if (email.trim()) {
          email_list.push(email);
        }
      });

      if (!email_list.length) {
        alert("Please enter at least one email!");
        return;
      }

      $http.post('/wp/wapo/set/delivery/email/', {emails: email_list.join(','), delivery_message: $scope.delivery_message}).success(function (response) {
        $rootScope.wapo = response.wapo;
        $location.path($rootScope.next_path);
      });
    };

    $scope.clear = function () {
      $scope.delivery_message = '';
      $scope.email_list = [];

      for (var x = $scope.email_list.length; x < $scope.max_count; x++) {
        $scope.email_list.push('');
      }
    };
  }]);

wapoApp.controller('EmailListCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.setProgress(3);
    $rootScope.setTitle('Email List', 'Enter up to 25 emails to send to!');

    $scope.email_list = [];
    $scope.max_count = 1;
    $scope.emails = '';
    $scope.delivery_message = '';
    $scope.count = 0;

    $rootScope.previous_path = '/delivery';
    $rootScope.next_path = '/checkout';

    $scope.change = function () {
      $scope.email_list = [];

      // Clean the emails.
      var email_list = $scope.emails.split(',');
      _.map(email_list, function (email) {
        if (email.trim()) {
          $scope.email_list.push(email.trim());
        }
      });

      // Filter unique.
      $scope.email_list = _.unique($scope.email_list, function (item) {
        return item;
      });

      $scope.count = $scope.email_list.length;
    };

    $scope.init = function () {
      $scope.email_list = $rootScope.wapo.email_list.email_list;
      $scope.emails = $scope.email_list.join(',');
      $scope.delivery_message = $rootScope.wapo.delivery_message;
      $scope.change();
    };
    $rootScope.mainInit(function () {
      $scope.init();
    });

    $scope.next = function () {
      $scope.change();

      // Validate the max count.
      if ($scope.email_list.length > $rootScope.wapo.email_list.max) {
        $rootScope.showDialog('Email Count Error', 'You have reached the max number of emails allowed!');
        return;
      } else if (!$scope.email_list.length) {
        $rootScope.showDialog('Email Count Error', 'Please enter at least 1 email!');
        return;
      }

      $scope.emails = $scope.email_list.join(',');
      $http.post('/wp/wapo/set/delivery/email-list/', {emails: $scope.email_list.join(','), delivery_message: $scope.delivery_message}).success(function (response) {
        $rootScope.wapo = response.wapo;
        $location.path($scope.next_path);
      });
    };

    $scope.clear = function () {
      $scope.emails = '';
    };
  }]);

wapoApp.controller('MailChimpCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.setProgress(3);
    $rootScope.setTitle('', '');

    $scope.account = null;

    $scope.email_list = [];// Emails (strings) that have been picked..
    $scope.max_count = 1;
    $scope.subscription_id = null;
    $scope.subscription = null;// The selected subscription.
    $scope.selected_email_list = [];// List of selected emails (objects).
    $scope.remaining_email_list = [];// List of remaining emails (objects).

    $scope.delivery_message = "";

    $scope.selected_item = null;
    $scope.search_text = null;

    $rootScope.previous_path = '/delivery';
    $rootScope.next_path = '/checkout';

    $scope.getSubscriptionEmails = function () {
      $http.get('/wp/mailchimp/lists/members/?id=' + $scope.subscription.id).success(function (response) {
        $rootScope.subscription_email_list = response.data.data;

        // Check if any of the emails in this list have been picked.
        $scope.selected_email_list = _.filter($rootScope.subscription_email_list, function (item) {
          return ($scope.email_list.indexOf(item.email) > -1);
        });

        $scope.remaining_email_list = _.filter($rootScope.subscription_email_list, function (item) {
          return ($scope.email_list.indexOf(item.email) < 0);
        });

        // Chunk into 3.
        $scope.chunked_email_list = _.chunk($scope.remaining_email_list, 3);
      });
    };

    $scope.refresh = function () {
      // If we had previously selected a 'subscription', then find the object.
      if ($scope.subscription_id) {
        $scope.subscription = _.find($rootScope.subscription_list, function (item) {
          return (item.id == $scope.subscription_id);
        });
      }

      // Pick the first one if we don't have any.
      if (!$scope.subscription) {
        $scope.subscription = $rootScope.subscription_list[0];
      }

      // Load the list of emails for this subscription.
      if ($scope.subscription) {
        $scope.getSubscriptionEmails();
      }
    };

    $scope.getSubscriptionLists = function () {
      $http.get('/wp/mailchimp/lists/').success(function (response) {
        $rootScope.subscription_list = response.data.data;
        $scope.refresh();
      });
    };

    $scope.init = function () {
      $scope.account = $rootScope.wapo.mailchimp.account;
      $scope.subscription_id = $rootScope.wapo.mailchimp.subscription;
      $scope.email_list = $rootScope.wapo.mailchimp.email_list;
      $scope.max_count = $rootScope.wapo.mailchimp.max;

      $scope.delivery_message = $rootScope.wapo.delivery_message;

      // Fetch the list of subscription lists if we don't have any.
      if (!$rootScope.subscription_list.length) {
        if ($scope.account) {
          $scope.getSubscriptionLists();
        } else {
          $http.get('/mailchimp/authenticated/').success(function (response) {
            if (response.data) {
              $scope.account = response.data;
              $scope.getSubscriptionLists();
            }
          });
        }
      } else {
        $scope.refresh();
      }
    };
    $rootScope.mainInit(function () {
      $scope.init();
    });

    $scope.search = function (keyword) {
      var searched_email_list = [];

      if (keyword) {
        searched_email_list = _.filter($scope.remaining_email_list, function (item) {
          var text = item.email + ' ' + item.merges.FNAME + ' ' + item.merges.LNAME;
          return (text.search(new RegExp(keyword, 'i')) > -1);
        });
      } else {
        searched_email_list = $scope.remaining_email_list;
      }

      // Chunk them again.
      $scope.chunked_email_list = _.chunk(searched_email_list, 3);
    };

    $scope.addEmail = function (item) {
      // If we have already added the email, don't do anything.
      if ($scope.email_list.indexOf(item.email) > -1) {
        return;
      }

      // Add it to our selected and email list.
      $scope.selected_email_list.push(item);
      $scope.email_list.push(item.email);

      // Filter our the remaining ones.
      $scope.remaining_email_list = _.filter($scope.remaining_email_list, function (item) {
        return ($scope.email_list.indexOf(item.email) < 0);
      });

      // Chunk them again.
      if ($scope.search_text) {
        $scope.search($scope.search_text);
      } else {
        $scope.chunked_email_list = _.chunk($scope.remaining_email_list, 3);
      }

    };

    $scope.removeEmail = function (item) {
      $scope.email_list = _.filter($scope.email_list, function (email) {
        return (email != item.email);
      });

      // Check if any of the emails in this list have been picked.
      $scope.selected_email_list = _.filter($rootScope.subscription_email_list, function (item) {
        return ($scope.email_list.indexOf(item.email) > -1);
      });

      $scope.remaining_email_list = _.filter($rootScope.subscription_email_list, function (item) {
        return ($scope.email_list.indexOf(item.email) < 0);
      });

      // Chunk them again.
      $scope.chunked_email_list = _.chunk($scope.remaining_email_list, 3);
    };

    $scope.next = function () {
      if (!$scope.email_list.length) {
        alert('Please select at least one email!');
        return;
      }

      if ($scope.email_list.length > $scope.max_count) {
        alert('You have entered too many emails! You can only select a max of ' + $scope.max_count);
        return;
      }

      $http.post('/wp/wapo/set/delivery/mailchimp/', {subscription: $scope.subscription.id, emails: $scope.email_list.join(','), delivery_message: $scope.delivery_message}).success(function (response) {
        $rootScope.wapo = response.wapo;
        $location.path($scope.next_path);
      });
    };

    $scope.clear = function () {
      $scope.email_list = [];
      $scope.subscription_email_list = [];
      $scope.delivery_message = '';
    };
  }]);

wapoApp.controller('TextCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.setProgress(3);
    $rootScope.setTitle('Text SMS', 'Enter Phone numbers to send to!');

    $scope.error_number = '';
    $scope.number_list = [];
    $scope.max_count = 1;

    $scope.numbers = '';

    $scope.delivery_message = '';
    $scope.count = 0;

    $rootScope.previous_path = '/delivery';
    $rootScope.next_path = '/checkout';

    $scope.change = function () {
      $scope.number_list = [];

      // Clean the numbers.
      var number_list = $scope.numbers.split(',');
      _.map(number_list, function (number) {
        var cleaned = number.trim().replace(')', '').replace('(', '').replace('-', '').replace(' ', '');
        if(cleaned.length != 10) {
          $scope.error_number = number;
        }
        
        if (number.trim()) {
          $scope.number_list.push(number.trim());
        }
      });

      // Filter unique.
      $scope.number_list = _.unique($scope.number_list, function (item) {
        return item;
      });

      $scope.count = $scope.number_list.length;
    };

    $scope.init = function () {
      $scope.number_list = $rootScope.wapo.text.number_list;
      $scope.numbers = $scope.number_list.join(',');
      $scope.delivery_message = $rootScope.wapo.delivery_message;
      $scope.change();
    };
    $rootScope.mainInit(function () {
      $scope.init();
    });

    $scope.next = function () {
      $scope.error_number = '';
      $scope.change();
      
      if($scope.error_number) {
        alert('Invalid number!', $scope.error_number);
        return;
      }

      // Validate the max count.
      if ($scope.number_list.length > $rootScope.wapo.text.max) {
        alert('You have entered more than the allowed numbers!');
        return;
      } else if (!$scope.number_list.length) {
        alert('Please enter at least one number!');
        return;
      }

      $scope.numbers = $scope.number_list.join(',');
      $http.post('/wp/wapo/set/delivery/text/', {numbers: $scope.number_list.join(','), delivery_message: $scope.delivery_message}).success(function (response) {
        $rootScope.wapo = response.wapo;
        $location.path($scope.next_path);
      }).error(function (errorResponse) {
        alert(errorResponse.message);
      });
    };

    $scope.clear = function () {
      $scope.numbers = '';
    };
  }]);

wapoApp.controller('AnyTwitterFollowersCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.setProgress(3);
    $rootScope.setTitle('', '');

    $scope.account = null;
    $rootScope.previous_path = '/delivery';
    $rootScope.next_path = '/checkout';

    $scope.quantity = 0;
    $scope.delivery_message = "";

    $scope.init = function () {
      $scope.quantity = $rootScope.wapo.quantity;
      $scope.delivery_message = $rootScope.wapo.delivery_message;

      $http.get('/twitter/authenticated/').success(function (response) {
        $scope.account = response.account;
      });
    };
    $rootScope.mainInit(function () {
      $scope.init();
    });

    $scope.next = function () {
      if (!$scope.account) {
        alert('Please log into Twitter!');
        return;
      }

      if ($scope.quantity < 1) {
        alert("Please enter a valid quantity!");
        return;
      }

      $http.post('/wp/wapo/set/delivery/any-twitter-followers/', {quantity: $scope.quantity, delivery_message: $scope.delivery_message}).success(function (response) {
        $location.path($rootScope.next_path);
      });
    };
  }]);

wapoApp.controller('SelectTwitterFollowersCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.setProgress(3);
    $rootScope.setTitle('', '');

    $rootScope.previous_path = '/delivery';
    $rootScope.next_path = '/checkout';

    $scope.account = null;
    $scope.follower_list = [];
    $scope.chunked_follower_list = [];

    $scope.selected_follower_list = [];
    $scope.remaining_follower_list = [];

    $scope.delivery_message = "";

    $scope.init = function () {
      $scope.delivery_message = $rootScope.wapo.delivery_message;
      $scope.follower_list = $rootScope.wapo.twitter.follower_list;

      if (!$rootScope.followers.length) {
        $http.get('/twitter/authenticated/').success(function (response) {
          $scope.account = response.account;

          if ($scope.account) {
            $scope.getFollowers();
          }
        });
      } else {
        $scope.refresh();
      }
    };
    $rootScope.mainInit(function () {
      $scope.init();
    });

    $scope.refresh = function () {
      $scope.selected_follower_list = [];
      $scope.remaining_follower_list = [];

      _.map($rootScope.followers, function (item) {
        if (_.contains($scope.follower_list, item.screen_name)) {
          $scope.selected_follower_list.push(item);
        } else {
          $scope.remaining_follower_list.push(item);
        }
      });
    };

    $scope.getFollowers = function () {
      $http.get('/twitter/followers/').success(function (response) {
        $rootScope.followers = response.follower_list;
        $scope.refresh();
        $scope.chunked_follower_list = _.chunk($scope.remaining_follower_list, 3);
      });
    };

    $scope.search = function (keyword) {
      var searched_twitter_list = [];

      if (keyword) {
        searched_twitter_list = _.filter($scope.remaining_follower_list, function (item) {
          var text = item.name + ' ' + item.screen_name;
          return (text.search(new RegExp(keyword, 'i')) > -1);
        });
      } else {
        searched_twitter_list = $scope.remaining_follower_list;
      }

      // Chunk them again.
      $scope.chunked_follower_list = _.chunk(searched_twitter_list, 3);
    };

    $scope.addFollower = function (item) {
      // If we have already added the email, don't do anything.
      if ($scope.follower_list.indexOf(item.screen_name) > -1) {
        return;
      }

      // Add it to our selected and email list.
      $scope.selected_follower_list.push(item);
      $scope.follower_list.push(item.screen_name);

      // Filter out the remaining ones.
      $scope.remaining_follower_list = _.filter($scope.remaining_follower_list, function (item) {
        return ($scope.follower_list.indexOf(item.screen_name) < 0);
      });

      // Chunk them again.
      if ($scope.search_text) {
        $scope.search($scope.search_text);
      } else {
        $scope.chunked_follower_list = _.chunk($scope.remaining_follower_list, 3);
      }

    };

    $scope.removeFollower = function (item) {
      $scope.follower_list = _.filter($scope.follower_list, function (screen_name) {
        return (screen_name != item.screen_name);
      });

      // Check if any of the emails in this list have been picked.
      $scope.selected_follower_list = [];
      $scope.remaining_follower_list = [];

      _.map($rootScope.followers, function (item) {
        if ($scope.follower_list.indexOf(item.email) > -1) {
          $scope.selected_follower_list.push(item);
        } else {
          $scope.remaining_follower_list.push(item);
        }
      });

      // Chunk them again.
      $scope.chunked_follower_list = _.chunk($scope.remaining_follower_list, 3);
    };

    $scope.next = function () {
      if (!$scope.follower_list.length) {
        alert("Please select at least one follower!");
        return;
      }

      $http.post('/wp/wapo/set/delivery/select-twitter-followers/', {followers: $scope.follower_list.join(','), delivery_message: $scope.delivery_message}).success(function (response) {
        $location.path($rootScope.next_path);
      }).error(function (errorResponse) {
        alert(errorResponse.message);
      });
    };
  }]);

wapoApp.controller('AnyFacebookFriendsCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.setProgress(3);
    $rootScope.setTitle('', '');

    $scope.profile = null;
    $rootScope.previous_path = '/delivery';
    $rootScope.next_path = '/checkout';

    $scope.quantity = 0;
    $scope.delivery_message = "";

    $scope.init = function () {
      $scope.quantity = $rootScope.wapo.quantity;
      $scope.delivery_message = $rootScope.wapo.delivery_message;

      $http.get('/facebook/authenticated/').success(function (response) {
        $scope.profile = response.profile;
      });
    };
    $rootScope.mainInit(function () {
      $scope.init();
    });

    $scope.next = function () {
      if ($scope.quantity < 1) {
        alert("Please enter a valid quantity!");
        return;
      }

      $http.post('/wp/wapo/set/delivery/any-facebook-friends/', {quantity: $scope.quantity, delivery_message: $scope.delivery_message}).success(function (response) {
        $rootScope.wapo = response.wapo;
        $location.path($rootScope.next_path);
      });
    };
  }]);

wapoApp.controller('FacebookPageCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.setProgress(3);
    $rootScope.setTitle('', '');

    $rootScope.previous_path = '/delivery';
    $rootScope.next_path = '/checkout';

    $scope.profile = null;

    $scope.delivery_message = "";

    $scope.page = null;
    $scope.page_id = null;
    $scope.chunked_page_list = [];

    $scope.refresh = function () {
      $scope.page = _.find($rootScope.pages, function (item) {
        return (item.id = $scope.page_id);
      });
    };

    $scope.getPages = function () {
      $http.get('/facebook/pages/').success(function (response) {
        $rootScope.pages = response.page_list;
        $scope.chunked_page_list = _.chunk($scope.pages, 3);
        $scope.refresh();
      });
    };

    $scope.init = function () {
      $scope.delivery_message = $rootScope.wapo.delivery_message;
      $scope.page_id = $rootScope.wapo.facebook.page;
      $scope.profile = $rootScope.wapo.facebook.profile;
      $scope.quantity = $rootScope.wapo.quantity;

      if (!$rootScope.pages.length) {
        if (!$scope.profile) {
          $http.get('/facebook/authenticated/').success(function (response) {
            $scope.profile = response.profile;

            if ($scope.profile) {
              $scope.getPages();
            }
          });
        } else {
          $scope.getPages();
        }
      } else {
        $scope.chunked_page_list = _.chunk($scope.pages, 3);
        $scope.refresh();
      }
    };
    $rootScope.mainInit(function () {
      $scope.init();
    });

    $scope.search = function (keyword) {
      var searched_page_list = [];

      if (keyword) {
        searched_page_list = _.filter($scope.pages, function (item) {
          var text = item.name;
          return (text.search(new RegExp(keyword, 'i')) > -1);
        });
      } else {
        searched_page_list = $scope.pages;
      }

      // Chunk them again.
      $scope.chunked_page_list = _.chunk(searched_page_list, 3);
    };

    $scope.setPage = function (item) {
      $scope.page = item;
    };

    $scope.next = function () {
      if (!$scope.page) {
        alert("Please select a page!");
        return;
      }

      if ($scope.quantity < 1) {
        alert('Please add a quantity > 0');
        return;
      }

      $http.post('/wp/wapo/set/delivery/facebook-page/', {page: $scope.page.id, name: $scope.page.name, delivery_message: $scope.delivery_message, quantity: $scope.quantity}).success(function (response) {
        $rootScope.wapo = response.wapo;
        $location.path($rootScope.next_path);
      }).error(function (errorResponse) {
        alert(errorResponse.message);
      });
    };

  }]);

wapoApp.controller('CheckoutCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.setProgress(4);
    $rootScope.setTitle('Checkout', '');

    $scope.valid = false;

    $rootScope.previous_path = '/delivery';
    $rootScope.next_path = null;
    $scope.hide_next = true;
    $scope.payment_method = "wepay";
    
    $scope.payment_method_list = [];
    
    $scope.free = false;
    $scope.processing = false;
    
    // Check if wapo is free!
    $scope.isFree = function() {
      if($scope.wapo.marketplace == 'promotion') {
        if($scope.wapo.promotion.price == 0) {
          return true;
        }
      }
      
      return false;
    };

    $scope.init = function () {
      $scope.account = $rootScope.wapo.twitter.account;
      $scope.profile = $rootScope.wapo.facebook.profile;
//      $rootScope.previous_path += '/' + $rootScope.wapo.delivery;
      
      
      if($scope.isFree()) {
        $rootScope.next_path = '/free';
        $scope.payment_method = "free";
        $scope.payment_method_list.push('free');
        $scope.free = true;
      } else {
        $rootScope.next_path = '/payment';
        $scope.payment_method = "wepay";
        $scope.payment_method_list.push('wepay');
        $scope.free = false;
      }
      
      WePay.set_endpoint("stage");
    };
    $rootScope.mainInit(function () {
      $scope.init();
    });

    $scope.setPaymentMethod = function (payment_method) {
      $scope.payment_method = payment_method;
      $rootScope.next_path = '/payment';
    };

    $scope.next = function () {
      $rootScope.previous_path = null;
      $rootScope.next_path = null;
      $scope.processing = true;
      
      if($scope.isFree()) {
        $location.path($rootScope.next_path);
      } else {
//        if (!$scope.payment_method) {
//          alert('Please select a payment method!');
//          return;
//        }

        $http.post('/wp/wapo/validate/', {}).success(function (response) {
          $scope.tokenize(response.client_id);
          
          
//          if ($scope.payment_method == "wepay") {
//            $rootScope.setPath('/payment', response.wepay.hosted_checkout.checkout_uri);
//          }
        }).error(function (errorResponse) {
          $scope.uprocess();
          
          alert(errorResponse.message);
        });
      }
    };
    
    $scope.tokenize = function(client_id) {
      console.log('tokenize?');
      if(!$scope.checkout) {
        alert('Please fill out payment information!');
        $scope.uprocess();
        return;
      }
      
      var response = WePay.credit_card.create({
            "client_id":        client_id,
            "user_name":        $scope.checkout.name,
            "email":            $scope.checkout.email,
            "cc_number":        $scope.checkout.cc_number,
            "cvv":              $scope.checkout.cc_cvv,
            "expiration_month": $scope.checkout.cc_month,
            "expiration_year":  $scope.checkout.cc_year,
            "address": {
                "zip": $scope.checkout.zip
            }
        }, function(data) {
          console.log('tokenize.data', data);
            if (data.error) {
              alert('There seems to be an error processing your credit card!');
              $scope.uprocess();
              
                console.log(data);
                // handle error response
            } else {
                $http.post('/wp/wapo/checkout/create/', {credit_card_id: data.credit_card_id}).success(function(response) {
                  if(response.wepay.checkout_id) {
                    $location.path('/payment');
                  }
                }).error(function(errorResponse) {
                  alert(errorResponse.message);
                });
            }
        });
    };
    
    $scope.uprocess = function() {
      $rootScope.previous_path = '/delivery';
      if ($scope.isFree()) {
        $rootScope.next_path = '/free';
      } else {
        $rootScope.next_path = '/payment';
      }
      $scope.processing = false;
    };
    
  }]);

wapoApp.filter('filenameFilter', function () {
  return function (input) {
    return input.replace('-', ' ');
  };
}).controller('PaymentCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.setProgress(4);
    $rootScope.setTitle('Processing', '');

    $scope.message = "Checking payment...";

    $scope.init = function () {
      $http.post('/wp/wapo/payment/').success(function (response) {
        $scope.create();
      }).error(function (errorResponse) {
        alert(errorResponse.message);
        $location.path('/checkout');
      });
    };
    $rootScope.mainInit(function () {
      $scope.init();
    });

    $scope.create = function () {
      $scope.message = "Creating Wapo...";
      $http.post('/wp/wapo/create/').success(function (response) {
        if (response.wapo_id) {
          $scope.send(response.wapo_id);
        } else {
          alert(response.message);
        }
      }).error(function (errorResponse) {
        alert(errorResponse.message);
      });
    };

    $scope.send = function (wapo_id) {
      $scope.message = "Sending Wapo...";
      $http.post('/wp/wapo/send/', {wapo_id: wapo_id}).success(function (response) {
        $location.path('/confirmation');
      }).error(function (errorResponse) {
        alert(errorResponse.message);
      });
    };
  }]);

wapoApp.controller('FreeCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.setProgress(4);
    $scope.progress = true;
    $rootScope.setTitle('Processing', '');

    $scope.previous_path = '/checkout';
    $scope.message = "Validating...";

    $scope.init = function () {
      $http.post('/wp/wapo/validate/', {payment_method: 'free'}).success(function (response) {
        $scope.checkFree();
      }).error(function (errorResponse) {
        $scope.progress = false;
        $scope.message = errorResponse.message;
      });
    };
    $rootScope.mainInit(function () {
      $scope.init();
    });
    
    $scope.checkFree = function() {
      $scope.message = "Checking free...";
      $http.post('/wp/wapo/free/').success(function (response) {
        $scope.create();
      }).error(function (errorResponse) {
        $scope.progress = false;
        $scope.message = errorResponse.message;
      });
    };

    $scope.create = function () {
      $scope.message = "Creating Wapo...";
      $http.post('/wp/wapo/create/').success(function (response) {
        if (response.wapo_id) {
          $scope.send(response.wapo_id);
        } else {
          $scope.progress = false;
          $scope.message = "Could not create wapo!";
        }
      }).error(function (errorResponse) {
        $scope.progress = false;
        $scope.message = errorResponse.message;
      });
    };

    $scope.send = function (wapo_id) {
      $scope.message = "Sending Wapo...";
      $http.post('/wp/wapo/send/', {wapo_id: wapo_id}).success(function (response) {
        $location.path('/confirmation');
      }).error(function (errorResponse) {
        $scope.progress = false;
        $scope.message = errorResponse.message;
      });
    };
  }]);

wapoApp.controller('ConfirmationCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.setProgress(5);
    $rootScope.setTitle('Confirmation', 'Thank you for your order!');

    $scope.init = function () {
      $http.get('/wp/wapo/confirmation/').success(function (response) {
        $scope.wapo = response.wapo;
      });
    };
    $scope.init();

//    $scope.startOver = function () {
//      $http.get('/wp/wapo/start-over/').success(function (response) {
//        $location.path('/module');
//      });
//    };
//
//    $scope.sendAnother = function () {
//      $location.path('/module');
//    };


  }]);


wapoApp.controller('StartOverCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $scope.init = function () {
      $http.get('/wp/wapo/start-over/').success(function (response) {
        $rootScope.wapo = response.wapo;
        $location.path('/module');
      });
    };
    $scope.init();
  }]);

wapoApp.config(function ($routeProvider) {
  $routeProvider.when('/', {
    templateUrl: '/apps/wp/templates/wapo/pages/module.html',
    controller: 'ModuleCtrl'
  }).when('/profile', {
    templateUrl: '/apps/wp/templates/wapo/pages/profile.html',
    controller: 'ProfileCtrl'
  }).when('/profile-new', {
    templateUrl: '/apps/wp/templates/wapo/pages/profile-new.html',
    controller: 'ProfileNewCtrl'
  }).when('/marketplace', {
    templateUrl: '/apps/wp/templates/wapo/pages/marketplace.html',
    controller: 'MarketplaceCtrl'
  }).when('/marketplace/tangocards', {
    templateUrl: '/apps/wp/templates/wapo/pages/marketplace-tangocards.html',
    controller: 'TangoCardsCtrl'
  }).when('/marketplace/promotion', {
    templateUrl: '/apps/wp/templates/wapo/pages/marketplace-promotion.html',
    controller: 'PromotionCtrl'
  }).when('/marketplace/promotion/:promotioncategory_id', {
    templateUrl: '/apps/wp/templates/wapo/pages/marketplace-promotion.html',
    controller: 'PromotionCtrl'
  }).when('/delivery', {
    templateUrl: '/apps/wp/templates/wapo/pages/delivery.html',
    controller: 'DeliveryCtrl'
  }).when('/delivery/email', {
    templateUrl: '/apps/wp/templates/wapo/pages/email.html',
    controller: 'EmailCtrl'
  }).when('/delivery/email-list', {
    templateUrl: '/apps/wp/templates/wapo/pages/email-list.html',
    controller: 'EmailListCtrl'
  }).when('/delivery/free-for-all', {
    templateUrl: '/apps/wp/templates/wapo/pages/free-for-all.html',
    controller: 'FFACtrl'
  }).when('/delivery/mailchimp', {
    templateUrl: '/apps/wp/templates/wapo/pages/mailchimp.html',
    controller: 'MailChimpCtrl'
  }).when('/delivery/text', {
    templateUrl: '/apps/wp/templates/wapo/pages/text.html',
    controller: 'TextCtrl'
  }).when('/delivery/any-twitter-followers', {
    templateUrl: '/apps/wp/templates/wapo/pages/any-twitter-followers.html',
    controller: 'AnyTwitterFollowersCtrl'
  }).when('/delivery/select-twitter-followers', {
    templateUrl: '/apps/wp/templates/wapo/pages/select-twitter-followers.html',
    controller: 'SelectTwitterFollowersCtrl'
  }).when('/delivery/any-facebook-friends', {
    templateUrl: '/apps/wp/templates/wapo/pages/any-facebook-friends.html',
    controller: 'AnyFacebookFriendsCtrl'
  }).when('/delivery/facebook-page', {
    templateUrl: '/apps/wp/templates/wapo/pages/facebook-page.html',
    controller: 'FacebookPageCtrl'
  }).when('/checkout', {
    templateUrl: '/apps/wp/templates/wapo/pages/checkout.html',
    controller: 'CheckoutCtrl'
  }).when('/payment', {
    templateUrl: '/apps/wp/templates/wapo/pages/payment.html',
    controller: 'PaymentCtrl'
  }).when('/free', {
    templateUrl: '/apps/wp/templates/wapo/pages/free.html',
    controller: 'FreeCtrl'
  }).when('/confirmation', {
    templateUrl: '/apps/wp/templates/wapo/pages/confirmation.html',
    controller: 'ConfirmationCtrl'
  }).when('/start-over', {
    templateUrl: '/apps/wp/templates/wapo/pages/start-over.html',
    controller: 'StartOverCtrl'
  }).otherwise({
    redirectTo: '/'
  });
});
