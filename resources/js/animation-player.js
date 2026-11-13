/**
 * Lecteur d'animations (boucles et clips) et cartes d'animation des grilles. Sans React.
 * Chargé par resources/views/animations/partials/assets.blade.php (entrée Vite), une fois par page.
 *
 * Règles (brief §13) :
 * - une boucle ne tourne que lorsqu'elle est à l'écran (IntersectionObserver), pause sinon et quand l'onglet est caché ;
 * - « réduire les animations » (prefers-reduced-motion) : jamais de lecture automatique, poster + bouton lecture ;
 * - un clip (avec son) ne démarre jamais tout seul ; un seul clip joue à la fois ; il se met en pause hors de l'écran ;
 * - raccourcis : Espace (ou K) lecture/pause, ← → ±5 s, M muet, F plein écran ;
 * - vue comptée (data-view-url) après ~3 s de lecture d'une boucle ou au lancement d'un clip.
 */

const VIEW_AFTER_SECONDS = 3;
const SEEK_STEP = 5;
const VISIBLE_RATIO = 0.25;

const motionQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
const reducedMotion = () => Boolean(motionQuery && motionQuery.matches);

const players = new Set();
const cards = new Set();

function formatTime(seconds) {
    const s = Math.max(0, Math.floor(Number.isFinite(seconds) ? seconds : 0));
    return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
}

function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

function fullscreenElement() {
    return document.fullscreenElement || document.webkitFullscreenElement || null;
}

/** Lance la lecture sans laisser remonter le refus du navigateur (autoplay bloqué, source absente…). */
function safePlay(video, onRefused) {
    try {
        const promise = video.play();
        if (promise && typeof promise.catch === 'function') {
            promise.catch(() => onRefused && onRefused());
        }
    } catch (e) {
        if (onRefused) onRefused();
    }
}

const observer = 'IntersectionObserver' in window
    ? new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            const target = entry.target.__aadAnimation;
            if (target) target.setVisible(entry.isIntersecting && entry.intersectionRatio >= VISIBLE_RATIO);
        });
    }, { threshold: [0, VISIBLE_RATIO, 0.6] })
    : null;

class Player {
    constructor(root) {
        this.root = root;
        this.video = root.querySelector('video');
        this.isLoop = root.dataset.mode === 'boucle';
        this.viewUrl = root.dataset.viewUrl || null;
        this.big = root.querySelector('[data-ap-big]');
        this.controls = root.querySelector('[data-ap-controls]');
        this.toggleBtn = root.querySelector('[data-ap-toggle]');
        this.muteBtn = root.querySelector('[data-ap-mute]');
        this.volume = root.querySelector('[data-ap-volume]');
        this.seek = root.querySelector('[data-ap-seek]');
        this.fsBtn = root.querySelector('[data-ap-fullscreen]');
        this.current = root.querySelector('[data-ap-current]');
        this.total = root.querySelector('[data-ap-total]');
        this.live = root.querySelector('[data-ap-live]');
        this.keys = root.querySelector('[data-ap-keys]');

        // Envie de lecture : une boucle veut tourner (sauf « réduire les animations »), un clip attend un clic.
        this.wantPlay = this.isLoop && !reducedMotion();
        this.touched = false; // l'internaute a lui-même lancé ou arrêté la lecture
        this.visible = !observer; // sans IntersectionObserver : considéré visible
        this.seeking = false;
        this.viewSent = false;
        this.watched = 0;
        this.lastTime = null;

        root.__aadAnimation = this;
        this.setup();
    }

    setup() {
        const v = this.video;
        if (!v) return;

        // Le script décide seul de la lecture : on retire l'autoplay natif (gardé pour les pages sans JS).
        v.autoplay = false;
        v.removeAttribute('autoplay');
        if (this.isLoop) {
            v.muted = true; // iOS : une vidéo ne démarre seule que muette
        } else {
            v.controls = false;
            v.removeAttribute('controls');
        }
        if (!this.wantPlay || !this.visible) v.pause();

        if (this.controls) this.controls.hidden = false;
        if (this.keys) this.keys.hidden = false;
        this.root.classList.add('is-enhanced');

        v.addEventListener('play', () => this.onPlay());
        v.addEventListener('pause', () => this.sync());
        v.addEventListener('ended', () => { this.wantPlay = false; this.sync(); });
        v.addEventListener('loadedmetadata', () => this.onMetadata());
        v.addEventListener('durationchange', () => this.onMetadata());
        v.addEventListener('timeupdate', () => this.onTime());
        v.addEventListener('volumechange', () => this.syncVolume());
        v.addEventListener('click', () => this.userToggle());
        v.addEventListener('dblclick', () => this.toggleFullscreen());

        if (this.big) this.big.addEventListener('click', () => this.userPlay());
        if (this.toggleBtn) this.toggleBtn.addEventListener('click', () => this.userToggle());
        if (this.muteBtn) this.muteBtn.addEventListener('click', () => this.toggleMute());
        if (this.fsBtn) this.fsBtn.addEventListener('click', () => this.toggleFullscreen());

        if (this.seek) {
            const start = () => { this.seeking = true; };
            const end = () => { this.seeking = false; };
            this.seek.addEventListener('pointerdown', start);
            this.seek.addEventListener('pointerup', end);
            this.seek.addEventListener('pointercancel', end);
            this.seek.addEventListener('change', end);
            this.seek.addEventListener('input', () => {
                const t = Number(this.seek.value);
                if (Number.isFinite(t)) v.currentTime = t;
                this.syncTime(t);
            });
        }

        if (this.volume) {
            // iOS : le volume est piloté par les boutons du téléphone (propriété en lecture seule) → curseur masqué.
            const before = v.volume;
            try { v.volume = before > 0.5 ? before - 0.01 : before + 0.01; } catch (e) { /* lecture seule */ }
            if (v.volume === before) {
                this.volume.hidden = true;
                const label = this.root.querySelector(`label[for="${this.volume.id}"]`);
                if (label) label.hidden = true;
            } else {
                v.volume = before;
            }
            this.volume.addEventListener('input', () => {
                const value = Number(this.volume.value);
                v.volume = value;
                v.muted = value === 0;
            });
        }

        this.root.addEventListener('keydown', (e) => this.onKey(e));

        if (observer) observer.observe(this.root);
        if (v.readyState >= 1) this.onMetadata();
        this.sync();
        this.syncVolume();
        this.apply();
    }

