var app = angular.module('mailchimp', ['ngRoute'], function ($httpProvider) {
  // http://victorblog.com/2012/12/20/make-angularjs-http-service-behave-like-jquery-ajax/
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

app.factory('BucketFactory', function() {
  var list_list = [];
  var email_list = [];
  var list_id = null;
  
  return {
    list_list: list_list,
    email_list: email_list,
    list_id: list_id,
    setList: function(list) {
      list_list = list;
    },
    getList: function() {
      return list_list;
    },
    setEmailList: function(emails) {
      email_list = emails;
    },
    getEmailList: function() {
      return email_list;
    },
    setListID: function(id) {
      list_id = id;
    },
    getListID: function() {
      return list_id;
    }
  };
});

app.config(['$routeProvider', function ($routeProvider) {
    $routeProvider.when('/', {
      templateUrl: 'login.html',
      controller: 'LoginCtrl'
    }).when('/lists/:listId', {
      templateUrl: 'list.html',
      controller: 'ListCtrl'
    }).when('/lists', {
      templateUrl: 'lists.html',
      controller: 'ListsCtrl'
    }).otherwise({
      redirectTo: '/' //'/lists/451da43dc2'
    });
  }]);

app.controller('LoginCtrl', ['$scope', '$location', '$routeParams', '$http', 'BucketFactory', function ($scope, $location, $routeParams, $http, BucketFactory) {
    $scope.status = 'Checking MailChimp login';
    $scope.error = '';

//    // Set the list_id variable if it was set.
//    if ($('#list_id').val()) {
//      BucketFactory.setListID($('#list_id').val());
//    }
//
//    // Set the email_list if it was set.
//    if ($('#email_list').val()) {
//      BucketFactory.setEmailList($('#email_list').val().split(','));
//    }

    // Check if we are authenticated. 
    $http({
      method: 'GET',
      url: '/mailchimp/isauthenticated/'
    }).success(function (data, status, headers, config) {
      $scope.status = '';
      if (data !== false && _.isUndefined(data.status)) {
        $location.path('/lists');
      }
    }).error(function (data, status, headers, config) {
      $scope.status = '';
      $scope.error = 'MailChimp error.';
    });
  }]).controller('ListsCtrl', ['$scope', '$location', '$routeParams', '$http', 'BucketFactory', function ($scope, $location, $routeParams, $http, BucketFactory) {
    $scope.status = 'Loading lists';
    $scope.error = '';
    $scope.lists = [];
    $scope.listId = $("#list_id").val();


    // Clear any list.
    $scope.clear = function (event) {
      event.preventDefault();

      var temp_list = $scope.lists;
      $scope.lists = [];
      $scope.lists = temp_list;
      $("#list_id").val('');
      $("#emails").val('');
    };

    $scope.reload = function (event) {
      if (!_.isUndefined(event)) {
        event.preventDefault();
      }
      
      // Disable reload button.
      $("#reload_list").addClass('disabled');

      $scope.status = 'Loading lists';
      $scope.error = '';
      $scope.lists = [];
      $scope.listId = null;

      $http({
        method: 'GET',
        url: '/wp/mailchimp/lists/'
      }).success(function (data, status, headers, config) {
        if (data.data) {
          $scope.lists = data.data.data;
        }
        $scope.status = '';
      }).error(function (data, status, headers, config) {
        $scope.status = '';
        $scope.error = 'Error gathering lists.';
      }).finally(function() {
        $("#reload_list").removeClass('disabled');// Enable the button.
      });
    };

    $scope.reload();
  }])
//    .directive('selectedList', function() {
//    return function(scope, element, attr) {
//      element.bind('click', function(e) {
//        $("#list-emails").html('we here...');
//      });
//    };
//  })
    .directive('myListSelected', ['BucketFactory', function(BucketFactory) {
    function link(scope, element, attrs) {
      if(attrs.myListSelected === $("#list_id").val()) {
        element.text('Discard List');
      } else {
        element.text('Select List');
      }
    }

    return {
      link: link
    };
  }]).controller('ListCtrl', ['$scope', '$location', '$routeParams', '$http', function ($scope, $location, $routeParams, $http) {
    $scope.status = 'Loading emails';
    $scope.error = '';
    $scope.emails = [];

    $scope.selectEmail = function (event) {
      var email_list = ($("#emails").val()) ? $("#emails").val().split(',') : [];
      
      var id = $(event.target).data('id');
      
      if (email_list.indexOf(id) === -1) {
        email_list.push(id);
        //$(event.target).prop('checked', true);
      } else {
        email_list.splice(email_list.indexOf(id), 1);
        //$(event.target).prop('checked', false);
      }
      
      $(".email-checkbox").prop('checked', false);
      console.log($(".email-checkbox"));
      _.map(email_list, function(id) {
        console.log($("input[name='" + id + "']"));
        $("input[name='" + id + "']").prop('checked', true);
      });
      
      //BucketFactory.email_list = email_list;
      $("#emails").val(email_list.join(','));
    };

    $scope.clear = function ($event) {
      $event.preventDefault();
      //BucketFactory.setEmailList([]);
      $("#emails").val('');
      $(".email-checkbox").prop('checked', false);
    };
    
    $scope.back = function($event) {
      $event.preventDefault();
      $location.path('/lists');
    };

    $scope.reload = function ($event) {
      if (!_.isUndefined($event)) {
        $event.preventDefault();
      }
      
      $("#reload_email_list").addClass('disabled');
      
      //var email_list = BucketFactory.email_list;

      $scope.status = 'Loading emails';
      $scope.error = '';
      $scope.emails = [];

      $http({
        method: 'GET',
        url: '/wp/mailchimp/lists/members/?id=' + $routeParams.listId
      }).success(function (data, status, headers, config) {
        if (data.data) {
          //$scope.emails = data.data.data;
          _.map(data.data.data, function (email) {
            var d = {id: email.id, name: email.email, email: email.email, checked: false, notchecked: false};
            if (email.merges.FNAME) {
              d.name = email.merges.FNAME + ' ' + email.merges.LNAME + '[' + email.email + ']';
            }
            //d.checked = (email_list.indexOf(email.id) !== -1) ? true : false;
            //d.notchecked = (email_list.indexOf(email.id) !== -1) ? false : true;

            $scope.emails.push(d);
          });
        }

        $scope.status = '';
      }).error(function (data, status, headers, config) {
        $scope.status = '';
        $scope.error = 'Error gathering emails.';
      }).finally(function() {
        $("#reload_email_list").removeClass('disabled');
      });
    };

    $scope.reload();
  }]).directive('myEmailSelected', ['BucketFactory', function(BucketFactory) {
    
    function link(scope, element, attrs) {
      
      if($("#emails").val().search(attrs.myEmailSelected) !== -1) {
        $(element).prop('checked', true);
      }
    }

    return {
      link: link
    };
  }]);