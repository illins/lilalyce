var wapoApp = angular.module('wapoApp', ['ngRoute', 'ngResource', 'ngMaterial', 'ngFileUpload', 'ui.bootstrap', 'ngCookies'], function ($httpProvider) {
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

wapoApp.controller('MainCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', '$mdDialog', '$cookies', function ($rootScope, $scope, $location, $http, $routeParams, $mdDialog, $cookies) {
    $scope.progress = 0;

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
      var path = $cookies.get('path');
      if (path) {
        $cookies.remove('path');
        $location.path(path);
        return;
      }

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

    $rootScope.setProgress = function (progress) {
      $scope.progress = progress;
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
      $mdDialog.show(
              $mdDialog.alert()
              .parent(angular.element(document.body))
              .clickOutsideToClose(true)
              .title(title)
              .textContent(text)
              .ariaLabel('Alert Dialog Demo')
              .ok('Got it!')
//        .targetEvent(ev)
              );
    };
  }]);

wapoApp.controller('ModuleCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.previous_path = null;
    $rootScope.next_path = '/profile';

    $scope.md_group_list = [];

    $scope.init = function () {
      if ($rootScope.wapo.module) {
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
    };
    $rootScope.mainInit(function () {
      $scope.init();
    });

    $scope.setModule = function (module) {
      $http.post('/wp/wapo/set/module/', {module_id: module.id}).success(function (response) {
        $rootScope.wapo = response.wapo;
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
    $rootScope.setProgress(1);

    $rootScope.previous_path = null;
    $rootScope.next_path = '/marketplace';

    $scope.profile = null;
    $scope.profile_chunk_list = [];

    $scope.init = function () {
      $scope.profile = $rootScope.wapo.profile.profile;

      if (!$rootScope.profile_list.length) {
        $http.get('/wp/wapo/profile/').success(function (response) {
          $rootScope.profile_list = response.profile_list;
          $scope.profile_chunk_list = _.chunk($rootScope.profile_list, 3);

          // If no profile, show new profile form.
          if (!$rootScope.profile_list.length) {
            $location.path('/profile-new');
          }
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
        console.log($rootScope.next_path);
        $location.path($rootScope.next_path);
      });
    };
  }]);

wapoApp.controller('ProfileNewCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', 'Upload', function ($rootScope, $scope, $location, $http, $routeParams, Upload) {
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

    // Redirect to the correct marketplace.
    $scope.init = function () {
      var marketplace = ($rootScope.wapo.marketplace) ? $rootScope.wapo.marketplace : 'tangocards';
      $location.path('/marketplace/' + marketplace);
    };
    $rootScope.mainInit(function () {
      $scope.init();
    });
  }]);

wapoApp.controller('TangoCardsCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', 'filterFilter', function ($rootScope, $scope, $location, $http, $routeParams, filterFilter) {
    $rootScope.setProgress(2);

    $rootScope.previous_path = '/profile';
    $rootScope.next_path = '/delivery';

    $scope.tangocards;
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
        return item.unit_price != -1;
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
      if (!$rootScope.tangocards_list.length) {
        $http.get('/wp/wapo/tangocards/').success(function (response) {
          $rootScope.tangocards_list = response.tangocardrewards_list;
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
    };

    $scope.selectTangoCards = function (tangocards) {
      $scope.tangocards = tangocards;
    };

    $scope.next = function () {
      if (!$scope.tangocards) {
        alert('Please select a reward!');
        return;
      }

      $http.post('/wp/wapo/set/tangocards/', {tangocards_id: $scope.tangocards.id}).success(function (response) {
        $rootScope.wapo = response.wapo;
        $location.path($rootScope.next_path);
      }).error(function (errorResponse) {
        alert(errorResponse.message);
      });
    };
  }]);

wapoApp.controller('PromotionCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', 'filterFilter', function ($rootScope, $scope, $location, $http, $routeParams, filterFilter) {
    $rootScope.setProgress(2);

    $rootScope.previous_path = '/profile';
    $rootScope.next_path = '/delivery';

    $scope.promotioncategory = {};
    
    $scope.promotion = null;
    $scope.promotion_list = [];
    
    $scope.chunked_promotion_list = [];
    
    $scope.changePromotionCategory = function() {
//      $scope.promotioncategory = promotioncategory;
      console.log($scope.promotioncategory);
      $scope.getPromotions();
    };
    
    $scope.getPromotions = function() {
      var url = '/wp/wapo/promotions/?promotioncategory=';
      
      if($scope.promotioncategory.id) {
        url += $scope.promotioncategory.id;
      } else {
        if($scope.promotion) {
          $scope.promotioncategory = $scope.promotion.promotioncategory;
          url += $scope.promotion.promotioncategory.id;
        } else {
          $scope.promotioncategory = $rootScope.promotioncategory_list[0];
          url += $rootScope.promotioncategory_list[0].id;
        }
      }
      
      url = '/wp/wapo/promotions/';
      $http.get(url).success(function(response) {
        $scope.promotion_list = response;
        $scope.chunked_promotion_list = _.chunk($scope.promotion_list, 3);
      });
    };


    $scope.init = function () {
      $scope.promotion = $rootScope.wapo.promotion;
      
      if($rootScope.promotioncategory_list.length) {
        $scope.getPromotions();
      } else {
        $http.get('/wp/wapo/promotioncategories/').success(function(response) {
          $rootScope.promotioncategory_list = response;
          
          if($rootScope.promotioncategory_list.length) {
            $scope.getPromotions();
          }
        });
      }

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
  }]);

wapoApp.controller('DeliveryCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.setProgress(3);

    $scope.delivery = null;
    $scope.main_delivery = 'free-for-all';
    $scope.enabled_list = [];

    $rootScope.next_path = null;
    $rootScope.previous_path = '/marketplace';

    $scope.init = function () {
      $scope.delivery = $rootScope.wapo.delivery;
      if ($scope.delivery) {
        $rootScope.next_path = '/delivery/' + $scope.delivery;

        if ($scope.delivery.search(/free/) != -1) {
          $scope.main_delivery = 'free-for-all';
        } else if ($scope.delivery.search(/mail/) != -1) {
          $scope.main_delivery = 'email';
        } else if ($scope.delivery.search(/text/) != -1) {
          $scope.main_delivery = 'text';
        } else if ($scope.delivery.search(/facebook/) != -1) {
          $scope.main_delivery = 'facebook';
        } else if ($scope.delivery.search(/twitter/) != -1) {
          $scope.main_delivery = 'twitter';
        } else {
          $scope.main_delivery = 'email';
        }
      } else {
        $scope.main_delivery = 'email';
      }
    };
    $rootScope.mainInit(function () {
      $scope.init();
    });

    $scope.setDelivery = function (delivery) {
      $rootScope.next_path = '/delivery/' + delivery;
    };

    $scope.checked = function (delivery) {
      return ($scope.delivery == delivery);
    };

    $scope.active = function (delivery) {
      return ($scope.main_delivery == delivery);
    };

  }]);

wapoApp.controller('FFACtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', '$mdDialog', function ($rootScope, $scope, $location, $http, $routeParams, $mdDialog) {
    $rootScope.setProgress(3);

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

    $scope.email_list = [];
    $scope.max_count = 1;
    $scope.delivery_message = "";

    $rootScope.previous_path = '/delivery';
    $rootScope.next_path = '/checkout';

    $scope.init = function () {
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
      $scope.change();

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

    $scope.valid = false;

    $rootScope.previous_path = '/delivery';
    $rootScope.next_path = null;
    $scope.hide_next = true;
    $scope.payment_method = "";
    
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
      $rootScope.previous_path += '/' + $rootScope.wapo.delivery;
      
      
      if($scope.isFree()) {
        $rootScope.next_path = '/free';
      }
    };
    $rootScope.mainInit(function () {
      $scope.init();
    });

    $scope.setPaymentMethod = function (payment_method) {
      $scope.payment_method = payment_method;
      $rootScope.next_path = '/payment';
    };

    $scope.next = function () {
      if($scope.isFree()) {
        $location.path($rootScope.next_path);
      } else {
        if (!$scope.payment_method) {
          alert('Please select a payment method!');
          return;
        }

        $http.post('/wp/wapo/validate/', {payment_method: $scope.payment_method}).success(function (response) {
          if ($scope.payment_method == "wepay") {
            $rootScope.setPath('/payment', response.wepay.hosted_checkout.checkout_uri);
          }
        }).error(function (errorResponse) {
          alert(errorResponse.message);
        });
      }
    };
  }]);

wapoApp.filter('filenameFilter', function () {
  return function (input) {
    return input.replace('-', ' ');
  };
}).controller('PaymentCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.setProgress(4);

    $scope.message = "Checking payment...";

    $scope.init = function () {
      $http.post('/wp/wapo/payment/').success(function (response) {
        $scope.create();
      }).error(function (errorResponse) {
        alert(errorResponse.message);
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

    $scope.message = "Checking free...";

    $scope.init = function () {
      $http.post('/wp/wapo/free/').success(function (response) {
        $scope.create();
      }).error(function (errorResponse) {
        alert(errorResponse.message);
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

wapoApp.controller('ConfirmationCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.setProgress(5);

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
