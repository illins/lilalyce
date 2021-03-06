var wapoDownloadApp = angular.module('wapoDownloadApp', ['ngRoute', 'ngResource', 'ui.bootstrap', 'ngCookies'], function ($httpProvider) {
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

wapoDownloadApp.controller('MainCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', '$cookies', function ($rootScope, $scope, $location, $http, $routeParams, $mdDialog, $cookies) {
    $rootScope.user = null;
    $rootScope.wapo = null;
    $rootScope.profile = null;
    $rootScope.related_product_list = [];
    $rootScope.social_link_list = [];

    $rootScope.setPath = function (path, href) {
      $cookies.put('path', path);
      window.location.href = href;
    };

    $rootScope.mainInit = function (wapo_id, callback) {
      if (!$rootScope.wapo) {
        $http.get('/wp/wapo/download/wapo/' + wapo_id + '/').success(function (response) {
          $rootScope.wapo = response;

          $http.get('/wp/wapo/download/profile/' + response.profile.id + '/').success(function (res1) {
            $rootScope.profile = res1;
          });

          $http.get('/wp/wapo/download/profile/' + response.profile.id + '/related-product/').success(function (res2) {
            $rootScope.related_product_list = res2;
          });
          
          $http.get('/wp/wapo/download/profile/' + response.profile.id + '/social-link/').success(function (res3) {
            $rootScope.social_link_list = res3;
          });
        });
      }

      if(callback) {
        callback();
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
    
    $rootScope.message = '';
    $rootScope.setMessage = function(message) {
      $rootScope.message = message;
    };
    
    $rootScope.showProgress = function() {
      return ($rootScope.message);
    };
  }]);

wapoDownloadApp.controller('WapoCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.setMessage("Checking Wapo...");
    
    $scope.progress = true;
    $scope.wapo_id = null;
//    $scope.code = null;
    
    $scope.checkWapo = function() {
      if(!$scope.wapo_id) {
        $scope.progress = false;
        $scope.setMessage("Wapo not found!");
        return;
      }
      $scope.setMessage("Checking Wapo!");

      $http.post('/wp/wapo/download/check/', {wapo: $scope.wapo_id}).success(function(response) {
        $scope.setMessage('');
        $location.path('/' + response.wapo.delivery_method + '/' + response.wapo.id);
      }).error(function(errorResponse) {
        $scope.progress = false;
        $scope.message = errorResponse.message;
      });
    };
    
    $scope.init = function() {
      try {
        $scope.wapo_id = location.href.match(/wapo_id=([^&]+)/)[1].replace('#/', '');
      } catch(err) {}

//      try {
//        $scope.code = location.href.match(/code=([^&]+)/)[1].replace('#/', '');
//      } catch(err) {}
      
//      if($scope.wapo_id && $scope.code) {
//        $scope.checkWapo();
//      } else {
//        $scope.setMessage('Please enter code!');
//      }
      $scope.checkWapo();
    };
  }]);

wapoDownloadApp.controller('EmailCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $scope.progress = false;
    
    $scope.wapo_id = null;
    $scope.email = null;
    
    $scope.checkEmail = function() {
      if(!$scope.email) {
        $scope.message = 'Please enter valid email!';
        return;
      }
      $scope.progress = true;
      $scope.message = "Sending confirmation code!";
      
      $http.post('/wp/wapo/download/email/check/', {wapo: $routeParams.wapo_id, email: $scope.email}).success(function(response) {
        $location.path('/confirm/' + $routeParams.wapo_id + '/' + $scope.email);
      }).error(function(errorResponse) {
        $scope.progress = false;
        $scope.message = errorResponse.message;
      });
    };
    
    $rootScope.mainInit($routeParams.wapo_id);
  }]);

