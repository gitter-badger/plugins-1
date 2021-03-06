'use strict';

/**
 * @ngdoc directive
 * @name frontendApp.directive:rateBox
 * @description
 * # rateBox
 */
angular.module('frontendApp')

// The rateBox directive shows a
// "star rate" widget which allow
// users to rate a plugin
.directive('rateBox', function() {
   return {
      template: '<stars></stars><votes ng-show="votesNumber">({{votesNumber}} votes)</votes>',
      restrict: 'E',
      link: function postLink(scope, element, attrs, controller) {
         // Defaults the current note to 0
         if (typeof(scope.currentNote) === 'undefined')
            scope.currentNote = 0;

         // Defaults to alreadyRated so, if directive
         // attribute is not set, we cannot rate   
         if (typeof(scope.disableRating) === 'undefined')
            scope.disableRating = false;

         // Creating five icon elements to display stars
         var _stars = element.find('stars');
         var stars = [];
         for (var i = 0; i < 5; i++) {
            var icon = angular.element('<i>');
            stars.push(icon);
            _stars.append(icon);
         }

         // Modify the DOM with the current stars
         var displayStars = function(note, hover) {
            var classes = controller.getClassesFromNote(note, hover);
            for (var i = 0; i < stars.length; i++) {
               stars[i].attr('class', classes[i]);
            }
         };

         // Compute the stars height/width
         controller.starWidth = 25;
         controller.starHeight = 25;

         var halfOrPlain = function(offsetX) {
            return (controller.starWidth -
               (controller.starWidth - offsetX) <=
               (controller.starWidth / 2)
            ) ? 0.5 : 1;
         };

         var enableRating = function() {
            stars.forEach(function(el, i) {
               var note;
               el.on('mouseenter', function(ev) {
                  if (!scope.alreadyRated) {
                     note = i + halfOrPlain(ev.offsetX);
                     displayStars(note, true);
                     el.on('mousedown', function() {
                        scope.rateMethod(note);
                        scope.votesNumber++;
                        disableRating();
                     });
                  }
               });
               el.on('mousemove', function(ev) {
                  if (!scope.disableRating) {
                     note = i + halfOrPlain(ev.offsetX);
                     displayStars(note, true);
                  }
               });
               el.on('mouseleave', function() {
                  displayStars(scope.currentNote);
                  el.unbind('mousedown');
               });
            });
         };

         var disableRating = function() {
            stars.forEach(function(el) {
               el.unbind('mousedown');
               el.unbind('mouseenter');
               el.unbind('mousemove');
               el.unbind('mouseleave');
            });
            displayStars(scope.currentNote);
         };


         // Watch for future modifications of the note
         scope.$watch('currentNote', function() {
            displayStars(scope.currentNote);
         });

         scope.$watch('disableRating', function(v) {
            disableRating();
            if (!v)
               enableRating();
         });

         if (!scope.disableRating)
            enableRating();
         // Create stars for current note
         displayStars(scope.currentNote);
      },

      scope: {
         currentNote: "=currentNote",
         disableRating: "=disableRating",
         rateMethod: "=rateMethod",
         votesNumber: "=votesNumber"
      },

      controller: function() {
         // returns fontawesome class for a full star
         this.getFullStar = function(hover) {
            return 'fa fa-star' + (hover ? ' hover' : '');
         };
         // returns fontawesome class for half a star
         this.getHalfStar = function(hover) {
            return 'fa fa-star-half-o' + (hover ? ' hover' : '');
         };
         // returns fontawesome class for an empty star
         this.getEmptyStar = function(hover) {
            return 'fa fa-star-o' + (hover ? ' hover' : '');
         };
         // returns an array of dom elements
         // which are the stars of the current
         // note
         this.getClassesFromNote = function(note, hover) {
            if (note > 5 || note < 0)
               note = 0;
            var stars = [];
            var lastIsHalf = note % 1 > 0;

            var hover = hover ? true : false;

            note = Math.floor(note);

            for (var i = 0; i < note; i++) {
               stars.push(this.getFullStar(hover));
            }

            if (lastIsHalf) {
               stars.push(this.getHalfStar(hover));
               i = i + 0.5;
            }

            if (5 - i >= 1) {
               var missingStars = Math.floor(5 - i);
               for (var i = 0; i < missingStars; i++) {
                  stars.push(this.getEmptyStar(hover));
               }
            }

            return stars;
         };
      }
   };
});