/* ==========================================================================
   storefront-fly-to-cart.js
   Load with `defer` after storefront-motion.js. No CSS needed.

   When a quick-add form (form[data-cart-add]) is submitted, a copy of the
   product image shrinks along an arc into the cart button, which then pulses.
   ========================================================================== */
(function () {
    'use strict';

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    if (!('animate' in Element.prototype)) return;

    // Cart button selectors, tried in order. Add your own if the navbar differs.
    var CART_SELECTORS = ['[data-cart-toggle]', '.store-cart-btn', '.store-icon-btn[href*="cart"]'];

    function findCartTarget() {
        for (var i = 0; i < CART_SELECTORS.length; i++) {
            var els = document.querySelectorAll(CART_SELECTORS[i]);
            for (var j = 0; j < els.length; j++) {
                if (els[j].offsetParent !== null) return els[j]; // visible only
            }
        }
        return null;
    }

    function fly(img, target) {
        var from = img.getBoundingClientRect();
        var to = target.getBoundingClientRect();
        if (!from.width || !from.height) return;

        var size = Math.min(from.width, 160);
        var ratio = from.height / from.width;
        var startX = from.left + from.width / 2 - size / 2;
        var startY = from.top + from.height / 2 - (size * ratio) / 2;

        var clone = document.createElement('img');
        clone.src = img.currentSrc || img.src;
        clone.alt = '';
        clone.setAttribute('aria-hidden', 'true');
        clone.style.cssText = [
            'position:fixed',
            'left:' + startX + 'px',
            'top:' + startY + 'px',
            'width:' + size + 'px',
            'height:' + size * ratio + 'px',
            'object-fit:cover',
            'border-radius:14px',
            'box-shadow:0 12px 30px rgba(15,23,42,.25)',
            'z-index:2100',
            'pointer-events:none',
            'transform-origin:center'
        ].join(';');
        document.body.appendChild(clone);

        // Aim at the cart button; if the navbar is scrolled away, aim at the top edge
        var dx = to.left + to.width / 2 - (startX + size / 2);
        var targetY = Math.max(to.top + to.height / 2, 28);
        var dy = targetY - (startY + (size * ratio) / 2);
        var lift = Math.min(120, Math.max(40, Math.abs(dy) * 0.25));

        var flight = clone.animate([
            { transform: 'translate(0, 0) scale(1)', opacity: 1, offset: 0 },
            { transform: 'translate(' + dx * 0.4 + 'px, ' + (dy * 0.4 - lift) + 'px) scale(0.6)', opacity: 1, offset: 0.45 },
            { transform: 'translate(' + dx + 'px, ' + dy + 'px) scale(0.08)', opacity: 0.3, offset: 1 }
        ], {
            duration: 750,
            easing: 'cubic-bezier(0.5, 0, 0.2, 1)',
            fill: 'forwards'
        });

        flight.onfinish = function () {
            clone.remove();
            target.animate([
                { transform: 'scale(1)' },
                { transform: 'scale(1.18)', offset: 0.4 },
                { transform: 'scale(1)' }
            ], { duration: 420, easing: 'cubic-bezier(0.34, 1.56, 0.64, 1)' });
        };
        flight.oncancel = function () { clone.remove(); };
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form.matches || !form.matches('form[data-cart-add]')) return;

        var card = form.closest('.product-card');
        var img = card
            ? card.querySelector('.product-card-img')
            : document.querySelector('[data-gallery-main]'); // product page form
        var target = findCartTarget();
        if (img && target) fly(img, target);
    }, true); // capture: runs before any AJAX handler can stop propagation
})();
