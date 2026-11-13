@php
    // Réglages du collectif (admin › Réglages) : nom, ville, e-mail public, réseaux.
    $footerEmail = $collectif['contact']['email'] ?? null;
    $footerHasContact = collect($collectif['contact'] ?? [])->except('whatsapp')->filter()->isNotEmpty() || count($collectif['reseaux'] ?? []);
@endphp

<footer class="w-footer">
    <div class="w-container w-footer__inner">
        <p>© {{ date('Y') }} {{ $collectif['nom'].(filled($collectif['ville'] ?? null) ? ' — '.$collectif['ville'] : '') }}</p>
        <nav class="w-footer__links" aria-label="Pied de page">
            <a href="{{ route('public.about') }}">À propos</a>
            @if ($footerHasContact)
                <a href="{{ route('public.about') }}#contact">Contact</a>
            @endif
            @if ($footerEmail)
                <a href="mailto:{{ $footerEmail }}">{{ $footerEmail }}</a>
            @endif
            <a href="{{ route('public.about') }}#rejoindre">Rejoindre le collectif</a>
            {{-- Pages éditables publiées (admin › Pages) : mentions légales, règles, confidentialité. --}}
            @includeIf('layouts.partials.footer-pages')
        </nav>
        @include('layouts.partials.social-links', ['class' => 'w-social'])
    </div>
</footer>
