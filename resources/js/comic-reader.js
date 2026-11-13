/**
 * Lecteur de récits dessinés (/recits-dessines/{slug}/lire/{numero}).
 * Chargé uniquement sur cette page (@vite dans resources/views/comics/reader.blade.php). JavaScript sans dépendance,
 * plus Alpine pour les composants partagés de l'écran de fin (J'aime, Partager, Signaler).
 *
 * Sans JavaScript, la page reste lisible : planches empilées (mode vertical), images en chargement natif « lazy ».
 */
import Alpine from 'alpinejs';

if (!window.Alpine) {
    window.Alpine = Alpine;
    Alpine.start();
}

// ---------- Stockage (navigation privée, quotas : jamais d'erreur bloquante) ----------

const store = (area) => ({
    get(key, fallback = null) {
        try {
            const raw = window[area].getItem(key);
            return raw === null ? fallback : JSON.parse(raw);
        } catch (e) {
            return fallback;
        }
    },
    set(key, value) {
        try {
            window[area].setItem(key, JSON.stringify(value));
        } catch (e) {
            /* stockage indisponible : réglage non mémorisé */
        }
    },
});
const local = store('localStorage');
const session = store('sessionStorage');

const clamp = (n, min, max) => Math.min(max, Math.max(min, n));
const INTERACTIVE = 'a, button, input, select, textarea, label, summary, details, [contenteditable], [data-cr-end]';

const root = document.querySelector('[data-comic-reader]');
if (root) {
    initReader(root);
}

