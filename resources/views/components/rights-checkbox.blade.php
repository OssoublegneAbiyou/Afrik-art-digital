@props(['what' => 'cette œuvre', 'required' => true, 'hint' => null, 'idPrefix' => 'rights'])

{{-- Certification des droits : le collectif est responsable de ce qui est publié sur le site. --}}
<div {{ $attributes->class('rights') }}>
    <label class="rights__label" for="{{ $idPrefix }}-check">
        <input id="{{ $idPrefix }}-check" type="checkbox" name="rights" value="1" @if ($required) required @endif @checked(old('rights'))>
        <span>
            <strong>Je certifie être l'auteur de {{ $what }}</strong> et avoir le droit de la publier : pas de copie, ni de personnages, logos ou visuels appartenant à d'autres (marques, studios, éditeurs…).
            @if ($hint)
                <small>{{ $hint }}</small>
            @endif
        </span>
    </label>
    @php($rulesPage = \App\Models\Page::footerLinks()->firstWhere('slug', 'regles-de-publication'))
    @if ($rulesPage)
        <p class="rights__note">
            <a href="{{ $rulesPage['url'] }}" target="_blank" rel="noopener">Lire les règles de publication<span class="sr-only"> (s’ouvre dans un nouvel onglet)</span> <span aria-hidden="true">↗</span></a>
        </p>
    @endif
    @if (\App\Models\PlatformSetting::moderationEnabled() && ! auth()->user()?->isStaff())
        <p class="rights__note">Chaque nouvelle œuvre est relue par l'équipe avant d'apparaître sur le site.</p>
    @endif
</div>
