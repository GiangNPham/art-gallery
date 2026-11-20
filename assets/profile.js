// Task 10: client-side checks for the password form on the profile page 
(function () {
    'use strict';
    var form = document.getElementById('password-form');
    if (!form) return;
    var next = form.elements.new_password;
    var confirm = form.elements.confirm_password;

    function checkNext() {
        var v = next.value;
        next.setCustomValidity(v && (!/[A-Za-z]/.test(v) || !/[0-9]/.test(v)) ? 'Use at least one letter and one number.' : '');
    }
    function checkMatch() {
        confirm.setCustomValidity(confirm.value && confirm.value !== next.value ? 'The two passwords do not match.' : '');
    }
    next.addEventListener('input', function () { checkNext(); checkMatch(); });
    confirm.addEventListener('input', checkMatch);
})();