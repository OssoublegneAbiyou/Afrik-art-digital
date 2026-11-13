<x-world-layout world="neo" title="Mes animations" description="Boucles et clips : envoie et gère tes animations courtes." robots-noindex>
    <div class="nd cs as">
        <section class="nd-hero">
            <div class="scanlines only-nuit" aria-hidden="true"></div>
            <div class="w-container nd-hero__inner">
                <div class="nd-hero__text">
                    <p class="neo-badge">Espace artiste · <span class="only-nuit">アニメ</span><span class="only-jour">Boucles · Clips</span></p>
                    <h1 class="nd-title font-bebas">Mes <span class="glitch nd-title__name">animations</span></h1>
                    <p class="nd-lead">Envoie des boucles muettes (comme un GIF) ou des clips avec son : ils sont convertis pour se lire vite, partout.</p>
                    <div class="nd-actions">
                        <a href="{{ route('dashboard') }}" class="neo-btn neo-btn--ghost">← Mon espace artiste</a>
                    </div>
                </div>
            </div>
        </section>

        <div class="w-container nd-body">
            @include('comics.studio._flash')
            @include('animations.studio._section', ['headingTag' => 'h2'])
        </div>
    </div>
</x-world-layout>
