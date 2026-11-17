/* ==========================================================================
   storefront-sticky-cart.js
   Product page only. Load with `defer` (or inside @push('scripts') after the
   page's own script). No changes to product.blade.php are needed.

   - Slides up on phones/tablets once the main "Add to Cart" button scrolls
     out of view, and tucks away again when the footer appears.
   - Mirrors the live product name, image and price (including variant prices).
   - If options are still unselected, the button reads "Choose options" and
     guides the shopper back to the selectors instead of sitting disabled.
   - Submits the real #productAddForm, so your existing AJAX cart handler and
     the fly-to-cart animation keep working.
   ========================================================================== */
(function () {
    'use strict';

    var form = document.getElementById('productAddForm');
    var mainBtn = document.querySelector('button[type="submit"][form="productAddForm"]');
    if (!form || !mainBtn) return; // out-of-stock products have no form

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var titleEl = document.querySelector('.product-detail-wrapper h1');
    var priceSrc = document.querySelector('[data-product-price]');
    var galleryMain = document.querySelector('[data-gallery-main]');
    var variantInput = document.querySelector('[data-selected-variant]');
    var optionsBox = document.querySelector('[data-product-options]');
    var footer = document.querySelector('.store-footer');

    /* ---------------- styles ---------------- */
    var style = document.createElement('style');
    style.textContent = [
        '.sticky-cart{position:fixed;left:0;right:0;bottom:0;z-index:1040;display:flex;align-items:center;gap:12px;',
        'padding:10px 16px calc(10px + env(safe-area-inset-bottom,0px));background:rgba(255,255,255,.96);',
        '-webkit-backdrop-filter:blur(12px);backdrop-filter:blur(12px);border-top:1px solid var(--store-border,#e2e8f0);',
        'box-shadow:0 -10px 30px rgba(15,23,42,.1);transform:translateY(110%);visibility:hidden;',
        'transition:transform .45s cubic-bezier(.16,1,.3,1),visibility 0s linear .45s}',
        '.sticky-cart.is-visible{transform:none;visibility:visible;transition-delay:0s}',
        '.sticky-cart__img{width:44px;height:56px;border-radius:8px;object-fit:cover;object-position:center top;',
        'border:1px solid var(--store-border,#e2e8f0);flex-shrink:0;background:#f8fafc}',
        '.sticky-cart__info{min-width:0;flex:1}',
        '.sticky-cart__name{font-size:13px;font-weight:600;line-height:1.3;white-space:nowrap;overflow:hidden;',
        'text-overflow:ellipsis;color:var(--store-primary,#0f172a)}',
        '.sticky-cart__price{font-size:15px;font-weight:700;color:var(--store-primary,#0f172a)}',
        '.sticky-cart__btn{flex-shrink:0;padding:11px 18px!important;font-size:14px!important;white-space:nowrap}',
        '.sticky-cart__btn.is-added{background-color:var(--store-success,#10b981)!important;',
        'border-color:var(--store-success,#10b981)!important}',
        '.sticky-cart__btn:disabled{opacity:.55;cursor:not-allowed}',
        '@media (min-width:992px){.sticky-cart{display:none}}',
        '@keyframes stickyShake{0%,100%{transform:translateX(0)}20%{transform:translateX(-6px)}',
        '40%{transform:translateX(6px)}60%{transform:translateX(-4px)}80%{transform:translateX(4px)}}',
        '.needs-attention{animation:stickyShake .45s ease;border-color:var(--store-danger,#ef4444)!important;',
        'box-shadow:0 0 0 3px rgba(239,68,68,.15)!important}',
        '@media (prefers-reduced-motion:reduce){.sticky-cart{transition:none}.needs-attention{animation:none}}'
    ].join('');
    document.head.appendChild(style);

    /* ---------------- markup ---------------- */
    var bar = document.createElement('div');
    bar.className = 'sticky-cart';
    bar.setAttribute('role', 'region');
    bar.setAttribute('aria-label', 'Quick add to cart');
    bar.setAttribute('aria-hidden', 'true');
    bar.inert = true;

    var img = document.createElement('img');
    img.className = 'sticky-cart__img';
    img.alt = '';

    var info = document.createElement('div');
    info.className = 'sticky-cart__info';
    var nameEl = document.createElement('div');
    nameEl.className = 'sticky-cart__name';
    var priceEl = document.createElement('div');
    priceEl.className = 'sticky-cart__price';
    info.appendChild(nameEl);
    info.appendChild(priceEl);

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-store-primary sticky-cart__btn';
    var icon = document.createElement('i');
    var label = document.createElement('span');
    btn.appendChild(icon);
    btn.appendChild(document.createTextNode(' '));
    btn.appendChild(label);

    bar.appendChild(img);
    bar.appendChild(info);
    bar.appendChild(btn);
    document.body.appendChild(bar);

    /* ---------------- state sync ---------------- */
    var mode = 'add';
    var flashing = false;

    function setBtn(nextMode, text, iconClass, disabled) {
        mode = nextMode;
        label.textContent = text;
        icon.className = 'bi ' + iconClass;
        btn.disabled = disabled;
    }

    function sync() {
        nameEl.textContent = titleEl ? titleEl.textContent.trim() : document.title;
        priceEl.textContent = priceSrc ? priceSrc.textContent.trim() : '';

        if (galleryMain && galleryMain.src) {
            img.hidden = false;
            img.src = galleryMain.currentSrc || galleryMain.src;
        } else {
            img.hidden = true;
        }

        if (flashing) return;

        var needsChoice = !!variantInput && variantInput.value === '';
        if (needsChoice) {
            setBtn('choose', 'Choose options', 'bi-sliders', false);
        } else if (mainBtn.disabled) {
            setBtn('off', 'Unavailable', 'bi-x-circle', true);
        } else {
            setBtn('add', 'Add to Cart', 'bi-bag-plus', false);
        }
    }

    // The page's script updates these nodes; watch them rather than patching it
    var observe = function (el, config) {
        if (el) new MutationObserver(sync).observe(el, config);
    };
    observe(priceSrc, { childList: true, characterData: true, subtree: true });
    observe(mainBtn, { attributes: true, attributeFilter: ['disabled'] });
    observe(galleryMain, { attributes: true, attributeFilter: ['src'] });

    // Delegated, so it runs after the page's own change handlers have updated the variant
    document.addEventListener('change', function (event) {
        if (event.target.matches && event.target.matches('[data-variant-option]')) sync();
    });

    /* ---------------- show / hide ---------------- */
    var pastButton = false;
    var footerVisible = false;

    function render() {
        var visible = pastButton && !footerVisible;
        bar.classList.toggle('is-visible', visible);
        bar.setAttribute('aria-hidden', String(!visible));
        bar.inert = !visible;
    }

    if ('IntersectionObserver' in window) {
        new IntersectionObserver(function (entries) {
            var entry = entries[entries.length - 1];
            // Show only after the button has scrolled off the TOP of the screen
            pastButton = !entry.isIntersecting && entry.boundingClientRect.top < window.innerHeight / 2;
            render();
        }, { rootMargin: '-70px 0px 0px 0px' }).observe(mainBtn);

        if (footer) {
            new IntersectionObserver(function (entries) {
                footerVisible = entries[entries.length - 1].isIntersecting;
                render();
            }).observe(footer);
        }
    }

    /* ---------------- actions ---------------- */
    function guideToOptions() {
        if (!optionsBox) return;

        var empty = Array.prototype.filter.call(
            optionsBox.querySelectorAll('[data-variant-option]'),
            function (select) { return select.value === ''; }
        );

        optionsBox.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });

        empty.forEach(function (select) {
            select.classList.remove('needs-attention');
            void select.offsetWidth; // restart the shake
            select.classList.add('needs-attention');
        });
        setTimeout(function () {
            empty.forEach(function (select) { select.classList.remove('needs-attention'); });
        }, 1400);

        if (empty[0]) setTimeout(function () { empty[0].focus({ preventScroll: true }); }, 450);
    }

    btn.addEventListener('click', function () {
        if (mode === 'choose') {
            guideToOptions();
        } else if (mode === 'add') {
            if (form.requestSubmit) {
                form.requestSubmit(mainBtn);
            } else {
                mainBtn.click();
            }
        }
    });

    // Confirm after the AJAX cart handler reports success
    form.addEventListener('cart:updated', function () {
        flashing = true;
        btn.classList.add('is-added');
        icon.className = 'bi bi-check2';
        label.textContent = 'Added';

        setTimeout(function () {
            flashing = false;
            btn.classList.remove('is-added');
            sync();
        }, 1400);
    });

    sync();
    render();
})();
