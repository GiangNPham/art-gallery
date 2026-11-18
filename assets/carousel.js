// Task 8: painting carousel
// paintings move to left on auto-rotate
// prev/next buttons
// pause/play button

(function () {
    'use strict';
    var DELAY = 3000;
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function init(root) {
        var track = root.querySelector('.carousel-track');
        var prev = root.querySelector('.carousel-prev');
        var next = root.querySelector('.carousel-next');
        var toggle = root.querySelector('.carousel-toggle');
        if (!track || !track.children.length) return;

        var busy = false, timer = null, userPaused = false, hoverPaused = false;

        function visible() {
            return Math.round(parseFloat(getComputedStyle(root).getPropertyValue('--visible'))) || 1;
        }
        function canRotate() { return track.children.length > visible(); }
        function step() {
            var gap = parseFloat(getComputedStyle(track).columnGap) || 0;
            return track.firstElementChild.getBoundingClientRect().width + gap;
        }
        function refresh() {
            if (canRotate()) root.removeAttribute('data-static'); else root.setAttribute('data-static', '');
        }

        function slide(direction) {
            if (busy || !canRotate()) return;
            busy = true;
            var done = false;
            function finish(after) {
                if (done) return;
                done = true;
                track.removeEventListener('transitionend', onEnd);
                if (after) after();
                busy = false;
            }
            function onEnd(e) { if (e.target === track) finish(cleanup); }
            var cleanup = null;

            if (reduceMotion) {
                if (direction > 0) track.appendChild(track.firstElementChild);
                else track.insertBefore(track.lastElementChild, track.firstElementChild);
                busy = false;
                return;
            }

            var distance = step();
            track.addEventListener('transitionend', onEnd);
            if (direction > 0) {
                // Slide left, then move the first image to the back and reset
                cleanup = function () {
                    track.style.transition = 'none';
                    track.appendChild(track.firstElementChild);
                    track.style.transform = 'none';
                    void track.offsetWidth;
                    track.style.transition = '';
                };
                track.style.transform = 'translateX(' + (-distance) + 'px)';
            } else {
                // Put the last image in front (offset out of view), then slide right
                track.style.transition = 'none';
                track.insertBefore(track.lastElementChild, track.firstElementChild);
                track.style.transform = 'translateX(' + (-distance) + 'px)';
                void track.offsetWidth;
                track.style.transition = '';
                track.style.transform = 'translateX(0)';
                cleanup = function () { track.style.transform = 'none'; };
            }
            // Fallback in case transitionend never fires 
            setTimeout(function () { finish(cleanup); }, 800);
        }

        function schedule() {
            clearTimeout(timer);
            if (userPaused || hoverPaused || document.hidden || !canRotate()) return;
            timer = setTimeout(function () { slide(1); schedule(); }, DELAY);
        }
        function manual(direction) { slide(direction); schedule(); }

        if (next) next.addEventListener('click', function () { manual(1); });
        if (prev) prev.addEventListener('click', function () { manual(-1); });
        if (toggle) toggle.addEventListener('click', function () {
            userPaused = !userPaused;
            toggle.textContent = userPaused ? 'Play' : 'Pause';
            toggle.setAttribute('aria-pressed', String(userPaused));
            schedule();
        });

        root.addEventListener('mouseenter', function () { hoverPaused = true; schedule(); });
        root.addEventListener('mouseleave', function () { hoverPaused = false; schedule(); });
        root.addEventListener('focusin', function () { hoverPaused = true; schedule(); });
        root.addEventListener('focusout', function () { hoverPaused = false; schedule(); });
        root.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowRight') { manual(1); e.preventDefault(); }
            if (e.key === 'ArrowLeft') { manual(-1); e.preventDefault(); }
        });
        document.addEventListener('visibilitychange', schedule);
        window.addEventListener('resize', function () { refresh(); schedule(); });

        refresh();
        schedule();
    }

    document.querySelectorAll('.carousel').forEach(init);
})();