function initReader(root) {
    let cfg;
    try {
        cfg = JSON.parse(root.dataset.config || '{}');
    } catch (e) {
        return;
    }

    const body = document.body;
    const stage = root.querySelector('#cr-stage');
    const list = root.querySelector('[data-cr-pages]');
    const pages = Array.from(root.querySelectorAll('[data-cr-page]'));
    const total = pages.length;
    const endEl = root.querySelector('[data-cr-end]');
    const live = root.querySelector('[data-cr-live]');
    const counter = root.querySelector('[data-cr-current]');
    const progressBar = root.querySelector('[data-cr-progress]');
    const settings = root.querySelector('[data-cr-settings]');
    const settingsToggle = root.querySelector('[data-cr-settings-toggle]');
    const bars = Array.from(root.querySelectorAll('[data-cr-bar]'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const wideQuery = window.matchMedia('(min-width: 1100px) and (orientation: landscape)');

    // ---------- Réglages mémorisés ----------
    const PREFS_KEY = 'aad-bd-reglages';
    const MODE_KEY = 'aad-bd-mode-' + cfg.comic;
    const prefs = Object.assign({ gap: 0, fit: 'hauteur', double: true, cover: true }, local.get(PREFS_KEY, {}) || {});
    const savedMode = local.get(MODE_KEY, null);

    const state = {
        mode: savedMode === 'pages' || savedMode === 'vertical' ? savedMode : cfg.mode,
        page: 0, // index de la page courante (première page affichée)
        spreads: [],
        spread: 0,
        atEnd: false,
        zoom: 1,
        tx: 0,
        ty: 0,
        dirty: false,
        viewSent: false,
    };

    // Point de départ : ?page=, progression en base (serveur), sinon localStorage pour les visiteurs.
    let start = clamp((cfg.start || 1) - 1, 0, Math.max(0, total - 1));
    const params = new URLSearchParams(window.location.search);
    if (!cfg.auth && !params.has('page')) {
        const saved = (local.get('aad-bd-' + cfg.comic, {}) || {}).chapitres?.[cfg.chapter];
        if (saved && saved.pourcentage < 90 && saved.page > 1) {
            start = clamp(saved.page - 1, 0, Math.max(0, total - 1));
        }
    }
    state.page = start;

    // ---------- Chargement des images ----------
    const refreshPage = (li) => {
        const imgs = Array.from(li.querySelectorAll('img'));
        const failed = imgs.some((img) => img.dataset.state === 'error');
        const loaded = imgs.every((img) => img.dataset.state === 'ok');
        li.classList.toggle('is-error', failed);
        li.classList.toggle('is-loading', !failed && !loaded);
        const retry = li.querySelector('[data-cr-retry]');
        if (retry) retry.hidden = !failed;
    };

    pages.forEach((li) => {
        li.querySelectorAll('img').forEach((img) => {
            img.addEventListener('load', () => {
                img.dataset.state = 'ok';
                refreshPage(li);
            });
            img.addEventListener('error', () => {
                img.dataset.state = 'error';
                refreshPage(li);
            });
            if (img.complete) {
                img.dataset.state = img.naturalWidth ? 'ok' : (img.getAttribute('loading') === 'lazy' ? '' : 'error');
            }
        });
        refreshPage(li);

        li.querySelector('[data-cr-retry]')?.addEventListener('click', (e) => {
            e.stopPropagation();
            li.querySelectorAll('img').forEach((img) => {
                if (img.dataset.state !== 'error') return;
                img.dataset.state = '';
                img.loading = 'eager';
                const url = new URL(img.src, window.location.href);
                url.searchParams.set('r', Date.now().toString(36));
                img.src = url.toString();
            });
            refreshPage(li);
        });
    });

    // Fait démarrer le téléchargement d'une page (les images au-delà des 3 premières sont en « lazy »).
    const warm = (index) => {
        const li = pages[index];
        if (!li) return;
        li.querySelectorAll('img[loading="lazy"]').forEach((img) => {
            img.loading = 'eager';
        });
    };

    // Mode vertical : préchargement des pages proches de l'écran (marge de 1 500 px).
    const nearObserver = 'IntersectionObserver' in window
        ? new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting && state.mode === 'vertical') {
                    warm(pages.indexOf(entry.target));
                    nearObserver.unobserve(entry.target);
                }
            });
        }, { rootMargin: '1500px 0px' })
        : null;

    // Mode vertical : page courante = celle qui croise une ligne à 40 % de la hauteur de l'écran.
    const currentObserver = 'IntersectionObserver' in window
        ? new IntersectionObserver((entries) => {
            if (state.mode !== 'vertical') return;
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                if (entry.target === endEl) {
                    setAtEnd(true);
                } else {
                    setAtEnd(false);
                    setPage(pages.indexOf(entry.target));
                }
            });
        }, { rootMargin: '-40% 0px -60% 0px' })
        : null;

    // ---------- Page courante, annonces, progression ----------
    let announceTimer = null;

    const currentNumbers = () => {
        if (state.mode === 'pages' && state.spreads[state.spread]) {
            return state.spreads[state.spread].map((i) => i + 1);
        }
        return [state.page + 1];
    };

    const percent = () => {
        if (!total) return 0;
        if (state.atEnd) return 100;
        const numbers = currentNumbers();
        return clamp(Math.round((numbers[numbers.length - 1] / total) * 100), 0, 100);
    };

    const announce = () => {
        clearTimeout(announceTimer);
        announceTimer = setTimeout(() => {
            if (!live) return;
            const n = currentNumbers();
            live.textContent = state.atEnd
                ? 'Fin du chapitre'
                : (n.length > 1 ? 'Pages ' + n[0] + ' et ' + n[1] + ' sur ' + total : 'Page ' + n[0] + ' sur ' + total);
        }, state.mode === 'vertical' ? 400 : 50);
    };

    const updateIndicators = () => {
        const n = currentNumbers();
        if (counter) counter.textContent = n.length > 1 ? n[0] + '–' + n[1] : String(n[0] || 0);
        if (state.mode === 'pages' || !list) {
            setBar(percent());
        }
        announce();
        state.dirty = true;
        maybeCountView();
    };

    const setBar = (pct) => {
        if (progressBar) progressBar.style.transform = 'scaleX(' + clamp(pct, 0, 100) / 100 + ')';
    };

    const setPage = (index) => {
        const i = clamp(index, 0, Math.max(0, total - 1));
        if (i === state.page) return;
        state.page = i;
        updateIndicators();
    };

    const setAtEnd = (value) => {
        if (state.atEnd === value) return;
        state.atEnd = value;
        root.classList.toggle('is-at-end', value);
        if (value) showBars(false);
        updateIndicators();
    };

    // Barre de progression continue en mode vertical.
    let scrollTicking = false;
    let lastScrollY = window.scrollY;
    let lastScrollAt = 0;
    const onScroll = () => {
        lastScrollAt = Date.now();
        if (scrollTicking) return;
        scrollTicking = true;
        window.requestAnimationFrame(() => {
            scrollTicking = false;
            const y = window.scrollY;
            if (state.mode === 'vertical' && list) {
                const rect = list.getBoundingClientRect();
                const read = (window.innerHeight - rect.top) / Math.max(1, rect.height);
                setBar(state.atEnd ? 100 : read * 100);
            }
            // Barres : masquées en descendant, réaffichées en remontant légèrement.
            if (y < lastScrollY - 8) showBars();
            else if (y > lastScrollY + 8 && y > 60) hideBars();
            lastScrollY = y;
        });
    };
    window.addEventListener('scroll', onScroll, { passive: true });

    // ---------- Barres haut / bas ----------
    let barsTimer = null;
    const settingsOpen = () => settings && !settings.hidden;
    // Seul un focus clavier garde les barres affichées : après un clic sur un bouton, le focus reste
    // dans la barre et l'empêchait de disparaître pendant le défilement.
    const keyboardFocus = (el) => {
        try { return el.matches(':focus-visible'); } catch (e) { return true; }
    };
    const barHasFocus = () => bars.some((bar) => bar.contains(document.activeElement) && keyboardFocus(document.activeElement));

    function showBars(autoHide = true) {
        root.classList.remove('bars-hidden');
        clearTimeout(barsTimer);
        if (autoHide) barsTimer = setTimeout(hideBars, state.mode === 'vertical' ? 2000 : 3500);
    }

    function hideBars() {
        if (settingsOpen() || barHasFocus() || state.atEnd || !total) return;
        clearTimeout(barsTimer);
        root.classList.add('bars-hidden');
    }

    const toggleBars = () => (root.classList.contains('bars-hidden') ? showBars() : hideBars());

    bars.forEach((bar) => {
        bar.addEventListener('focusin', () => showBars(false));
        bar.addEventListener('focusout', () => showBars());
    });

    let lastMouse = 0;
    root.addEventListener('pointermove', (e) => {
        if (e.pointerType !== 'mouse') return;
        const now = Date.now();
        // Le défilement à la molette peut produire des « mouvements » de souris immobile : on les ignore.
        if (now - lastScrollAt < 400 || Math.abs(e.movementX || 0) + Math.abs(e.movementY || 0) < 3) return;
        if (now - lastMouse < 200) return;
        lastMouse = now;
        showBars();
    });

    // ---------- Mode vertical ----------
    const scrollToPage = (index, smooth = true) => {
        const li = pages[clamp(index, 0, total - 1)];
        if (!li) return;
        li.scrollIntoView({ block: 'start', behavior: smooth && !reducedMotion.matches ? 'smooth' : 'auto' });
    };

    // ---------- Mode page par page ----------
    const computeSpreads = () => {
        const spreads = [];
        const useDouble = prefs.double && wideQuery.matches;
        let i = 0;
        if (!useDouble) {
            pages.forEach((_, k) => spreads.push([k]));
            return spreads;
        }
        if (prefs.cover && total) {
            spreads.push([0]);
            i = 1;
        }
        while (i < total) {
            const a = pages[i];
            const b = pages[i + 1];
            // Une page « double page » (panoramique) s'affiche toujours seule.
            if (a.dataset.double === '1' || !b || b.dataset.double === '1' || a.classList.contains('is-tall') || b.classList.contains('is-tall')) {
                spreads.push([i]);
                i += 1;
            } else {
                spreads.push([i, i + 1]);
                i += 2;
            }
        }
        return spreads;
    };

    const spreadOf = (index) => Math.max(0, state.spreads.findIndex((s) => s.includes(index)));

    const showSpread = (k, focusEnd = false) => {
        state.spread = clamp(k, 0, state.spreads.length);
        const end = state.spread >= state.spreads.length;
        const shown = end ? [] : state.spreads[state.spread];

        pages.forEach((li, i) => {
            li.classList.toggle('is-shown', shown.includes(i));
        });
        if (list) list.dataset.pair = shown.length > 1 ? '1' : '';
        endEl?.classList.toggle('is-shown', end);
        resetZoom();

        if (!end) {
            state.page = shown[0];
            // Préchargement des 3 pages suivantes.
            const last = shown[shown.length - 1];
            for (let i = 1; i <= 3; i += 1) warm(last + i);
            shown.forEach(warm);
        }
        state.atEnd = end;
        root.classList.toggle('is-at-end', end);
        window.scrollTo(0, 0);
        if (end) {
            showBars(false);
            if (focusEnd) endEl?.focus({ preventScroll: true });
        }
        updateIndicators();
    };

    // ---------- Navigation commune ----------
    const next = () => {
        if (state.mode === 'pages') {
            if (state.atEnd) {
                if (cfg.nextUrl) window.location.href = cfg.nextUrl;
                return;
            }
            showSpread(state.spread + 1, true);
        } else if (state.page >= total - 1) {
            endEl?.scrollIntoView({ block: 'start', behavior: reducedMotion.matches ? 'auto' : 'smooth' });
        } else {
            scrollToPage(state.page + 1);
        }
        startedReading();
    };

    const prev = () => {
        if (state.mode === 'pages') {
            showSpread(state.spread - 1);
        } else if (state.atEnd) {
            scrollToPage(total - 1);
        } else {
            scrollToPage(state.page - 1);
        }
    };

    const first = () => (state.mode === 'pages' ? showSpread(0) : scrollToPage(0));
    const last = () => (state.mode === 'pages' ? showSpread(state.spreads.length - 1) : scrollToPage(total - 1));

    // En sens manga, la gauche avance et la droite recule (mode page par page uniquement).
    const isRtl = () => cfg.dir === 'rtl' && state.mode === 'pages';
    const goLeft = () => (isRtl() ? next() : prev());
    const goRight = () => (isRtl() ? prev() : next());

    root.querySelector('[data-cr-next]')?.addEventListener('click', () => next());
    root.querySelector('[data-cr-prev]')?.addEventListener('click', () => prev());

    let reading = false;
    function startedReading() {
        if (reading) return;
        reading = true;
        hideBars();
    }

    // ---------- Zoom ----------
    const MAX_ZOOM = 4;

    const applyZoom = () => {
        const zoomed = state.zoom > 1.01;
        root.dataset.zoomed = zoomed ? '1' : '';
        if (state.mode === 'pages') {
            list.style.transform = zoomed ? 'translate(' + state.tx + 'px, ' + state.ty + 'px) scale(' + state.zoom + ')' : '';
        } else {
            root.style.setProperty('--cr-vzoom', String(state.zoom));
        }
    };

    function resetZoom() {
        state.zoom = 1;
        state.tx = 0;
        state.ty = 0;
        if (list) list.style.transform = '';
        root.style.removeProperty('--cr-vzoom');
        root.dataset.zoomed = '';
    }

    const clampPan = () => {
        const w = list.offsetWidth;
        const h = list.offsetHeight;
        state.tx = clamp(state.tx, w - w * state.zoom, 0);
        state.ty = clamp(state.ty, h - h * state.zoom, 0);
    };

    // Zoom autour d'un point de l'écran (origine de transformation : coin haut gauche de la liste).
    const zoomAt = (zoom, clientX, clientY) => {
        zoom = clamp(zoom, 1, MAX_ZOOM);
        if (!list) return;

        if (state.mode === 'pages') {
            const rect = list.getBoundingClientRect();
            const baseLeft = rect.left - state.tx;
            const baseTop = rect.top - state.ty;
            const lx = (clientX - baseLeft - state.tx) / state.zoom;
            const ly = (clientY - baseTop - state.ty) / state.zoom;
            state.zoom = zoom;
            state.tx = clientX - baseLeft - lx * zoom;
            state.ty = clientY - baseTop - ly * zoom;
            if (zoom <= 1.01) resetZoom();
            else {
                clampPan();
                applyZoom();
            }
            return;
        }

        // Vertical : la colonne s'élargit, le déplacement se fait par le défilement natif.
        const before = list.getBoundingClientRect();
        const rx = (clientX - before.left) / Math.max(1, before.width);
        const ry = (clientY - before.top) / Math.max(1, before.height);
        state.zoom = zoom;
        if (zoom <= 1.01) resetZoom();
        else applyZoom();
        const after = list.getBoundingClientRect();
        window.scrollBy(0, after.top + ry * after.height - clientY);
        stage.scrollLeft += after.left + rx * after.width - clientX;
    };

    const toggleZoom = (x, y) => zoomAt(state.zoom > 1.01 ? 1 : 2.5, x, y);

    // Ctrl + molette (et pincement du pavé tactile) : seulement en mode page par page ; en vertical,
    // le zoom natif du navigateur reste disponible.
    stage.addEventListener('wheel', (e) => {
        if (!e.ctrlKey || state.mode !== 'pages') return;
        e.preventDefault();
        zoomAt(state.zoom * Math.exp(-e.deltaY * 0.01), e.clientX, e.clientY);
    }, { passive: false });

    // ---------- Gestes : clic, double clic, glissement, pincement, déplacement ----------
    let tapTimer = null;
    let lastTap = 0;
    let suppressClick = false;
    // Après un glissement ou un déplacement, le « clic » qui suit éventuellement est ignoré.
    const suppress = () => {
        suppressClick = true;
        setTimeout(() => {
            suppressClick = false;
        }, 400);
    };

    stage.addEventListener('click', (e) => {
        if (e.target.closest(INTERACTIVE)) return;
        if (suppressClick) {
            suppressClick = false;
            return;
        }
        const now = Date.now();
        if (now - lastTap < 300) {
            clearTimeout(tapTimer);
            lastTap = 0;
            toggleZoom(e.clientX, e.clientY);
            return;
        }
        lastTap = now;
        const ratio = e.clientX / window.innerWidth;
        tapTimer = setTimeout(() => {
            if (state.mode !== 'pages' || state.zoom > 1.01) {
                toggleBars();
            } else if (ratio < 0.4) {
                goLeft();
            } else if (ratio > 0.6) {
                goRight();
            } else {
                toggleBars();
            }
        }, 260);
    });

    const pointers = new Map();
    let gesture = null;

    stage.addEventListener('pointerdown', (e) => {
        if (state.mode !== 'pages' || e.target.closest(INTERACTIVE)) return;
        if (e.pointerType === 'mouse' && (e.button !== 0 || state.zoom <= 1.01)) return;
        pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
        const pts = Array.from(pointers.values());
        if (pts.length === 2) {
            gesture = {
                type: 'pinch',
                dist: Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y),
                zoom: state.zoom,
            };
        } else if (pts.length === 1) {
            gesture = { type: state.zoom > 1.01 ? 'pan' : 'swipe', x: e.clientX, y: e.clientY, tx: state.tx, ty: state.ty, t: Date.now(), moved: false };
            if (gesture.type === 'pan') stage.setPointerCapture?.(e.pointerId);
        }
    });

    stage.addEventListener('pointermove', (e) => {
        if (!pointers.has(e.pointerId) || !gesture) return;
        pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
        const pts = Array.from(pointers.values());

        if (gesture.type === 'pinch' && pts.length === 2) {
            const dist = Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y);
            const cx = (pts[0].x + pts[1].x) / 2;
            const cy = (pts[0].y + pts[1].y) / 2;
            zoomAt(gesture.zoom * (dist / Math.max(1, gesture.dist)), cx, cy);
            suppress();
        } else if (gesture.type === 'pan') {
            const dx = e.clientX - gesture.x;
            const dy = e.clientY - gesture.y;
            if (Math.abs(dx) + Math.abs(dy) > 4) gesture.moved = true;
            state.tx = gesture.tx + dx;
            state.ty = gesture.ty + dy;
            clampPan();
            applyZoom();
        }
    });

    const endPointer = (e) => {
        if (!pointers.has(e.pointerId)) return;
        pointers.delete(e.pointerId);
        if (!gesture) return;

        if (gesture.type === 'swipe' && e.type === 'pointerup') {
            const dx = e.clientX - gesture.x;
            const dy = e.clientY - gesture.y;
            if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.5 && Date.now() - gesture.t < 800) {
                suppress();
                // Doigt vers la gauche = on « tourne » vers la droite de l'écran.
                if (dx < 0) goRight();
                else goLeft();
            }
        } else if (gesture.type === 'pan' && gesture.moved) {
            suppress();
        }

        if (pointers.size === 0) {
            gesture = null;
            if (state.zoom <= 1.01) resetZoom();
        } else if (pointers.size === 1 && gesture.type === 'pinch') {
            const [p] = Array.from(pointers.values());
            gesture = { type: 'pan', x: p.x, y: p.y, tx: state.tx, ty: state.ty, t: Date.now(), moved: true };
        }
    };
    stage.addEventListener('pointerup', endPointer);
    stage.addEventListener('pointercancel', endPointer);

    // ---------- Clavier ----------
    const atScrollBottom = () => window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 4;
    const atScrollTop = () => window.scrollY <= 4;

    document.addEventListener('keydown', (e) => {
        if (e.defaultPrevented || e.altKey || e.ctrlKey || e.metaKey) return;
        const target = e.target instanceof Element ? e.target : null;

        if (e.key === 'Escape') {
            if (settingsOpen()) {
                closeSettings();
                e.preventDefault();
            } else if (state.zoom > 1.01) {
                resetZoom();
            }
            return;
        }
        if (root.querySelector('[data-cr-warning]') || settingsOpen()) return;
        if (target && target.closest('input, select, textarea, [contenteditable]')) return;
        // Espace / Entrée sur un bouton ou un lien : comportement normal.
        if (target && target.closest('a, button, summary') && (e.key === ' ' || e.key === 'Enter')) return;
        if (target && target.closest('[data-cr-end]') && e.key !== 'f' && e.key !== 'F' && state.mode === 'vertical') return;

        let handled = true;
        switch (e.key) {
            case 'f':
            case 'F':
                toggleFullscreen();
                break;
            case 'ArrowRight':
                goRight();
                break;
            case 'ArrowLeft':
                goLeft();
                break;
            case ' ':
            case 'Spacebar':
            case 'PageDown':
            case 'PageUp': {
                const back = e.key === 'PageUp' || e.shiftKey && e.key !== 'PageDown';
                if (state.mode !== 'pages') {
                    handled = false; // défilement natif
                } else if (back) {
                    if (atScrollTop()) prev();
                    else handled = false;
                } else if (atScrollBottom()) {
                    next();
                } else {
                    handled = false;
                }
                break;
            }
            case 'Home':
                if (state.mode === 'pages') first();
                else handled = false;
                break;
            case 'End':
                if (state.mode === 'pages') last();
                else handled = false;
                break;
            default:
                handled = false;
        }
        if (handled) e.preventDefault();
    });

    // ---------- Plein écran ----------
    const fsButton = root.querySelector('[data-cr-fullscreen]');
    const fsElement = () => document.fullscreenElement || document.webkitFullscreenElement;
    const canFullscreen = !!(document.fullscreenEnabled || document.webkitFullscreenEnabled);
    if (fsButton && canFullscreen) {
        fsButton.hidden = false;
        fsButton.addEventListener('click', () => toggleFullscreen());
    }

    function toggleFullscreen() {
        if (!canFullscreen) return;
        if (fsElement()) {
            (document.exitFullscreen || document.webkitExitFullscreen).call(document);
        } else {
            const el = document.documentElement;
            const request = el.requestFullscreen || el.webkitRequestFullscreen;
            const result = request?.call(el);
            if (result && result.catch) result.catch(() => {});
        }
    }
    const onFullscreen = () => fsButton?.setAttribute('aria-pressed', fsElement() ? 'true' : 'false');
    document.addEventListener('fullscreenchange', onFullscreen);
    document.addEventListener('webkitfullscreenchange', onFullscreen);

    // ---------- Menu ⚙ ----------
    function openSettings() {
        settings.hidden = false;
        settingsToggle?.setAttribute('aria-expanded', 'true');
        showBars(false);
        settings.querySelector('input:checked, input')?.focus();
    }

    function closeSettings() {
        settings.hidden = true;
        settingsToggle?.setAttribute('aria-expanded', 'false');
        settingsToggle?.focus();
        showBars();
    }

    settingsToggle?.addEventListener('click', () => (settingsOpen() ? closeSettings() : openSettings()));
    root.querySelector('[data-cr-settings-close]')?.addEventListener('click', closeSettings);
    document.addEventListener('click', (e) => {
        if (settingsOpen() && !settings.contains(e.target) && !settingsToggle.contains(e.target)) closeSettings();
    });

    const syncSettingsForm = () => {
        if (!settings) return;
        const check = (name, value) => {
            settings.querySelectorAll('input[name="' + name + '"]').forEach((input) => {
                input.checked = input.type === 'checkbox' ? !!value : input.value === String(value);
            });
        };
        check('cr-mode', state.mode);
        check('cr-gap', prefs.gap);
        check('cr-fit', prefs.fit);
        check('cr-double', prefs.double);
        check('cr-cover', prefs.cover);
        check('cr-theme', body.dataset.mode);
        settings.querySelectorAll('[data-cr-only]').forEach((fieldset) => {
            fieldset.hidden = fieldset.dataset.crOnly !== state.mode;
        });
    };

    settings?.addEventListener('change', (e) => {
        const input = e.target;
        switch (input.name) {
            case 'cr-mode':
                setMode(input.value);
                local.set(MODE_KEY, state.mode);
                break;
            case 'cr-gap':
                prefs.gap = [0, 8, 24].includes(Number(input.value)) ? Number(input.value) : 0;
                break;
            case 'cr-fit':
                prefs.fit = input.value === 'largeur' ? 'largeur' : 'hauteur';
                break;
            case 'cr-double':
                prefs.double = input.checked;
                break;
            case 'cr-cover':
                prefs.cover = input.checked;
                break;
            case 'cr-theme':
                body.dataset.mode = input.value === 'jour' ? 'jour' : 'nuit';
                try {
                    localStorage.setItem('aad-mode-neo', body.dataset.mode);
                } catch (err) {
                    /* non mémorisé */
                }
                break;
            default:
                return;
        }
        local.set(PREFS_KEY, prefs);
        applyPrefs();
        syncSettingsForm();
    });

    function applyPrefs() {
        root.style.setProperty('--cr-gap', prefs.gap + 'px');
        root.dataset.fit = prefs.fit;
        if (state.mode === 'pages') {
            const keep = state.atEnd ? null : state.page;
            state.spreads = computeSpreads();
            showSpread(keep === null ? state.spreads.length : spreadOf(keep));
        }
    }

    // ---------- Changement de mode ----------
    function setMode(mode) {
        const keep = state.page;
        const wasEnd = state.atEnd;
        resetZoom();
        state.mode = mode === 'pages' ? 'pages' : 'vertical';
        root.dataset.mode = state.mode;

        if (state.mode === 'pages') {
            pages.forEach((li) => li.classList.remove('is-shown'));
            state.spreads = computeSpreads();
            showSpread(wasEnd ? state.spreads.length : spreadOf(keep));
            showRtlHint();
        } else {
            pages.forEach((li) => li.classList.remove('is-shown'));
            endEl?.classList.remove('is-shown');
            if (list) list.dataset.pair = '';
            state.atEnd = false;
            root.classList.remove('is-at-end');
            for (let i = Math.max(0, keep - 1); i <= keep + 2; i += 1) warm(i);
            window.requestAnimationFrame(() => {
                if (keep > 0) scrollToPage(keep, false);
                else window.scrollTo(0, 0);
            });
            updateIndicators();
        }
    }

    // Écran large ou rotation : la double page apparaît ou disparaît.
    const onWide = () => {
        if (state.mode !== 'pages') return;
        const keep = state.atEnd ? null : state.page;
        state.spreads = computeSpreads();
        showSpread(keep === null ? state.spreads.length : spreadOf(keep));
    };
    if (wideQuery.addEventListener) wideQuery.addEventListener('change', onWide);
    else if (wideQuery.addListener) wideQuery.addListener(onWide);

    // ---------- Rappel « Lecture de droite à gauche » (première ouverture) ----------
    const hint = root.querySelector('[data-cr-rtl-hint]');
    let hintTimer = null;
    function showRtlHint() {
        if (!hint || cfg.dir !== 'rtl' || state.mode !== 'pages') return;
        const key = 'aad-bd-rtl-' + cfg.comic;
        if (local.get(key, false)) return;
        local.set(key, true);
        hint.hidden = false;
        hintTimer = setTimeout(() => {
            hint.hidden = true;
        }, 6000);
    }
    root.querySelector('[data-cr-hint-close]')?.addEventListener('click', () => {
        clearTimeout(hintTimer);
        hint.hidden = true;
    });

    // ---------- Avertissement « contenu sensible » ----------
    const warning = root.querySelector('[data-cr-warning]');
    const WARNING_KEY = 'aad-bd-sensible-' + cfg.comic;
    const others = [stage, ...bars];
    if (warning) {
        if (session.get(WARNING_KEY, false)) {
            warning.remove();
        } else {
            others.forEach((el) => el && (el.inert = true));
            const accept = warning.querySelector('[data-cr-warning-accept]');
            accept?.focus();
            accept?.addEventListener('click', (e) => {
                e.preventDefault();
                session.set(WARNING_KEY, true);
                others.forEach((el) => el && (el.inert = false));
                warning.remove();
                stage.focus({ preventScroll: true });
            });
        }
    }

    // ---------- Progression : toutes les 5 s et à la fermeture ----------
    const snapshot = () => {
        const numbers = currentNumbers();
        return { chapitre: cfg.chapter, page: clamp(numbers[0] || 1, 1, Math.max(1, total)), pourcentage: percent() };
    };

    const saveLocal = (snap) => {
        const key = 'aad-bd-' + cfg.comic;
        const data = local.get(key, {}) || {};
        data.chapitres = data.chapitres || {};
        // Pourcentage = le plus loin jamais atteint (un chapitre terminé reste « Lu » quand on le relit).
        const before = data.chapitres[cfg.chapter] || {};
        const best = Math.max(Number(before.pourcentage) || 0, snap.pourcentage);
        data.chapitres[cfg.chapter] = { page: snap.page, pourcentage: best, n: cfg.chapterNumber, t: Date.now() };
        data.dernier = cfg.chapter;
        local.set(key, data);
    };

    const send = (snap) => {
        if (!cfg.progressUrl || !total) return;
        fetch(cfg.progressUrl, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(snap),
        }).catch(() => {});
    };

    // Une vue compte à partir de 3 pages ou de 20 % (le serveur dédoublonne par visiteur et par jour).
    function maybeCountView() {
        if (state.viewSent || cfg.auth || !total) return;
        const snap = snapshot();
        if (snap.page >= cfg.viewPages || snap.pourcentage >= cfg.viewPercent) {
            state.viewSent = true;
            send(snap);
        }
    }

    const flush = (beacon = false) => {
        if (!state.dirty || !total) return;
        state.dirty = false;
        const snap = snapshot();
        saveLocal(snap); // reprise rapide, même connecté
        if (!cfg.auth) return;
        if (beacon && navigator.sendBeacon) {
            // FormData + _token : vérifié par le middleware CSRF comme un formulaire classique.
            const form = new FormData();
            form.append('_token', csrf);
            Object.entries(snap).forEach(([k, v]) => form.append(k, String(v)));
            if (navigator.sendBeacon(cfg.progressUrl, form)) return;
        }
        send(snap);
    };

    setInterval(() => flush(false), 5000);
    window.addEventListener('pagehide', () => flush(true));
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') flush(true);
    });

    // ---------- Démarrage ----------
    root.style.setProperty('--cr-gap', prefs.gap + 'px');
    root.dataset.fit = prefs.fit;
    root.dataset.mode = state.mode;
    root.classList.add('is-ready');
    syncSettingsForm();

    if (state.mode === 'pages') {
        state.spreads = computeSpreads();
        showSpread(spreadOf(start));
        showRtlHint();
    } else {
        if (start > 0) {
            for (let i = Math.max(0, start - 1); i <= start + 2; i += 1) warm(i);
            window.requestAnimationFrame(() => scrollToPage(start, false));
        }
        updateIndicators();
    }
    state.dirty = false;

    pages.forEach((li) => {
        nearObserver?.observe(li);
        currentObserver?.observe(li);
    });
    if (endEl) currentObserver?.observe(endEl);

    onScroll();
    showBars();
}

