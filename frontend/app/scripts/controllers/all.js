'use strict';

/**
 * @ngdoc function
 * @name frontendApp.controller:AllCtrl
 * @description
 * # AllCtrl
 * Controller of the frontendApp
 */
angular.module('frontendApp')
   .controller('AllCtrl', function(API_URL, $http, $scope, PaginatedCollection, $stateParams) {
      $scope.results = PaginatedCollection.getInstance();
      $scope.results.setRequest(function(from, to) {
         return $http({
            method: "GET",
            url: API_URL + '/plugin',
            headers: {
               'X-Range': from+'-'+to
            }
         });
      });

      if ($stateParams.page) {
         $scope.results.setPage($stateParams.page - 1);
      } else {
         $scope.results.setPage(0);
      }

      $scope.$on('languageChange', function() {
         $scope.results.setPage($stateParams.page - 1);
      });
   });