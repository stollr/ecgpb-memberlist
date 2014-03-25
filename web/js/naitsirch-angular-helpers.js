/**
 * AngularJS helper directives
 * @author naitsirch
 */
angular.module('naitsirch.helpers', ['ui.bootstrap']);

/**
 * @ngdoc directive
 * @name confirmClick
 * @restrict EA
 *
 * @description
 * Confirmation of a click event with a bootstrap modal.
 *
 * @example
    <example>
        <button type="button" class="btn btn-default btn-sm"
         nait-confirm-click
         confirm="{% trans %}Do you really want to remove this record?{% endtrans %}"
         confirm-if="ministryGroup.name != 'NEIN'"
         confirm-button-text="OK"
         cancel-button-text="Cancel"
         do="removeGroup(ministryGroup)">
            <span class="glyphicon glyphicon-remove"></span>
        </button>
    </example>
 */
angular.module('naitsirch.helpers').directive('naitConfirmClick', function($modal, $parse) {
    return {
        restrict: 'EA',
        link: function(scope, element, attrs) {
            if (!attrs.do) {
                return;
            }

            // register the confirmation event
            var confirmButtonText = attrs.confirmButtonText ? attrs.confirmButtonText : 'OK';
            var cancelButtonText = attrs.cancelButtonText ? attrs.cancelButtonText : 'Cancel';
            element.click(function() {
                // action that should be executed if user confirms
                var doThis = $parse(attrs.do);

                // condition for confirmation
                if (attrs.confirmIf) {
                    var confirmationCondition = $parse(attrs.confirmIf);
                    if (!confirmationCondition(scope)) {
                        // if no confirmation is needed, we can execute the action and leave
                        doThis(scope);
                        scope.$apply();
                        return;
                    }
                }
                $modal
                    .open({
                        template: '<div class="modal-body">' + attrs.confirm + '</div>'
                            + '<div class="modal-footer">'
                            +     '<button type="button" class="btn btn-default btn-naitsirch-confirm pull-right" ng-click="$close(\'ok\')">' + confirmButtonText + '</button>'
                            +     '<button type="button" class="btn btn-default btn-naitsirch-cancel pull-right" ng-click="$dismiss(\'cancel\')">' + cancelButtonText + '</button>'
                            + '</div>'
                    })
                    .result.then(function() {
                        doThis(scope);
                        scope.$apply();
                    })
                ;
            });
        }
    };
});
