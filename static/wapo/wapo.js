var wapoApp = angular.module('wapoApp', ['ngRoute', 'ngResource', 'ngMaterial', 'ngFileUpload'], function ($httpProvider) {
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

wapoApp.controller('MainCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', '$mdDialog', function ($rootScope, $scope, $location, $http, $routeParams, $mdDialog) {
    $rootScope.user = null;
    $rootScope.wapo = null;


    $rootScope.module_list = [];

    $rootScope.profile_list = [];

    $rootScope.tangocards_list = [];

    $rootScope.subscription_list = [];
    $rootScope.subscription_email_list = [];

    $scope.init = function () {
      $http.get('/wp/wapo/data/').success(function (response) {
        $rootScope.user = response.blink.request.user;
        $rootScope.wapo = response.wapo;
        $scope.uobj = $rootScope.user;
        console.log('wapo', $rootScope.wapo);
        console.log('user', $rootScope.user);
      });
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

    $rootScope.go = function (path) {
      $location.path(path);
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

    // Run init only when 'wapo' is set.
    $scope.$watch('wapo', function (newValue, oldValue) {
      if (!oldValue && newValue) {
        $scope.init();
      }
    });

    $scope.init = function () {
      if (!$rootScope.module_list.length) {
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
      }
    };

    $scope.setModule = function (module) {
      $http.post('/wp/wapo/set/module/', {module_id: module.id}).success(function (response) {
        $rootScope.wapo = response.wapo;
        $location.path('/profile');
      }).error(function (errorResponse) {
        console.log(errorResponse);
      });
    };

    $scope.css = function (tag, name) {
      return (tag === name);
    };
  }]);

wapoApp.controller('ProfileCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.previous_path = null;
    $rootScope.next_path = null;

    $scope.profile = null;

    // Run init only when 'wapo' is set.
    $scope.$watch('wapo', function (newValue, oldValue) {
      if (!oldValue && newValue) {
        $scope.init();
      }
    });

    $scope.init = function () {
      $scope.profile = $rootScope.wapo.profile.profile;

      if ($scope.profile) {
        $rootScope.next_path = '/marketplace';
      }

      if (!$rootScope.profile_list.length) {
        $http.get('/wp/wapo/profile/').success(function (response) {
          $rootScope.profile_list = response.profile_list;

//          if(!$rootScope.profile_list.length) {
//            $location.path('/profile-new');
//          }
        });
      }
    };

    $scope.selectProfile = function (profile) {
      $scope.profile = profile;
    };

    $scope.setProfile = function () {
      $http.post('/wp/wapo/set/profile/', $scope.profile).success(function (response) {
        $rootScope.wapo = response.wapo;
        $rootScope.next_path = '/marketplace';
//        $location.path('/marketplace');
      });
    };
  }]);

wapoApp.controller('ProfileNewCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', 'Upload', function ($rootScope, $scope, $location, $http, $routeParams, Upload) {
    $rootScope.previous_path = null;
    $rootScope.next_path = '/marketplace';

    $scope.profile = {};
    $scope.delete_image;

    // Run init only when 'wapo' is set.
    $scope.$watch('wapo', function (newValue, oldValue) {
      if (!oldValue && newValue) {
        $scope.init();
      }
    });
    
    $scope.$watch('delete_image', function (newValue, oldValue) {
      console.log(newValue);
    });

    $scope.init = function () {
      $scope.profile = $rootScope.wapo.profile.new;
    };
    
    $scope.clear_image = function() {
      $scope.file = null;
    };

    $scope.next = function () {
      var data = angular.merge({}, $scope.profile);
      
      if(!data.name.trim() || !data.email.trim()) {
        $rootScope.showDialog('Error!', 'Please enter missing data!');
      }
      
      if($scope.file) {
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
        if(angular.element('#delete_image')[0].checked) {
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
    // Run init only when 'wapo' is set.
    $scope.$watch('wapo', function (newValue, oldValue) {
      if (!oldValue && newValue) {
        $scope.init();
      }
    });

    // Redirect to the correct marketplace.
    $scope.init = function () {
      var marketplace = ($rootScope.wapo.marketplace) ? $rootScope.wapo.marketplace : 'tangocards';
      $location.path('/marketplace/' + marketplace);
    };
  }]);

wapoApp.controller('TangoCardsCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', 'filterFilter', function ($rootScope, $scope, $location, $http, $routeParams, filterFilter) {
    $rootScope.previous_path = '/profile';
    $rootScope.next_path = null;

    $scope.tangocards_group_list = [];
    $scope.brand_list = [];

    $scope.selected_brand_description;

    // Run init only when 'wapo' is set.
    $scope.$watch('wapo', function (newValue, oldValue) {
      if (!oldValue && newValue) {
        $scope.init();
      }
    });

    $scope.$watch('selected_brand_description', function (newValue, oldValue) {
      if (newValue) {
        $scope.applyFilter();
      }
    });

    $scope.initFilters = function () {
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

    // Filter based on brand.
    $scope.applyFilter = function () {
      var filtered_tangocards_list = filterFilter($rootScope.tangocards_list, $scope.selected_brand_description);
      $scope.tangocards_group_list = _.chunk(filtered_tangocards_list, 3);
    };

    $scope.setTangoCards = function (tangocards) {
      $http.post('/wp/wapo/set/tangocards/', {tangocards_id: tangocards.id}).success(function (response) {
        $rootScope.wapo = response.wapo;
        $rootScope.next_path = '/delivery';
      }).error(function (errorResponse) {

      });
    };
  }]);

wapoApp.controller('DeliveryCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $scope.delivery = null;
    $scope.main_delivery = 'free-for-all';
    $scope.enabled_list = [];
    
    $rootScope.next_path = null;
    $rootScope.previous_path = '/marketplace';

    // Run init only when 'wapo' is set.
    $scope.$watch('wapo', function (newValue, oldValue) {
      if (!oldValue && newValue) {
        $scope.init();
      }
    });

    $scope.init = function () {
      $scope.delivery = $rootScope.wapo.delivery;
      if($scope.delivery) {
        $rootScope.next_path = '/delivery/' + $scope.delivery;
        
        if($scope.delivery.search(/free/) != -1) {
          $scope.main_delivery = 'free-for-all';
        } else if($scope.delivery.search(/email/) != -1) {
          $scope.main_delivery = 'email';
        } else if($scope.delivery.search(/text/) != -1) {
          $scope.main_delivery = 'text';
        } else if($scope.delivery.search(/facebook/) != -1) {
          $scope.main_delivery = 'facebook';
        } else if($scope.delivery.search(/twitter/) != -1) {
          $scope.main_delivery = 'twitter';
        }
      }      
      
    };

    $scope.setDelivery = function (delivery) {
      $rootScope.next_path = '/delivery/' + delivery;
    };
    
    $scope.checked = function (delivery) {
      return ($scope.delivery == delivery);
    };
    
    $scope.active = function(delivery) {
      return ($scope.main_delivery == delivery);
    };
    
  }]);

wapoApp.controller('FFACtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', '$mdDialog', function ($rootScope, $scope, $location, $http, $routeParams, $mdDialog) {
    $rootScope.previous_path = '/delivery';
    $rootScope.next_path = '/checkout';

    $scope.quantity = 0;

    $scope.next = function () {
      $scope.setFFA();
    };

    // Run init only when 'wapo' is set.
    $scope.$watch('wapo', function (newValue, oldValue) {
      if (!oldValue && newValue) {
        $scope.init();
      }
    });

    $scope.init = function () {
      $scope.quantity = $rootScope.wapo.quantity;
      $scope.delivery_message = $rootScope.wapo.delivery_message;
    };

    $scope.setFFA = function () {
      if ($scope.quantity < 1) {
        $rootScope.showDialog('Quantity Error', 'Quantity must be greater than 0!');
        return;
      }

      $http.post('/wp/wapo/set/delivery/free-for-all/', {quantity: $scope.quantity, delivery_message: $scope.delivery_message}).success(function (response) {
        $rootScope.wapo = response.wapo;
        $location.path($rootScope.next_path);
      }).error(function(errorResponse) {
        $rootScope.showDialog('Error', errorResponse.message);
      });
    };
  }]);

