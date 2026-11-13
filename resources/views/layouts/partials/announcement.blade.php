{{-- Annonce active (admin › Annonces) : la plus récente, fermable, mémorisée par identifiant. --}}
@php($annonce = \App\Models\Annonce::current())
@if ($annonce)
    @php($annonceExterne = str_starts_with((string) $annonce['lien'], 'http') && ! str_starts_with((string) $annonce['lien'], url('/')))
    <div class="aad-annonce" id="aad-annonce" data-annonce-id="{{ $annonce['id'] }}" role="region" aria-label="Annonce">
        <p class="aad-annonce__text">
            @if ($annonce['lien'])
                <a href="{{ $annonce['lien'] }}" class="aad-annonce__link" @if ($annonceExterne) target="_blank" rel="noopener noreferrer" @endif>
                    {{ $annonce['texte'] }}
                    <span aria-hidden="true">→</span>
                    @if ($annonceExterne)
                        <span class="sr-only">(s’ouvre dans un nouvel onglet)</span>
                    @endif
                </a>
            @else
                {{ $annonce['texte'] }}
            @endif
        </p>
        <button type="button" class="aad-annonce__close" aria-label="Fermer l’annonce" data-annonce-close>
            <span aria-hidden="true">×</span>
        </button>
    </div>
    <script>
        (function () {
            var el = document.getElementById('aad-annonce');
            if (!el) return;
            var key = 'aad-annonce-fermee-' + el.getAttribute('data-annonce-id');
            try {
                if (localStorage.getItem(key)) { el.remove(); return; }
            } catch (e) {}
            el.querySelector('[data-annonce-close]').addEventListener('click', function () {
                try { localStorage.setItem(key, '1'); } catch (e) {}
                el.remove();
                var target = document.getElementById('contenu');
                if (target) { target.setAttribute('tabindex', '-1'); target.focus({ preventScroll: true }); }
            });
        })();
    </script>
@endif
