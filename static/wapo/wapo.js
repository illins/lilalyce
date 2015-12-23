var wapoApp = angular.module('wapoApp', ['ngRoute', 'ngResource'], function ($httpProvider) {
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

wapoApp.controller('MainCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $rootScope.user = null;
    $rootScope.wapo = null;
    
    $rootScope.module_list = [];

    $scope.init = function () {
      $http.get('/wp/wapo/data/').success(function (response) {
        $rootScope.user = response.blink.request.user;
        $rootScope.wapo = response.wapo;
        console.log($rootScope.wapo);
      });
    };
  }]);

wapoApp.controller('ModuleCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $scope.init = function () {
      if (!$rootScope.module_list.length) {
        $http.get('/wp/wapo/module/').success(function (response) {
          $rootScope.module_list = response.module_list;
          if (response.module_list.length == 1) {
            $scope.setModule(response.module_list[0]);
          }
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
  }]);

wapoApp.controller('ProfileCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {

  }]);


wapoApp.config(function ($routeProvider) {
  $routeProvider.when('/', {
    templateUrl: '/apps/wp/templates/wapo/pages/module.html',
    controller: 'ModuleCtrl'
  }).when('/profile', {
    templateUrl: '/apps/wp/templates/wapo/pages/profile.html',
    controller: 'ProfileCtrl'
  });
});