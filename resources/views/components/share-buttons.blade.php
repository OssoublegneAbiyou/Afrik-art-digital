@props([
    'title',
    'url' => null,
    'label' => 'Partager',
])

@php
    $shareUrl = $url ?? url()->current();
    $shareText = $title.' — '.$shareUrl;
@endphp

{{-- Partage : WhatsApp, Facebook, copie du lien et partage natif du téléphone. Les aperçus viennent des balises Open Graph. --}}
<div {{ $attributes->class('share') }}
    x-data="{
        copied: false,
        canShare: typeof navigator !== 'undefined' && !! navigator.share,
        copy() {
            const url = @js($shareUrl);
            const done = () => { this.copied = true; setTimeout(() => this.copied = false, 2500); };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(done, () => this.fallback(url, done));
            } else {
                this.fallback(url, done);
            }
        },
        fallback(url, done) {
            const field = document.createElement('textarea');
            field.value = url; field.setAttribute('readonly', ''); field.style.position = 'fixed'; field.style.opacity = '0';
            document.body.appendChild(field); field.select();
            try { document.execCommand('copy'); done(); } catch (e) { window.prompt('Copie ce lien :', url); }
            field.remove();
        },
        native() { navigator.share({ title: @js($title), url: @js($shareUrl) }).catch(() => {}); },
    }">
    <span class="share__label">{{ $label }}</span>
    <div class="share__buttons">
        <a class="share__btn share__btn--whatsapp" href="https://wa.me/?text={{ rawurlencode($shareText) }}" target="_blank" rel="noopener noreferrer"
            aria-label="Partager sur WhatsApp (nouvelle fenêtre)">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.6-1.3a.4.4 0 0 0 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 2.9 2.9 0 0 0-.9 2.2 5.1 5.1 0 0 0 1.1 2.7 11.6 11.6 0 0 0 4.4 3.9c1.6.7 2.3.8 3.1.6a2.6 2.6 0 0 0 1.7-1.2 2.1 2.1 0 0 0 .2-1.2c-.1-.1-.3-.2-.5-.3Z"/></svg>
            <span>WhatsApp</span>
        </a>
        <a class="share__btn share__btn--facebook" href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($shareUrl) }}" target="_blank" rel="noopener noreferrer"
            aria-label="Partager sur Facebook (nouvelle fenêtre)">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M13.5 21v-7.5H16l.4-3h-2.9V8.6c0-.9.3-1.5 1.5-1.5h1.5V4.4a20 20 0 0 0-2.2-.1c-2.2 0-3.7 1.3-3.7 3.8v2.4H8.1v3h2.5V21h2.9Z"/></svg>
            <span>Facebook</span>
        </a>
        <button type="button" class="share__btn share__btn--copy" x-on:click="copy()">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></svg>
            <span x-text="copied ? 'Lien copié !' : 'Copier le lien'">Copier le lien</span>
        </button>
        <button type="button" class="share__btn share__btn--native" x-show="canShare" x-cloak x-on:click="native()">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 3v12M7 8l5-5 5 5M5 13v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6"/></svg>
            <span>Autres applis</span>
        </button>
    </div>
    <span class="sr-only" role="status" aria-live="polite" x-text="copied ? 'Lien copié dans le presse-papiers' : ''"></span>
</div>
