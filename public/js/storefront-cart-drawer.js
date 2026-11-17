/* ==========================================================================
   storefront-cart-drawer.js
   Load with `defer` on every storefront page (put it in the layout).
   Self-contained: injects its own styles, no Blade changes required.

   - Free-shipping progress bar under the drawer header (live as the cart changes)
   - Items slide in, staggered, when the drawer opens
   - A newly added (or increased) item flashes briefly
   - Removing an item collapses it smoothly
   - Drawer opens itself after a successful add (set AUTO_OPEN = false to disable)

   Built against these existing hooks:
     .cart-drawer, .cart-drawer-header, .cart-drawer-items, .cart-drawer-item,
     form[data-cart-remove], and the `cart:updated` event fired by your AJAX cart.
   ========================================================================== */
(function () {
    'use strict';

    var AUTO_OPEN = true;
    var DEFAULT_THRESHOLD = 75; // overridden by data-free-shipping-threshold on .cart-drawer

    var drawer = document.querySelector('.cart-drawer');
    if (!drawer) return;

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var threshold = parseFloat(
        drawer.getAttribute('data-free-shipping-threshold') ||
        window.STORE_FREE_SHIPPING_THRESHOLD ||
        DEFAULT_THRESHOLD
    );

    /* ---------------- styles ---------------- */
    var style = document.createElement('style');
    style.textContent = [
        /* free shipping bar */
        '.cart-ship{padding:14px 24px 16px;background:#fff;border-bottom:1px solid var(--store-border,#e2e8f0)}',
        '.cart-ship[hidden]{display:none}',
        '.cart-ship__text{display:flex;align-items:center;gap:8px;margin-bottom:10px;font-size:13px;font-weight:600;',
        'color:var(--store-text,#0f172a)}',
        '.cart-ship__icon{display:inline-block;font-size:16px;color:var(--store-text-muted,#64748b)}',
        '.cart-ship__track{height:6px;border-radius:9999px;overflow:hidden;background:var(--store-surface-alt,#f1f5f9)}',
        '.cart-ship__fill{height:100%;width:100%;border-radius:inherit;transform:scaleX(0);transform-origin:0 50%;',
        'background:var(--store-accent,#2563eb);transition:transform .8s cubic-bezier(.16,1,.3,1),background-color .3s ease}',
        '.cart-ship.is-unlocked .cart-ship__fill{background:var(--store-success,#10b981)}',
        '.cart-ship.is-unlocked .cart-ship__icon{color:var(--store-success,#10b981)}',
        '.cart-ship.just-unlocked .cart-ship__icon{animation:cartShipGo .9s cubic-bezier(.5,0,.2,1)}',
        '@keyframes cartShipGo{0%{transform:translateX(0);opacity:1}45%{transform:translateX(28px);opacity:0}',
        '46%{transform:translateX(-28px);opacity:0}100%{transform:none;opacity:1}}',

        /* items entering when the drawer opens */
        '@keyframes cartItemIn{from{opacity:0;transform:translateX(24px)}to{opacity:1;transform:none}}',
        '.cart-drawer.is-entering .cart-drawer-item{animation:cartItemIn .5s cubic-bezier(.16,1,.3,1) both;',
        'animation-delay:360ms}',
        '.cart-drawer.is-entering .cart-drawer-item:nth-child(1){animation-delay:140ms}',
        '.cart-drawer.is-entering .cart-drawer-item:nth-child(2){animation-delay:200ms}',
        '.cart-drawer.is-entering .cart-drawer-item:nth-child(3){animation-delay:260ms}',
        '.cart-drawer.is-entering .cart-drawer-item:nth-child(4){animation-delay:320ms}',

        /* new / increased item */
        '@keyframes cartItemFlash{0%,35%{background-color:rgba(37,99,235,.1)}100%{background-color:transparent}}',
        '.cart-drawer-item.is-new{border-radius:10px;animation:cartItemFlash 1.8s ease}',

        /* removing an item */
        '.cart-drawer-item.is-removing{overflow:hidden;opacity:0;transform:translateX(30px);max-height:0!important;',
        'padding-top:0;padding-bottom:0;border-bottom-width:0;',
        'transition:opacity .25s ease,transform .3s cubic-bezier(.16,1,.3,1),max-height .35s ease .1s,',
        'padding .35s ease .1s,border-width .35s ease .1s}',

        /* empty state */
        '@keyframes cartEmptyIn{from{opacity:0;transform:scale(.94)}to{opacity:1;transform:none}}',
        '.cart-drawer-empty{animation:cartEmptyIn .5s cubic-bezier(.16,1,.3,1)}',

        '@media (prefers-reduced-motion:reduce){',
        '.cart-ship__fill{transition:none}',
        '.cart-ship.just-unlocked .cart-ship__icon,.cart-drawer.is-entering .cart-drawer-item,',
        '.cart-drawer-item.is-new,.cart-drawer-empty{animation:none}',
        '.cart-drawer-item.is-removing{transition:none}}'
    ].join('');
    document.head.appendChild(style);

    /* ---------------- free-shipping bar ---------------- */
    var bar = document.createElement('div');
    bar.className = 'cart-ship';
    bar.hidden = true;
    bar.setAttribute('aria-live', 'polite');
    bar.innerHTML =
        '<div class="cart-ship__text"><i class="bi bi-truck cart-ship__icon" aria-hidden="true"></i>' +
        '<span class="cart-ship__msg"></span></div>' +
        '<div class="cart-ship__track" role="progressbar" aria-label="Progress to free delivery" ' +
        'aria-valuemin="0" aria-valuemax="100"><div class="cart-ship__fill"></div></div>';

    var header = drawer.querySelector('.cart-drawer-header');
    var itemsBox = drawer.querySelector('.cart-drawer-items');
    if (header) {
        header.insertAdjacentElement('afterend', bar);
    } else if (itemsBox) {
        itemsBox.insertAdjacentElement('beforebegin', bar);
    } else {
        drawer.insertBefore(bar, drawer.firstChild);
    }

    var msg = bar.querySelector('.cart-ship__msg');
    var track = bar.querySelector('.cart-ship__track');
    var fill = bar.querySelector('.cart-ship__fill');
    var wasUnlocked = null; // null until the first reading, so loading a full cart doesn't celebrate

    function money(n) {
        return '$' + n.toFixed(2);
    }

    // Uses data-subtotal on .cart-drawer-item if you add it, otherwise reads the price text
    function readCart() {
        var items = drawer.querySelectorAll('.cart-drawer-item');
        var total = 0;

        items.forEach(function (item) {
            var raw = item.getAttribute('data-subtotal');
            if (raw === null && item.lastElementChild) raw = item.lastElementChild.textContent;
            var n = parseFloat(String(raw).replace(/[^0-9.]/g, ''));
            if (!isNaN(n)) total += n;
        });

        return { count: items.length, total: total };
    }

    function updateBar() {
        var cart = readCart();
        bar.hidden = cart.count === 0;

        var ratio = threshold > 0 ? Math.min(cart.total / threshold, 1) : 1;
        var unlocked = cart.total >= threshold;

        fill.style.transform = 'scaleX(' + ratio + ')';
        track.setAttribute('aria-valuenow', String(Math.round(ratio * 100)));
        msg.textContent = unlocked
            ? 'You\u2019ve unlocked free delivery'
            : money(threshold - cart.total) + ' away from free delivery';
        bar.classList.toggle('is-unlocked', unlocked);

        if (unlocked && wasUnlocked === false && !reduceMotion) {
            bar.classList.remove('just-unlocked');
            void bar.offsetWidth;
            bar.classList.add('just-unlocked');
            setTimeout(function () { bar.classList.remove('just-unlocked'); }, 1100);
        }
        wasUnlocked = unlocked;
    }

    /* ---------------- highlight new / increased items ---------------- */
    function describe(item) {
        var link = item.querySelector('a.fw-semibold');
        var key = link ? link.getAttribute('href') : '';
        var qty = 0;

        item.querySelectorAll('.small.text-muted').forEach(function (el) {
            var text = el.textContent.trim();
            var match = text.match(/^Qty\s+(\d+)/i);
            if (match) {
                qty = parseInt(match[1], 10);
            } else {
                key += '|' + text; // variant name
            }
        });

        return { key: key, qty: qty };
    }

    function snapshot() {
        var map = {};
        drawer.querySelectorAll('.cart-drawer-item').forEach(function (item) {
            var d = describe(item);
            map[d.key] = d.qty;
        });
        return map;
    }

    var previous = snapshot();

    function highlightChanges() {
        var next = {};

        drawer.querySelectorAll('.cart-drawer-item').forEach(function (item) {
            var d = describe(item);
            next[d.key] = d.qty;

            var isNew = !(d.key in previous);
            var increased = !isNew && d.qty > previous[d.key];

            if ((isNew || increased) && !reduceMotion) {
                item.classList.add('is-new');
                setTimeout(function () { item.classList.remove('is-new'); }, 1900);
            }
        });

        previous = next;
    }

    /* ---------------- observe the drawer's contents ---------------- */
    var scheduled = false;

    function onContentChange() {
        scheduled = false;
        updateBar();
        highlightChanges();
    }

    new MutationObserver(function (records) {
        // Ignore our own bar updates, otherwise we'd loop
        var relevant = records.some(function (record) { return !bar.contains(record.target); });
        if (!relevant || scheduled) return;
        scheduled = true;
        requestAnimationFrame(onContentChange);
    }).observe(drawer, { childList: true, subtree: true });

    /* ---------------- staggered entrance on open ---------------- */
    var wasOpen = drawer.classList.contains('is-open');

    new MutationObserver(function () {
        var open = drawer.classList.contains('is-open');

        if (open && !wasOpen && !reduceMotion) {
            drawer.classList.add('is-entering');
            setTimeout(function () { drawer.classList.remove('is-entering'); }, 1000);
        }
        wasOpen = open;
    }).observe(drawer, { attributes: true, attributeFilter: ['class'] });

    /* ---------------- removal animation ---------------- */
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form.matches || !form.matches('form[data-cart-remove]') || reduceMotion) return;

        var item = form.closest('.cart-drawer-item');
        if (!item) return;

        item.style.maxHeight = item.offsetHeight + 'px';
        void item.offsetHeight; // commit the starting height before collapsing
        item.classList.add('is-removing');

        // If the request fails and the item is never re-rendered, bring it back
        setTimeout(function () {
            if (item.isConnected) {
                item.classList.remove('is-removing');
                item.style.maxHeight = '';
            }
        }, 4000);
    }, true);

    /* ---------------- open the drawer after adding ---------------- */
    function openDrawer() {
        if (drawer.classList.contains('is-open')) return;

        var backdrop = document.querySelector('.cart-drawer-backdrop');
        drawer.classList.add('is-open');
        if (backdrop) backdrop.classList.add('is-open');
        document.body.classList.add('cart-drawer-open');
        if (drawer.hasAttribute('aria-hidden')) drawer.setAttribute('aria-hidden', 'false');
    }

    // Capture phase: works even if the event doesn't bubble
    document.addEventListener('cart:updated', function (event) {
        if (!AUTO_OPEN) return;
        if (/\/(cart|checkout)/.test(window.location.pathname)) return;

        var source = event.target;
        if (source && source.matches && source.matches('[data-cart-remove]')) return;

        // Wait a beat so your own code can open the drawer first; only open if it hasn't
        setTimeout(openDrawer, 60);
    }, true);

    updateBar();
})();