wapoApp.controller('EmailCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $scope.email_list = [];
    $scope.max_count = 1;
    
    $rootScope.previous_path = '/marketplace';
    $rootScope.next_path = '/checkout';
    
    $scope.next = function() {
      $scope.setEmail();
    };

    // Run init only when 'wapo' is set.
    $scope.$watch('wapo', function (newValue, oldValue) {
      if (!oldValue && newValue) {
        $scope.init();
      }
    });

    $scope.init = function () {
      $scope.max_count = $rootScope.wapo.email.max;
      $scope.email_list = $rootScope.wapo.email.email_list;
      
      for(var x = $scope.email_list.length; x < $scope.max_count; x++) {
        $scope.email_list.push('');
      }
    };

    $scope.addEmail = function () {
      console.log($scope.email_list);
      if ($scope.email_list.length < $scope.max_count) {
        $scope.email_list.push('');
      }
    };

    $scope.setEmail = function () {
      var email_list = [];

      _.map($scope.email_list, function (email) {
        if (email.trim()) {
          email_list.push(email);
        }
      });
      
      if(!email_list.length) {
        alert("Please enter at least one email!");
        return;
      }
      
      $http.post('/wp/wapo/set/delivery/email/', {emails: email_list.join(',')}).success(function (response) {
        $rootScope.wapo = response.wapo;
        $location.path($rootScope.next_path);
      });
    };

    $scope.clear = function () {
      $scope.email_list = [];
      
      for(var x = $scope.email_list.length; x < $scope.max_count; x++) {
        $scope.email_list.push('');
      }
    };
  }]);

