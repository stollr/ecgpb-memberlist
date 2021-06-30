// any CSS you import will output into a single css file (app.css in this case)
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import './styles/app.scss';

// JS imports
import jQuery from 'jquery';
import 'bootstrap';
import a2lix_lib from '@a2lix/symfony-collection/src/a2lix_sf_collection';
import collection from './scripts/collection.js';

jQuery(document).ready(function () {
    const a2lix_lib_langs = {
        de: {
            add: 'Hinzufügen',
            remove: 'Entfernen'
        }
    };
    
    a2lix_lib.sfCollection.init({
        collectionsSelector: 'form .a2lix-sfcollection div[data-prototype]',
        manageRemoveEntry: true,
        lang: a2lix_lib_langs[document.documentElement.lang] || {
            add: 'Add',
            remove: 'Remove'
        }        
    });
    
    collection.init();
});