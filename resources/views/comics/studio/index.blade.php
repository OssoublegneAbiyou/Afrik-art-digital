<x-world-layout world="neo" title="Mes récits dessinés" description="One-shots et séries en chapitres : crée et gère tes récits dessinés." robots-noindex>
    <div class="nd cs">
        <section class="nd-hero">
            <div class="scanlines only-nuit" aria-hidden="true"></div>
            <div class="w-container nd-hero__inner">
                <div class="nd-hero__text">
                    <p class="neo-badge">Espace artiste · <span class="only-nuit">漫画</span><span class="only-jour">BD · Manga · Webtoon</span></p>
                    <h1 class="nd-title font-bebas">Récits <span class="glitch nd-title__name">dessinés</span></h1>
                    <p class="nd-lead">Publie des one-shots et des séries en chapitres, programme leurs sorties et suis tes lecteurs.</p>
                    <div class="nd-actions">
                        <a href="{{ route('dashboard') }}" class="neo-btn neo-btn--ghost">← Mon espace artiste</a>
                    </div>
                </div>
            </div>
        </section>

        <div class="w-container nd-body">
            @include('comics.studio._flash')
            @include('comics.studio._section', ['headingTag' => 'h2'])
        </div>
    </div>
</x-world-layout>