wapoDownloadApp.controller('TextCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $scope.progress = false;
    
    $scope.checkNumber = function() {
      if(!$scope.number) {
        $scope.message = 'Please enter valid phone number!';
        return;
      }
      
      $scope.progress = true;
      $scope.message = 'Sending confirmation code...';
      
      $http.post('/wp/wapo/download/text/check/', {wapo: $routeParams.wapo_id, number: $scope.number}).success(function(response) {
        $location.path('/confirm/' + $routeParams.wapo_id + '/' + $scope.number);
      }).error(function(errorResponse) {
        $scope.progress = false;
        $scope.message = errorResponse.message;
      });
    };
    
    $rootScope.mainInit(null);
  }]);

wapoDownloadApp.controller('ConfirmCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $scope.progress = false;
    
    $scope.confirm = function() {
      if(!$scope.confirmation) {
        $scope.message = 'Please enter confirmation code!';
        return;
      }
      
      $scope.progress = true;
      $scope.message = 'Checking confirmation code!';
      
      $http.post('/wp/wapo/download/confirm/', {wapo: $routeParams.wapo_id, contact: $routeParams.contact, confirmation: $scope.confirmation}).success(function(response) {
        $location.path('/download/' + $routeParams.wapo_id + '/' + $routeParams.contact + '/' + $scope.confirmation);
      }).error(function(errorResponse) {
        $scope.progress = false;
        $scope.message = errorResponse.message;
      });
    };
    
    $rootScope.mainInit($routeParams.wapo_id);
  }]);

wapoDownloadApp.controller('DownloadCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $scope.progress = true;
    
    $scope.wapo = {};
    $scope.reward = {};
    $scope.url = '';
    
    var data = {};
    
    $scope.init = function() {
      data = {wapo: $routeParams.wapo_id, contact: $routeParams.contact, confirmation: $routeParams.confirmation};
      $scope.message = "Preparing download...";
      
      $http.get('/wp/wapo/download/info/?wapo=' + $routeParams.wapo_id).success(function(response) {
        $scope.wapo = response.wapo;
        
        if(response.wapo.marketplace == "promotion") {
          $scope.getPromotion();
        } else if(response.wapo.marketplace == "tangocards") {
          $scope.getReward();
        }
      }).error(function(errorResponse) {
        $scope.progress = false;
        $scope.message = errorResponse.message;
      });
      
      $scope.getPromotion = function() {
        $http.post('/wp/wapo/download/prepare/', data).success(function (response) {
          $scope.url = response.url;
          $scope.progress = false;
          $scope.message = '';
        }).error(function (errorResponse) {
          $scope.progress = false;
          $scope.message = errorResponse.message;
        });
      };
      
      $scope.getReward = function() {
        $http.post('/wp/wapo/download/reward/', data).success(function (response) {
          $scope.reward = response.reward;
          $scope.progress = false;
          $scope.message = '';
        }).error(function (errorResponse) {
          $scope.progress = false;
          $scope.message = errorResponse.message;
        });
      };
    };
    
    $rootScope.mainInit($routeParams.wapo_id, $scope.init);
    
  }]);

wapoDownloadApp.config(function ($routeProvider) {
  $routeProvider.when('/', {
    templateUrl: '/apps/wp/templates/wapo-download/pages/wapo.html',
    controller: 'WapoCtrl'
  }).when('/profile', {
    templateUrl: '/apps/wp/templates/wapo-download/pages/profile.html',
    controller: 'ProfileCtrl'
  }).when('/email/:wapo_id', {
    templateUrl: '/apps/wp/templates/wapo-download/pages/email.html',
    controller: 'EmailCtrl'
  }).when('/email-list/:wapo_id', {
    templateUrl: '/apps/wp/templates/wapo-download/pages/email.html',
    controller: 'EmailCtrl'
  }).when('/text/:wapo_id', {
    templateUrl: '/apps/wp/templates/wapo-download/pages/text.html',
    controller: 'TextCtrl'
  }).when('/confirm/:wapo_id/:contact', {
    templateUrl: '/apps/wp/templates/wapo-download/pages/confirmation.html',
    controller: 'ConfirmCtrl'
  }).when('/download/:wapo_id/:contact/:confirmation', {
    templateUrl: '/apps/wp/templates/wapo-download/pages/download.html',
    controller: 'DownloadCtrl'
  }).otherwise({
    redirectTo: '/'
  });
});