wapoApp.controller('EmailListCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $scope.email_list = [];
    $scope.max_count = 1;
    $scope.emails = '';
    $scope.delivery_message = '';
    $scope.count = 0;

    $rootScope.previous_path = '/delivery';
    $rootScope.next_path = '/checkout';

    $scope.next = function () {
      $scope.setEmailList();
    };

    // Run init only when 'wapo' is set.
    $scope.$watch('wapo', function (newValue, oldValue) {
      if (!oldValue && newValue) {
        $scope.init();
      }
    });

    $scope.init = function () {
      $scope.email_list = $rootScope.wapo.email_list.email_list;
      $scope.emails = $scope.email_list.join(',');
      $scope.delivery_message = $rootScope.wapo.delivery_message;
      $scope.change();
    };

    $scope.setEmailList = function () {
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
      $http.post('/wp/wapo/set/delivery/email-list/', {emails: $scope.email_list.join(',')}).success(function (response) {
        $rootScope.wapo = response.wapo;
        $location.path($scope.next_path);
      });
    };
    
    $scope.change = function() {
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

    $scope.clear = function () {
      $scope.emails = '';
    };
  }]);

wapoApp.controller('MailChimpCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $scope.email_list = [];// Emails (strings) that have been picked..
    $scope.max_count = 1;
    $scope.subscription_id = null;
    $scope.subscription = null;// The selected subscription.
    $scope.selected_email_list = [];// List of selected emails (objects).
    $scope.remaining_email_list = [];// List of remaining emails (objects).
    
    $scope.selected_item = null;
    $scope.search_text = null;
    
    $rootScope.previous_path = '/delivery';
    $rootScope.next_path = '/checkout';
    
    $scope.next = function () {
      $scope.setMail();
    };

    // Run init only when 'wapo' is set.
    $scope.$watch('wapo', function (newValue, oldValue) {
      if (!oldValue && newValue) {
        $scope.init();
      }
    });

    $scope.init = function () {
      $scope.subscription_id = $rootScope.wapo.mailchimp.subscription;
      $scope.email_list = $rootScope.wapo.mailchimp.email_list;
      $scope.max_count = $rootScope.wapo.mailchimp.max;

      // Fetch the list of subscription lists if we don't have any.
      if (!$rootScope.subscription_list.length) {
        $http.get('/wp/mailchimp/lists/').success(function (response) {
          $rootScope.subscription_list = response.data.data;
          
          // If we had previously selected a 'subscription', then find the object.
          if($scope.subscription_id) {
            $scope.subscription = _.find($rootScope.subscription_list, function(item) {
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
        });
      }
    };

    $scope.getSubscriptionEmails = function () {
      $http.get('/wp/mailchimp/lists/members/?id=' + $scope.subscription.id).success(function (response) {
        $rootScope.subscription_email_list = response.data.data;
        
        // Check if any of the emails in this list have been picked.
        $scope.selected_email_list = _.filter($rootScope.subscription_email_list, function(item) {
          return ($scope.email_list.indexOf(item.email) > -1);
        });
        
        $scope.remaining_email_list = _.filter($rootScope.subscription_email_list, function(item) {
          return ($scope.email_list.indexOf(item.email) < 0);
        });
        
        // Chunk into 3.
        $scope.chunked_email_list = _.chunk($scope.remaining_email_list, 3);
      });
    };
    
    $scope.search = function (keyword) {
      var searched_email_list = [];

      if (keyword) {
        searched_email_list = _.filter($scope.remaining_email_list, function (item) {
          var text = item.email+' '+item.merges.FNAME+' '+item.merges.LNAME;
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
      if($scope.search_text) {
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

    $scope.setMail = function () {
      $http.post('/wp/wapo/set/delivery/mailchimp/', {subscription: $scope.subscription.id, emails: $scope.email_list.join(',')}).success(function (response) {
        $rootScope.wapo = response.wapo;
        $location.path($scope.next_path);
      });
    };
    
    $scope.clear = function () {
      $scope.email_list = [];
      $scope.subscription_email_list = [];
    };
  }]);

wapoApp.controller('AnyTwitterFollowersCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $scope.account = null;
    $rootScope.previous_path = '/delivery';
    $rootScope.next_path = '/checkout';
    
    $scope.next = function () {
      $scope.setTwitter();
    };

    // Run init only when 'wapo' is set.
    $scope.$watch('wapo', function (newValue, oldValue) {
      if (!oldValue && newValue) {
        $scope.init();
      }
    });

    $scope.init = function () {
      $http.get('/twitter/authenticated/').success(function(response) {
        $scope.account = response.account;
      });
    };
    
    $scope.setTwitter = function() {
      if(!$scope.account) {
        alert('Please log into Twitter!');
        return;
      }
      
      $http.post('/wp/wapo/set/delivery/any-twitter-followers/', {}).success(function(response) {
        $location.path($rootScope.next_path);
      });
    };
  }]);

wapoApp.controller('SelectTwitterFollowersCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.previous_path = '/delivery';
    $rootScope.next_path = '/checkout';
    
    $scope.account = null;
    $scope.selected_follower_list = [];
    $scope.follower_list = [];
    
    $scope.next = function () {
      $scope.setTwitter();
    };

    // Run init only when 'wapo' is set.
    $scope.$watch('wapo', function (newValue, oldValue) {
      if (!oldValue && newValue) {
        $scope.init();
      }
    });
    
    $scope.refresh = function() {
      console.log($scope.selected_follower_list);
    };
    
    $scope.next = function() {
      $scope.setTwitter();
    };

    $scope.init = function () {
      $scope.follower_list = $rootScope.wapo.twitter.follower_list;
      
      $http.get('/twitter/authenticated/').success(function(response) {
        $scope.account = response.account;
        
        if($scope.account) {
          $scope.getFollowers();
        }
      });
    };
    
    $scope.getFollowers = function() {
      $http.get('/twitter/followers/').success(function(response) {
        $scope.followers = response.follower_list;
        
        // Re-fill 'selected_follower_list' with selected screen names from 'follower_list'.
        _.map($scope.followers, function(item) {
          if(_.contains($scope.follower_list, item.screen_name)) {
            $scope.selected_follower_list.push(item);
          }
        });
      });
    };
    
    $scope.addFollower = function(item) {
      $scope.selected_follower_list.push(item);
      $scope.follower_list.push(item.screen_name);
    };
    
    $scope.setTwitter = function() {
      if(!$scope.follower_list.length) {
        alert("Please select at least one follower!");
        return;
      }
      
      $http.post('/wp/wapo/set/delivery/select-twitter-followers/', {followers: $scope.follower_list.join(',')}).success(function(response) {
        $location.path($rootScope.next_path);
      });
    };
    
    /**
     * Search for emails!
     */
    $scope.querySearch = function(query) {
      var results = query ? $scope.followers.filter($scope.createFilterFor(query)) : [];
      return results;
    };
    /**
     * Create filter function for a query string
     */
    $scope.createFilterFor = function(query) {
      var lowercaseQuery = angular.lowercase(query);
      return function filterFn(item) {
//        return (item.email.toLowerCase().indexOf(lowercaseQuery) === 0) || (item.email.toLowerCase().indexOf(lowercaseQuery) === 0);
          var text = item.name+' '+item.screen_name;
          text = text.toLowerCase();
          return (text.indexOf(lowercaseQuery) > -1) || (text.indexOf(lowercaseQuery) > -1);
      };
    };
  }]);

wapoApp.controller('AnyFacebookFriendsCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $scope.profile = null;
    $rootScope.previous_path = '/delivery';
    $rootScope.next_path = '/checkout';
    
    $scope.next = function () {
      $scope.setFacebook();
    };

    // Run init only when 'wapo' is set.
    $scope.$watch('wapo', function (newValue, oldValue) {
      if (!oldValue && newValue) {
        $scope.init();
      }
    });

    $scope.init = function () {
      $http.get('/facebook/authenticated/').success(function(response) {
        $scope.profile = response.profile;
        console.log($scope.profile);
      });
    };
    
    $scope.setFacebook = function() {
      $http.post('/wp/wapo/set/delivery/any-facebook-friends/', {}).success(function(response) {
        $location.path($rootScope.next_path);
      });
    };
  }]);