// ---------- Cases animées (pages vidéo d'un chapitre) ----------
// Lecture seulement quand la page est à l'écran (mode vertical) ou affichée (mode page par page : les pages
// masquées ne croisent jamais l'écran), pause sinon. « Réduire les animations » : poster + bouton lecture.
function initAnimatedPanels() {
    const videos = Array.from(document.querySelectorAll('[data-cr-video]'));
    if (!videos.length) return;

    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    const visible = new Set();
    const chosen = new Set(); // lues à la demande quand les animations sont réduites

    const sync = (video) => {
        const wanted = visible.has(video) && (!reduced.matches || chosen.has(video));
        if (wanted && video.paused) {
            video.play().catch(() => {});
        } else if (!wanted && !video.paused) {
            video.pause();
        }
        const button = video.parentElement.querySelector('[data-cr-video-toggle]');
        if (button) {
            button.hidden = !reduced.matches;
            button.setAttribute('aria-pressed', String(!video.paused || chosen.has(video)));
            const label = button.querySelector('[data-cr-video-label]');
            if (label) label.textContent = chosen.has(video) ? 'Arrêter l’animation' : 'Lire l’animation';
        }
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            entry.isIntersecting && entry.intersectionRatio >= 0.35 ? visible.add(entry.target) : visible.delete(entry.target);
            sync(entry.target);
        });
    }, { threshold: [0, 0.35, 0.7] });

    videos.forEach((video) => {
        observer.observe(video);
        video.parentElement.querySelector('[data-cr-video-toggle]')?.addEventListener('click', (event) => {
            event.stopPropagation();
            chosen.has(video) ? chosen.delete(video) : chosen.add(video);
            sync(video);
        });
        sync(video);
    });

    reduced.addEventListener?.('change', () => videos.forEach(sync));
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) videos.forEach((video) => video.pause());
        else videos.forEach(sync);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAnimatedPanels);
} else {
    initAnimatedPanels();
}
