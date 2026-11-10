/* Article page-turn controller. Vanilla JS, no dependencies.
 *
 * The DOM (rendered by article.php):
 *   main.article-main   carries --pt-perspective / --pt-speed
 *     .bw-leaf#bw-leaf  the paper sheet holding the story + nav
 *       a[data-turn="prev"|"next"]  the turn links
 *
 * css/page-turn.css animates the sheet: .bw-leaf-away-* curls the
   page over as the reader leaves, .bw-leaf-settle-* eases the next
   page flat as it arrives, .bw-leaf-hint-* is the hover tease. This
   script decides when: it intercepts turn-link clicks (and arrow
   keys), remembers the direction across the navigation, and keeps
   reduced-motion and back/forward navigation well behaved. */
(function () {
    'use strict';

    var leaf = document.getElementById('bw-leaf');
    if (!leaf) return;

    var main = document.querySelector('.article-main');
    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var STORE = 'bw-turn-dir';

    function speedMs() {
        var raw = main ? getComputedStyle(main).getPropertyValue('--pt-speed') : '';
        var m = /^\s*([\d.]+)\s*(ms|s)?\s*$/.exec(String(raw || ''));
        if (!m) return 650;
        var v = parseFloat(m[1]);
        if (isNaN(v)) return 650;
        return m[2] === 's' ? v * 1000 : v;
    }

    function clearHint(dir) {
        leaf.classList.remove('bw-leaf-hint-' + dir);
    }

    function turn(href, dir) {
        try { sessionStorage.setItem(STORE, dir); } catch (e) { /* private mode */ }
        clearHint(dir);
        leaf.classList.add('bw-leaf-away-' + dir);
        window.setTimeout(function () { window.location.href = href; }, speedMs() + 50);
    }

    /* Fresh page: if we just turned here, settle into place. */
    var arrived = null;
    try { arrived = sessionStorage.getItem(STORE); sessionStorage.removeItem(STORE); } catch (e) { /* ignore */ }
    if ((arrived === 'next' || arrived === 'prev') && !reduced) {
        var settleClass = 'bw-leaf-settle-' + arrived;
        leaf.classList.add(settleClass);
        var clearSettle = function () { leaf.classList.remove(settleClass); };
        leaf.addEventListener('animationend', clearSettle, { once: true });
        window.setTimeout(clearSettle, Math.ceil(speedMs() * 0.7) + 250);
    }

    /* Click a turn link: curl this page away, then follow it. */
    document.addEventListener('click', function (ev) {
        if (ev.defaultPrevented || ev.button !== 0) return;
        if (ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) return;
        var a = ev.target.closest ? ev.target.closest('a[data-turn]') : null;
        if (!a || !a.href) return;
        var dir = a.getAttribute('data-turn');
        if (dir !== 'next' && dir !== 'prev') return;
        if (reduced) return; /* plain navigation */
        ev.preventDefault();
        turn(a.href, dir);
    });

    /* Arrow keys turn pages too. */
    document.addEventListener('keydown', function (ev) {
        if (ev.defaultPrevented) return;
        var t = ev.target;
        if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable)) return;
        var a = null;
        if (ev.key === 'ArrowRight') a = document.querySelector('a[data-turn="next"]');
        else if (ev.key === 'ArrowLeft') a = document.querySelector('a[data-turn="prev"]');
        if (!a || !a.href) return;
        ev.preventDefault();
        if (reduced) { window.location.href = a.href; return; }
        turn(a.href, a.getAttribute('data-turn'));
    });

    /* Hovering a turn link gives the page a faint curl. */
    Array.prototype.forEach.call(document.querySelectorAll('a[data-turn]'), function (a) {
        var dir = a.getAttribute('data-turn');
        var hint = 'bw-leaf-hint-' + dir;
        a.addEventListener('mouseenter', function () { if (!reduced) leaf.classList.add(hint); });
        a.addEventListener('mouseleave', function () { clearHint(dir); });
    });

    /* Back/forward cache: never replay a turn on return. */
    window.addEventListener('pageshow', function () {
        leaf.classList.remove(
            'bw-leaf-settle-next', 'bw-leaf-settle-prev',
            'bw-leaf-hint-next', 'bw-leaf-hint-prev'
        );
    });
})();
