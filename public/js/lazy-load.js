/**
 * LazyLoad - Global Image Lazy Loading Module
 * Uses IntersectionObserver for performance
 * Supports: lazy images, lazy backgrounds, blur-up, skeleton, error fallback
 */
(function () {
    'use strict';

    var PLACEHOLDER_SVG = 'data:image/svg+xml,' + encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" fill="%23f1f3f5">' +
        '<rect width="400" height="300"/>' +
        '<text x="200" y="150" text-anchor="middle" dy=".3em" fill="%23adb5bd" font-family="sans-serif" font-size="14">Loading...</text></svg>'
    );

    var observer = null;
    var observedElements = new WeakSet();

    function getObserver() {
        if (observer) return observer;
        if (!('IntersectionObserver' in window)) {
            loadAllImmediately();
            return null;
        }
        observer = new IntersectionObserver(onIntersect, {
            rootMargin: '200px 0px',
            threshold: 0.01
        });
        return observer;
    }

    function onIntersect(entries) {
        for (var i = 0; i < entries.length; i++) {
            if (entries[i].isIntersecting) {
                loadImage(entries[i].target);
            }
        }
    }

    function loadImage(el) {
        if (observedElements.has(el)) {
            observedElements.delete(el);
            var obs = getObserver();
            if (obs) obs.unobserve(el);
        }

        var src = el.getAttribute('data-src');
        var srcset = el.getAttribute('data-srcset');

        if (el.tagName === 'IMG') {
            loadImgElement(el, src, srcset);
        } else if (el.hasAttribute('data-bg')) {
            loadBgElement(el);
        }
    }

    function loadImgElement(el, src, srcset) {
        if (!src) return;

        var tempImg = new Image();
        tempImg.onload = function () {
            el.src = src;
            if (srcset) el.srcset = srcset;
            el.removeAttribute('data-src');
            el.removeAttribute('data-srcset');
            el.classList.add('loaded');
            el.classList.remove('lazy-loading');
        };
        tempImg.onerror = function () {
            el.src = PLACEHOLDER_SVG;
            el.alt = el.alt || 'Image not available';
            el.classList.add('lazy-error');
            el.classList.remove('lazy-loading');
        };
        tempImg.src = src;
    }

    function loadBgElement(el) {
        var bg = el.getAttribute('data-bg');
        if (!bg) return;

        var tempImg = new Image();
        tempImg.onload = function () {
            el.style.backgroundImage = 'url(' + bg + ')';
            el.removeAttribute('data-bg');
            el.classList.add('loaded');
            el.classList.remove('lazy-loading');
        };
        tempImg.onerror = function () {
            el.classList.add('lazy-error');
            el.classList.remove('lazy-loading');
        };
        tempImg.src = bg;
    }

    function loadAllImmediately() {
        var els = document.querySelectorAll('[data-src], [data-srcset], [data-bg]');
        for (var i = 0; i < els.length; i++) {
            loadImage(els[i]);
        }
    }

    function observeElement(el) {
        if (observedElements.has(el)) return;
        var obs = getObserver();
        if (!obs) {
            loadImage(el);
            return;
        }
        observedElements.add(el);
        obs.observe(el);
    }

    function init(container) {
        container = container || document;
        var selectors = '[data-src], [data-srcset], [data-bg]';
        var els = container.querySelectorAll(selectors);
        for (var i = 0; i < els.length; i++) {
            observeElement(els[i]);
        }
    }

    function destroy() {
        if (observer) {
            observer.disconnect();
            observer = null;
        }
        observedElements = new WeakSet();
    }

    window.LazyLoad = {
        init: init,
        destroy: destroy,
        loadImage: loadImage,
        observe: observeElement,
        PLACEHOLDER: PLACEHOLDER_SVG
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { init(); });
    } else {
        init();
    }
})();
