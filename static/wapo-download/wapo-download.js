var wapoDownloadApp = angular.module('wapoDownloadApp', ['ngRoute', 'ngResource', 'ngMaterial', 'ui.bootstrap', 'ngCookies'], function ($httpProvider) {
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
    
  }]);

wapoDownloadApp.controller('WapoCtrl', ['$rootScope', '$scope', '$location', '$http', '$routeParams', function ($rootScope, $scope, $location, $http, $routeParams) {
    $scope.showLoading = true;
    $scope.message = "Checking Wapo...";

    $scope.wapo_id = location.href.match(/wapo_id=([^&]+)/)[1].replace('#/', '');
    $scope.code = location.href.match(/code=([^&]+)/)[1].replace('#/', '');
    
    console.log($scope.wapo_id, $scope.code);
    
  }]);

wapoDownloadApp.config(function ($routeProvider) {
  $routeProvider.when('/', {
    templateUrl: '/apps/wp/templates/wapo-download/pages/wapo.html',
    controller: 'WapoCtrl'
  }).when('/profile', {
    templateUrl: '/apps/wp/templates/wapo-download/pages/profile.html',
    controller: 'ProfileCtrl'
  }).otherwise({
    redirectTo: '/'
  });
});
