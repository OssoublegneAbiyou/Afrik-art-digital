@php
    $nom = $collectif['nom'] ?? config('site.name');
    $fill = fn (?string $text) => str_replace('{nom}', $nom, (string) $text);
    // Contenu assaini à l'enregistrement ; on le repasse au filtre à l'affichage par sécurité,
    // puis on insère le nom du collectif (échappé).
    $contenu = str_replace('{nom}', e($nom), \App\Support\RichText::clean($page->contenu));
    $email = $collectif['contact']['email'] ?? null;
@endphp

<x-world-layout world="pont" :title="$fill($page->titre)" :description="$page->meta_description ? $fill($page->meta_description) : null">
    <div class="pont-halo" style="width:420px;height:420px;top:-80px;right:-120px;background:rgba(255,46,136,.22)" aria-hidden="true"></div>
    <div class="pont-halo" style="width:380px;height:380px;bottom:0;left:-140px;background:rgba(46,242,255,.14)" aria-hidden="true"></div>

    <section class="w-container relative py-16" aria-labelledby="page-title">
        @unless ($page->publie)
            <p class="pg-draft" role="status">Aperçu réservé à l’équipe : cette page n’est pas publiée.</p>
        @endunless

        <h1 id="page-title" class="font-bebas text-[clamp(2.6rem,7vw,4.5rem)] leading-none">{{ $fill($page->titre) }}</h1>

        <div class="glass-card pg-content mt-10 max-w-3xl p-6 leading-7 text-[var(--muted)] md:p-10">
            {!! $contenu !!}

            @if ($email)
                <p class="pg-content__contact">Contact : <a href="mailto:{{ $email }}">{{ $email }}</a></p>
            @endif
        </div>

        <p class="mt-6 text-sm text-[var(--dim)]">Mis à jour le {{ $page->updated_at?->locale('fr')->translatedFormat('j F Y') }}</p>
    </section>
</x-world-layout>