wapoApp.controller('FacebookPageCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.previous_path = '/delivery';
    $rootScope.next_path = '/checkout';
    
    $scope.profile = null;
    $scope.selected_page_list = [];
    $scope.page_list = [];
    
    $scope.next = function () {
      $scope.setFacebook();
    };

    // Run init only when 'wapo' is set.
    $scope.$watch('wapo', function (newValue, oldValue) {
      if (!oldValue && newValue) {
        $scope.init();
      }
    });
    
    $scope.refresh = function() {
      console.log($scope.selected_page_list);
    };
    
    $scope.next = function() {
      $scope.setFacebook();
    };

    $scope.init = function () {
      $scope.page_list = $rootScope.wapo.facebook.page_list;
      
      $http.get('/facebook/authenticated/').success(function(response) {
        $scope.profile = response.profile;
        
        if($scope.profile) {
          $scope.getPages();
        }
      });
    };
    
    $scope.getPages = function() {
      $http.get('/facebook/pages/').success(function(response) {
        $scope.pages = response.page_list;
        
        // Re-fill 'selected_follower_list' with selected screen names from 'follower_list'.
        _.map($scope.pages, function(item) {
          if(_.contains($scope.page_list, item.id)) {
            $scope.selected_page_list.push(item);
          }
        });
      });
    };
    
    $scope.addPage = function(item) {
      $scope.selected_page_list.push(item);
      $scope.page_list.push(item.id);
    };
    
    $scope.setFacebook = function() {
      if(!$scope.page_list.length) {
        alert("Please select at least one page!");
        return;
      }
      
      $http.post('/wp/wapo/set/delivery/facebook-page/', {pages: $scope.page_list.join(',')}).success(function(response) {
        $location.path($rootScope.next_path);
      });
    };
    
    /**
     * Search for emails!
     */
    $scope.querySearch = function(query) {
      var results = query ? $scope.pages.filter($scope.createFilterFor(query)) : [];
      return results;
    };
    /**
     * Create filter function for a query string
     */
    $scope.createFilterFor = function(query) {
      var lowercaseQuery = angular.lowercase(query);
      return function filterFn(item) {
          var text = item.name;
          text = text.toLowerCase();
          return (text.indexOf(lowercaseQuery) > -1) || (text.indexOf(lowercaseQuery) > -1);
      };
    };
  }]);

