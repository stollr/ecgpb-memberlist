import $ from 'jquery';

export default {
    init: function() {
        $(document).on('click', '[remove-collection-item]', function (event) {
            const collectionElm = $(event.target).closest('[data-prototype]');
            const itemElmToRemove = collectionElm.children().has(event.target);

            if (collectionElm.data('nextIndex') === undefined) {
                collectionElm.data('nextIndex', collectionElm.children().length);
            }
            
            if (itemElmToRemove.length > 0) {
                itemElmToRemove.remove();
            }
        });
        
        $(document).on('click', '[add-collection-item]', function (event) {
            const collectionElmSelector = $(event.target).attr('add-collection-item');
            const collectionElm = $(collectionElmSelector);

            if (collectionElm.length === 0 || !collectionElm.attr('data-prototype')) {
                throw 'No collection element found with selector "' + collectionElmSelector + '".';
            }

            const prototypeName = collectionElm.attr('data-prototype-name') || '__name__';
            const nextIndex = collectionElm.data('nextIndex') === undefined
                ? collectionElm.children().length
                : collectionElm.data('nextIndex');

            const prototype = collectionElm
                    .attr('data-prototype')
                    .replace(new RegExp(prototypeName, 'g'), nextIndex);
            const newItemElm = $(prototype);
            
            collectionElm.append(newItemElm);

            collectionElm.data('nextIndex', nextIndex + 1)
        });
    }
}
