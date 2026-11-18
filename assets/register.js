// Task 10: client-side checks for registration form 
(function () {
    'use strict';
    var form = document.getElementById('register-form');
    if (!form) return;
    var password = form.elements.password;
    var confirm = form.elements.confirm;

    function checkPassword() {
        var v = password.value;
        if (v && (!/[A-Za-z]/.test(v) || !/[0-9]/.test(v))) {
            password.setCustomValidity('Use at least one letter and one number.');
        } else {
            password.setCustomValidity('');
        }
    }
    function checkMatch() {
        if (confirm.value && confirm.value !== password.value) {
            confirm.setCustomValidity('The two passwords do not match.');
        } else {
            confirm.setCustomValidity('');
        }
    }
    password.addEventListener('input', function () { checkPassword(); checkMatch(); });
    confirm.addEventListener('input', checkMatch);
})();