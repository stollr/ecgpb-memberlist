import $ from 'jquery';

document.addEventListener('DOMContentLoaded', function () {
    const deleteButtons = document.querySelectorAll('button[delete-ministry-category]');

    deleteButtons.forEach(function (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
            $('#delete-modal').modal();

            const formElm = document.querySelector('#delete-modal form');

            formElm.action = formElm.action.replace('_ID_', deleteBtn.getAttribute('delete-ministry-category'));
        });
    });
});