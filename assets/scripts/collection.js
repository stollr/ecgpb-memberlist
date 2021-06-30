import $ from 'jquery';

export default {
    init: function() {
        $(document).on('click', '[remove-collection-item]', function (event) {
            const collectionElm = $(event.target).closest('[data-prototype]');
            
            const itemElmToRemove = collectionElm.children().has(event.target);
            
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
            
            const prototype = collectionElm.attr('data-prototype').replace(/__name__/g, collectionElm.children().length);
            const newItemElm = $(prototype);
            
            collectionElm.append(newItemElm);
        });
    }
}