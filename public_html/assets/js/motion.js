/* ============================================================
   CodigoAmigo — motion.js
   Sistema global de animaciones: Lenis (smooth scroll) + GSAP + ScrollTrigger.
   Activación por data-attributes — cualquier página opta sin tocar JS.

   API:
   - data-reveal              fade + slide-up al entrar al viewport
   - data-reveal="up|down|left|right|scale|fade"
   - data-reveal-delay="0.2"  retardo en segundos
   - data-stagger             anima hijos secuencialmente
   - data-stagger-amount="0.08"
   - data-parallax="0.3"      parallax suave en scroll
   - data-count="137502"      count-up numérico
   - data-count-suffix="€"
   - data-count-prefix=""
   - data-magnetic            efecto imán al cursor (botones)
   - data-tilt                tilt 3D al hover
   - [data-scroll-pin]        pin al hacer scroll (avanzado)

   Guards:
   - Respeta prefers-reduced-motion
   - Desactiva Lenis en touch <768px (rendimiento)
   - Idempotente: detecta DOMContentLoaded + MutationObserver para nodos inyectados
   ============================================================ */

(function () {
    'use strict';

    var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var isTouch = ('ontouchstart' in window) && window.matchMedia('(max-width: 768px)').matches;

    function whenReady(fn) {
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    }

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = src;
            s.async = true;
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    // ─── Lenis smooth scroll ─────────────────────────────
    function initLenis() {
        if (prefersReduced || isTouch || typeof Lenis === 'undefined') return null;
        var lenis = new Lenis({
            duration: 1.1,
            easing: function (t) { return Math.min(1, 1.001 - Math.pow(2, -10 * t)); },
            smoothWheel: true,
            smoothTouch: false,
            wheelMultiplier: 1,
            touchMultiplier: 1.5
        });
        function raf(time) { lenis.raf(time); requestAnimationFrame(raf); }
        requestAnimationFrame(raf);
        window.__lenis = lenis;
        // Conectar Lenis con ScrollTrigger
        if (window.ScrollTrigger) {
            lenis.on('scroll', ScrollTrigger.update);
            gsap.ticker.add(function (time) { lenis.raf(time * 1000); });
            gsap.ticker.lagSmoothing(0);
        }
        return lenis;
    }

    // ─── Helpers GSAP ───────────────────────────────────
    function revealDirVars(dir) {
        switch (dir) {
            case 'down':  return { y: -40, opacity: 0 };
            case 'left':  return { x: 40, opacity: 0 };
            case 'right': return { x: -40, opacity: 0 };
            case 'scale': return { scale: 0.92, opacity: 0 };
            case 'fade':  return { opacity: 0 };
            case 'up':
            default:      return { y: 40, opacity: 0 };
        }
    }

    function initReveals() {
        if (prefersReduced) return;
        if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;

        document.querySelectorAll('[data-reveal]:not([data-reveal-init])').forEach(function (el) {
            el.setAttribute('data-reveal-init', '1');
            var dir = el.getAttribute('data-reveal') || 'up';
            var delay = parseFloat(el.getAttribute('data-reveal-delay') || '0');
            var dur = parseFloat(el.getAttribute('data-reveal-duration') || '0.7');
            var from = revealDirVars(dir);
            gsap.from(el, {
                ...from,
                duration: dur,
                delay: delay,
                ease: 'power3.out',
                scrollTrigger: { trigger: el, start: 'top 88%', toggleActions: 'play none none none' }
            });
        });

        document.querySelectorAll('[data-stagger]:not([data-stagger-init])').forEach(function (el) {
            el.setAttribute('data-stagger-init', '1');
            var amount = parseFloat(el.getAttribute('data-stagger-amount') || '0.08');
            var dir = el.getAttribute('data-stagger') || 'up';
            var from = revealDirVars(dir);
            var children = Array.from(el.children);
            if (!children.length) return;
            gsap.from(children, {
                ...from,
                duration: 0.6,
                ease: 'power3.out',
                stagger: amount,
                scrollTrigger: { trigger: el, start: 'top 85%', toggleActions: 'play none none none' }
            });
        });

        document.querySelectorAll('[data-parallax]:not([data-parallax-init])').forEach(function (el) {
            el.setAttribute('data-parallax-init', '1');
            var depth = parseFloat(el.getAttribute('data-parallax') || '0.3');
            gsap.to(el, {
                yPercent: -depth * 100,
                ease: 'none',
                scrollTrigger: { trigger: el, start: 'top bottom', end: 'bottom top', scrub: true }
            });
        });
    }

    // ─── Count-up ───────────────────────────────────────
    function initCounters() {
        if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;
        document.querySelectorAll('[data-count]:not([data-count-init])').forEach(function (el) {
            el.setAttribute('data-count-init', '1');
            var end = parseFloat(el.getAttribute('data-count')) || 0;
            var prefix = el.getAttribute('data-count-prefix') || '';
            var suffix = el.getAttribute('data-count-suffix') || '';
            var decimals = parseInt(el.getAttribute('data-count-decimals') || '0', 10);
            var dur = parseFloat(el.getAttribute('data-count-duration') || '1.6');
            var obj = { val: 0 };
            ScrollTrigger.create({
                trigger: el,
                start: 'top 90%',
                once: true,
                onEnter: function () {
                    gsap.to(obj, {
                        val: end,
                        duration: dur,
                        ease: 'power2.out',
                        onUpdate: function () {
                            var n = obj.val;
                            var s = decimals > 0
                                ? n.toFixed(decimals)
                                : Math.floor(n).toLocaleString('es-ES');
                            el.textContent = prefix + s + suffix;
                        }
                    });
                }
            });
        });
    }

    // ─── Magnetic buttons ───────────────────────────────
    function initMagnetic() {
        if (prefersReduced || isTouch) return;
        document.querySelectorAll('[data-magnetic]:not([data-magnetic-init])').forEach(function (el) {
            el.setAttribute('data-magnetic-init', '1');
            var strength = parseFloat(el.getAttribute('data-magnetic') || '0.25');
            el.addEventListener('mousemove', function (e) {
                var r = el.getBoundingClientRect();
                var x = (e.clientX - r.left - r.width / 2) * strength;
                var y = (e.clientY - r.top - r.height / 2) * strength;
                gsap.to(el, { x: x, y: y, duration: 0.4, ease: 'power3.out' });
            });
            el.addEventListener('mouseleave', function () {
                gsap.to(el, { x: 0, y: 0, duration: 0.6, ease: 'elastic.out(1,0.4)' });
            });
        });
    }

    // ─── Tilt 3D ────────────────────────────────────────
    function initTilt() {
        if (prefersReduced || isTouch) return;
        document.querySelectorAll('[data-tilt]:not([data-tilt-init])').forEach(function (el) {
            el.setAttribute('data-tilt-init', '1');
            var max = parseFloat(el.getAttribute('data-tilt') || '8');
            el.style.transformStyle = 'preserve-3d';
            el.addEventListener('mousemove', function (e) {
                var r = el.getBoundingClientRect();
                var px = (e.clientX - r.left) / r.width;
                var py = (e.clientY - r.top) / r.height;
                var rx = (py - 0.5) * -max * 2;
                var ry = (px - 0.5) * max * 2;
                gsap.to(el, { rotateX: rx, rotateY: ry, duration: 0.3, ease: 'power2.out', transformPerspective: 800 });
            });
            el.addEventListener('mouseleave', function () {
                gsap.to(el, { rotateX: 0, rotateY: 0, duration: 0.6, ease: 'power3.out' });
            });
        });
    }

    // ─── Observer para nodos inyectados dinámicamente ───
    function watchDynamic() {
        var mo = new MutationObserver(function () { refresh(); });
        mo.observe(document.body, { childList: true, subtree: true });
    }

    function refresh() {
        initReveals();
        initCounters();
        initMagnetic();
        initTilt();
        if (window.ScrollTrigger) ScrollTrigger.refresh();
    }

    window.CAMotion = { refresh: refresh };

    // ─── Boot ───────────────────────────────────────────
    whenReady(function () {
        if (typeof gsap !== 'undefined') gsap.registerPlugin(ScrollTrigger);
        initLenis();
        refresh();
        watchDynamic();
    });
})();
