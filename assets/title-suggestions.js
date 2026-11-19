(function () {
    'use strict';
    var input = document.getElementById('title');
    var list = document.getElementById('title-suggestions');
    if (!input || !list) return;

    var timer = null;
    var controller = null;
    var active = -1;

    function close() {
        list.hidden = true;
        list.replaceChildren();
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        active = -1;
    }

    function activate(index) {
        var options = list.querySelectorAll('[role="option"]');
        active = index;
        options.forEach(function (option, i) {
            option.setAttribute('aria-selected', String(i === index));
        });
        if (index >= 0) input.setAttribute('aria-activedescendant', options[index].id);
        else input.removeAttribute('aria-activedescendant');
    }

    function show(titles) {
        close();
        if (!titles.length) return;
        titles.forEach(function (title, index) {
            var item = document.createElement('li');
            item.id = 'title-suggestion-' + index;
            item.setAttribute('role', 'option');
            item.setAttribute('aria-selected', 'false');
            item.textContent = title;
            item.addEventListener('mousedown', function (event) { event.preventDefault(); });
            item.addEventListener('click', function () {
                input.value = title;
                close();
                input.form.requestSubmit();
            });
            list.appendChild(item);
        });
        list.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        if (controller) controller.abort();
        close();
        var query = input.value.trim();
        if (!query || query.length > 100) return;
        timer = setTimeout(function () {
            controller = new AbortController();
            fetch('title-suggestions.php?q=' + encodeURIComponent(query), {
                signal: controller.signal,
                headers: { 'Accept': 'application/json' }
            }).then(function (response) {
                if (!response.ok) throw new Error('Suggestions unavailable');
                return response.json();
            }).then(function (data) {
                if (input.value.trim() === query && document.activeElement === input) {
                    show(Array.isArray(data.titles) ? data.titles : []);
                }
            }).catch(function (error) {
                if (error.name !== 'AbortError') close();
            });
        }, 200);
    });

    input.addEventListener('keydown', function (event) {
        var options = list.querySelectorAll('[role="option"]');
        if (event.key === 'Escape') { close(); return; }
        if (list.hidden || !options.length) return;
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            activate((active + (event.key === 'ArrowDown' ? 1 : options.length - 1)) % options.length);
        } else if (event.key === 'Enter' && active >= 0) {
            event.preventDefault();
            options[active].click();
        }
    });
    input.addEventListener('blur', function () { setTimeout(close, 100); });
})();
