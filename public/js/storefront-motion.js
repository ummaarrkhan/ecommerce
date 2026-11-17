/* ==========================================================================
   storefront-motion.js
   Load with `defer` after theme.css + storefront-motion.css.
   No dependencies, no changes needed in home.blade.php.
   ========================================================================== */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    ready(function () {
        initCartFeedback();
        initCategoryPills();

        if (reduceMotion) return; // everything below is decorative

        initImageFade();
        initScrollReveal();
        initHeroCounters();
        initScrollEffects();
    });

    /* ----------------------------------------------------------------------
       Product cards ease in as they enter the viewport
       ---------------------------------------------------------------------- */
    function initScrollReveal() {
        var cols = document.querySelectorAll('#featured-products .row > .col, .row > .col:has(> .product-card)');
        var seen = new Set();
        var items = [];

        cols.forEach(function (col) {
            if (!col.querySelector('.product-card') || seen.has(col)) return;
            seen.add(col);
            items.push(col);
        });

        if (!items.length || !('IntersectionObserver' in window)) return;

        var observer = new IntersectionObserver(function (entries) {
            // Stagger only the cards that arrive in the same batch
            var batch = 0;
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.style.setProperty('--reveal-delay', Math.min(batch, 5) * 70 + 'ms');
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
                batch++;
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });

        items.forEach(function (el) {
            el.setAttribute('data-reveal', '');
            observer.observe(el);
        });
    }

    /* ----------------------------------------------------------------------
       Lazy product images fade in over a skeleton
       ---------------------------------------------------------------------- */
    function initImageFade() {
        document.querySelectorAll('.product-card-img').forEach(function (img) {
            if (img.complete && img.naturalWidth > 0) return; // already there, leave it

            var wrapper = img.closest('.product-card-img-wrapper');
            img.setAttribute('data-img-fade', '');
            if (wrapper) wrapper.setAttribute('data-skeleton', '');

            function done() {
                img.classList.add('is-loaded');
                if (wrapper) wrapper.removeAttribute('data-skeleton');
            }

            img.addEventListener('load', done, { once: true });
            img.addEventListener('error', done, { once: true });
        });
    }

    /* ----------------------------------------------------------------------
       Hero stats count up once, right after the hero sequence starts
       Skips non-numeric values such as "Fast".
       ---------------------------------------------------------------------- */
    function initHeroCounters() {
        var stats = document.querySelectorAll('.hero-section strong.fs-5');

        stats.forEach(function (el) {
            var original = el.textContent.trim();
            var match = original.match(/^([\d,]*\.?\d+)(.*)$/);
            if (!match) return;

            var target = parseFloat(match[1].replace(/,/g, ''));
            var suffix = match[2];
            var decimals = (match[1].split('.')[1] || '').length;
            var duration = 1600;
            var delay = 650;

            // Reserve the final width so the layout doesn't jump while counting
            el.style.display = 'inline-block';
            el.style.minWidth = el.getBoundingClientRect().width + 'px';
            el.style.fontVariantNumeric = 'tabular-nums';

            setTimeout(function () {
                var start = performance.now();

                function tick(now) {
                    var t = Math.min((now - start) / duration, 1);
                    var eased = t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
                    var value = target * eased;

                    el.textContent = value.toLocaleString('en-US', {
                        minimumFractionDigits: decimals,
                        maximumFractionDigits: decimals
                    }) + suffix;

                    if (t < 1) {
                        requestAnimationFrame(tick);
                    } else {
                        el.textContent = original;
                    }
                }

                requestAnimationFrame(tick);
            }, delay);
        });
    }

    /* ----------------------------------------------------------------------
       Scroll progress bar + navbar that hides on scroll down
       ---------------------------------------------------------------------- */
    function initScrollEffects() {
        var bar = document.createElement('div');
        bar.className = 'scroll-progress';
        bar.setAttribute('aria-hidden', 'true');
        document.body.appendChild(bar);

        var navbar = document.querySelector('.store-navbar');
        var lastY = window.scrollY;
        var ticking = false;

        function update() {
            var y = window.scrollY;
            var max = document.documentElement.scrollHeight - window.innerHeight;
            bar.style.transform = 'scaleX(' + (max > 0 ? Math.min(y / max, 1) : 0) + ')';

            if (navbar) {
                navbar.classList.toggle('scrolled', y > 8);

                var searchOpen = navbar.contains(document.activeElement) &&
                                 document.activeElement.matches('input, select, textarea');
                var goingDown = y > lastY + 4;
                var goingUp = y < lastY - 4;

                if (goingDown && y > 400 && !searchOpen) {
                    navbar.classList.add('is-hidden');
                } else if (goingUp || y < 400) {
                    navbar.classList.remove('is-hidden');
                }
            }

            lastY = y;
            ticking = false;
        }

        window.addEventListener('scroll', function () {
            if (!ticking) {
                requestAnimationFrame(update);
                ticking = true;
            }
        }, { passive: true });

        // Anchor jumps (e.g. "Explore Catalog") shouldn't leave the navbar hidden
        window.addEventListener('hashchange', function () {
            if (navbar) navbar.classList.remove('is-hidden');
        });

        update();
    }

    /* ----------------------------------------------------------------------
       Add to cart: the button confirms, and the cart badge bumps
       (runs even with reduced motion, since it communicates state)
       ---------------------------------------------------------------------- */
    function initCartFeedback() {
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!form.matches || !form.matches('form[data-cart-add]')) return;

            var button = form.querySelector('.btn-card-add');
            var icon = button && button.querySelector('i');
            if (!button || !icon) return;

            var originalClass = icon.className;
            button.classList.add('is-added');
            icon.className = 'bi bi-check2';

            setTimeout(function () {
                button.classList.remove('is-added');
                icon.className = originalClass;
            }, 1400);
        });

        // Bump the cart badge whenever its number changes (works with AJAX carts)
        document.querySelectorAll('.store-cart-count').forEach(function (badge) {
            new MutationObserver(function () {
                badge.classList.remove('is-bumping');
                void badge.offsetWidth; // restart the animation
                badge.classList.add('is-bumping');
            }).observe(badge, { childList: true, characterData: true, subtree: true });
        });
    }

    /* ----------------------------------------------------------------------
       Keep the active category pill in view inside the scrolling row
       ---------------------------------------------------------------------- */
    function initCategoryPills() {
        var container = document.querySelector('.category-scroll-container');
        var active = container && container.querySelector('.category-pill.active');
        if (!container || !active) return;

        var c = container.getBoundingClientRect();
        var a = active.getBoundingClientRect();
        var offset = a.left - c.left - (c.width - a.width) / 2;

        container.scrollTo({
            left: container.scrollLeft + offset,
            behavior: reduceMotion ? 'auto' : 'smooth'
        });
    }
})();