wapoApp.controller('CheckoutCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $scope.valid = false;
    
    $rootScope.previous_path = '/delivery';
    $rootScope.next_path = '/pay';

    // Run init only when 'wapo' is set.
    $scope.$watch('wapo', function (newValue, oldValue) {
      if (!oldValue && newValue) {
        $scope.init();
      }
    });

    $scope.init = function () {
      
//      $http.get('/wp/wapo/validate/').success(function (response) {
//        $scope.valid = response.valid;
//      });
    };

    $scope.setPaymentMethod = function (payment_method) {
      $http.post('/wp/wapo/set/payment-method/', {payment_method: payment_method}).success(function (response) {
        $rootScope.wapo = response.wapo;
        document.location.href = response.checkout_url;
      });
    };
  }]);

wapoApp.controller('PaymentCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    // Run init only when 'wapo' is set.
    $scope.$watch('wapo', function (newValue, oldValue) {
      if (!oldValue && newValue) {
        $scope.init();
      }
    });

    $scope.init = function () {
//      $http.get('/wp/wapo/validate/').success(function(response) {
//        $scope.valid = response.valid;
//      });
    };
  }]);

wapoApp.controller('SendCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    // Run init only when 'wapo' is set.
    $scope.$watch('wapo', function (newValue, oldValue) {
      if (!oldValue && newValue) {
        $scope.init();
      }
    });

    $scope.init = function () {
      $http.get('/wp/wapo/send/').success(function (response) {
        $rootScope.wapo = response.wapo;
        $location.path('/confirmation');
      }).error(function (errorResponse) {
        $scope.message = errorResponse.message;
      });
    };
  }]);

wapoApp.controller('ConfirmationCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    // Run init only when 'wapo' is set.
    $scope.$watch('wapo', function (newValue, oldValue) {
      if (!oldValue && newValue) {
        $scope.init();
      }
    });

    $scope.init = function () {

    };

    $scope.startOver = function () {
      $http.get('/wp/wapo/start-over/').success(function (response) {
        $location.path('/module');
      });
    };

    $scope.sendAnother = function () {
      $location.path('/module');
    };


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
  });
});