    // ---------- Politique de lecture ----------

    /** Applique l'état voulu : lecture si voulue, visible et onglet affiché ; pause sinon. */
    apply() {
        const v = this.video;
        if (!v) return;
        const shouldPlay = this.wantPlay && this.visible && !document.hidden;

        if (shouldPlay && v.paused) {
            safePlay(v, () => { this.wantPlay = false; this.sync(); });
        } else if (!shouldPlay && !v.paused) {
            v.pause();
        }
        this.sync();
    }

    setVisible(visible) {
        if (this.visible === visible) return;
        this.visible = visible;
        // Un clip qui sort de l'écran se met en pause et ne repart pas tout seul (il a du son).
        if (!visible && !this.isLoop) this.wantPlay = false;
        this.apply();
    }

    onMotionChange() {
        if (!this.isLoop || this.touched) return;
        this.wantPlay = !reducedMotion();
        this.apply();
    }

    userPlay() {
        this.touched = true;
        this.wantPlay = true;
        // Lancé par l'internaute : on joue même si le lecteur n'est qu'en partie à l'écran.
        this.visible = true;
        safePlay(this.video, () => { this.wantPlay = false; this.sync(); });
    }

    userToggle() {
        if (this.video.paused) {
            this.userPlay();
        } else {
            this.touched = true;
            this.wantPlay = false;
            this.video.pause();
        }
    }

    seekBy(delta) {
        const v = this.video;
        const duration = Number.isFinite(v.duration) ? v.duration : Number(this.root.dataset.duration || 0);
        v.currentTime = Math.min(Math.max(0, v.currentTime + delta), duration || v.currentTime + delta);
        this.syncTime(v.currentTime);
    }

    toggleMute() {
        const v = this.video;
        v.muted = !v.muted;
        if (!v.muted && v.volume === 0) v.volume = 1;
        this.announce(v.muted ? 'Son coupé' : 'Son activé');
    }

    toggleFullscreen() {
        const v = this.video;
        if (fullscreenElement()) {
            (document.exitFullscreen || document.webkitExitFullscreen).call(document);
            return;
        }
        const el = this.root;
        if (el.requestFullscreen) {
            const p = el.requestFullscreen();
            if (p && p.catch) p.catch(() => {});
        } else if (el.webkitRequestFullscreen) {
            el.webkitRequestFullscreen();
        } else if (v.webkitEnterFullscreen) {
            // iPhone : seul le lecteur natif de la vidéo peut passer en plein écran.
            v.webkitEnterFullscreen();
        }
    }

    // ---------- Événements ----------

    onPlay() {
        if (!this.isLoop) {
            // Un seul clip avec du son à la fois.
            players.forEach((other) => {
                if (other !== this && !other.isLoop && !other.video.paused) {
                    other.wantPlay = false;
                    other.video.pause();
                }
            });
            this.sendView();
        }
        this.lastTime = this.video.currentTime;
        this.sync();
    }

    onMetadata() {
        const d = this.video.duration;
        if (!Number.isFinite(d) || d <= 0) return;
        if (this.seek) this.seek.max = String(d);
        if (this.total) this.total.textContent = formatTime(d);
        this.syncTime(this.video.currentTime);
    }

    onTime() {
        const t = this.video.currentTime;
        if (!this.seeking) this.syncTime(t);

        // Temps réellement regardé (une boucle qui repart à 0 donne un écart négatif, ignoré).
        if (this.lastTime !== null && !this.video.paused) {
            const delta = t - this.lastTime;
            if (delta > 0 && delta < 1.5) this.watched += delta;
        }
        this.lastTime = t;
        if (this.isLoop && this.watched >= VIEW_AFTER_SECONDS) this.sendView();
    }

    onKey(e) {
        if (e.altKey || e.ctrlKey || e.metaKey) return;
        const target = e.target;
        const onRange = target instanceof HTMLInputElement && target.type === 'range';
        const onButton = target instanceof HTMLButtonElement;

        switch (e.key) {
            case ' ':
            case 'Spacebar':
                if (onButton) return; // le bouton s'active déjà lui-même
                e.preventDefault();
                this.userToggle();
                break;
            case 'k':
            case 'K':
                e.preventDefault();
                this.userToggle();
                break;
            case 'ArrowLeft':
            case 'ArrowRight':
                if (onRange) return; // le curseur gère ses flèches
                e.preventDefault();
                this.seekBy(e.key === 'ArrowLeft' ? -SEEK_STEP : SEEK_STEP);
                break;
            case 'm':
            case 'M':
                if (this.isLoop) return; // boucle sans piste son
                e.preventDefault();
                this.toggleMute();
                break;
            case 'f':
            case 'F':
                e.preventDefault();
                this.toggleFullscreen();
                break;
            default:
        }
    }

    // ---------- Affichage ----------

    sync() {
        const v = this.video;
        const playing = !v.paused && !v.ended;
        this.root.classList.toggle('is-playing', playing);
        if (this.big) this.big.hidden = playing;
        if (this.toggleBtn) {
            // Boucle : bouton « Mettre en pause » enfoncé quand elle est arrêtée ; clip : « Lecture » enfoncé pendant la lecture.
            this.toggleBtn.setAttribute('aria-pressed', String(this.isLoop ? !playing : playing));
        }
        if (this.fsBtn) this.fsBtn.setAttribute('aria-pressed', String(fullscreenElement() === this.root));
    }

    syncTime(t) {
        if (this.current) this.current.textContent = formatTime(t);
        if (!this.seek) return;
        if (!this.seeking) this.seek.value = String(t);
        const total = Number(this.seek.max) || 0;
        this.seek.setAttribute('aria-valuenow', String(Math.round(t)));
        this.seek.setAttribute('aria-valuetext', `${formatTime(t)} sur ${formatTime(total)}`);
    }

    syncVolume() {
        const v = this.video;
        if (this.muteBtn) this.muteBtn.setAttribute('aria-pressed', String(v.muted));
        if (this.volume) {
            const level = v.muted ? 0 : v.volume;
            this.volume.value = String(level);
            this.volume.setAttribute('aria-valuetext', `${Math.round(level * 100)} %`);
        }
    }

    announce(text) {
        if (this.live) this.live.textContent = text;
    }

    sendView() {
        if (!this.viewUrl || this.viewSent) return;
        this.viewSent = true;
        fetch(this.viewUrl, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
        }).catch(() => {});
    }
}

/**
 * Carte d'une grille : la boucle (muette) tourne quand la carte est à l'écran, pause sinon.
 * Avec « réduire les animations » : poster seul, lecture au survol ou au focus de la carte (geste volontaire).
 */
class Card {
    constructor(video) {
        this.video = video;
        this.visible = false;
        this.hovered = false;
        video.muted = true;
        video.__aadAnimation = this;

        const link = video.closest('a') || video.parentElement;
        const on = () => { this.hovered = true; this.apply(); };
        const off = () => { this.hovered = false; this.apply(); };
        link.addEventListener('mouseenter', on);
        link.addEventListener('mouseleave', off);
        link.addEventListener('focusin', on);
        link.addEventListener('focusout', off);

        if (observer) observer.observe(video);
        else this.visible = true;
        this.apply();
    }

    setVisible(visible) {
        this.visible = visible;
        this.apply();
    }

    apply() {
        const shouldPlay = this.visible && !document.hidden && (!reducedMotion() || this.hovered);
        if (shouldPlay && this.video.paused) {
            safePlay(this.video);
        } else if (!shouldPlay && !this.video.paused) {
            this.video.pause();
        }
        this.video.closest('[data-animation-card-frame]')?.classList.toggle('is-playing', shouldPlay);
    }
}

function scan(root = document) {
    root.querySelectorAll('[data-animation-player]').forEach((el) => {
        if (!el.__aadAnimation) players.add(new Player(el));
    });
    root.querySelectorAll('video[data-animation-card]').forEach((el) => {
        if (!el.__aadAnimation) cards.add(new Card(el));
    });
}

const refreshAll = () => {
    players.forEach((p) => p.apply());
    cards.forEach((c) => c.apply());
};

document.addEventListener('visibilitychange', refreshAll);
document.addEventListener('fullscreenchange', () => players.forEach((p) => p.sync()));
document.addEventListener('webkitfullscreenchange', () => players.forEach((p) => p.sync()));
if (motionQuery) {
    const onChange = () => {
        players.forEach((p) => p.onMotionChange());
        cards.forEach((c) => c.apply());
    };
    if (motionQuery.addEventListener) motionQuery.addEventListener('change', onChange);
    else if (motionQuery.addListener) motionQuery.addListener(onChange);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => scan());
} else {
    scan();
}

// Pour du contenu ajouté après coup (ex. aperçu dans la modération) : window.aadAnimations.scan(element).
window.aadAnimations = { scan };
