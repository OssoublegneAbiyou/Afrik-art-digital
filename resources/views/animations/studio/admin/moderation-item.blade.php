@php
    /** @var \App\Models\Animation $animation */
@endphp
<li class="adc-item as-adm-item" id="oeuvre-{{ $key }}">
    <div class="adc-item__media">
        @if ($animation->isReady())
            {{-- Jamais de lecture automatique dans l'admin : poster + contrôles ; le son des clips est conservé. --}}
            <video class="as-adm-video" src="{{ $animation->mp4Url() }}" @if ($animation->posterUrl()) poster="{{ $animation->posterUrl() }}" @endif
                controls playsinline preload="metadata" @if ($animation->isLoop()) muted loop @endif
                @if ($animation->largeur && $animation->hauteur) width="{{ $animation->largeur }}" height="{{ $animation->hauteur }}" @endif
                aria-label="Vidéo « {{ $animation->titre }} »"></video>
        @else
            <p class="adm-empty">Vidéo pas encore convertie.</p>
        @endif
    </div>
    <div class="adc-item__body">
        <p class="adc-kicker">{{ \App\Support\Moderation::kindLabel($animation) }}</p>
        <h3 class="adc-item__title">{{ $animation->titre }}</h3>
        <p class="adm-muted">
            par {{ $author?->name ?? 'Auteur inconnu' }}
            · soumise le <time datetime="{{ $animation->updated_at->toIso8601String() }}">{{ $animation->updated_at->locale('fr')->translatedFormat('j F Y à H\hi') }}</time>
            @if ($animation->largeur && $animation->hauteur)
                · {{ $animation->largeur }} × {{ $animation->hauteur }} px
            @endif
        </p>
        @if ($animation->isScheduled())
            <p class="adm-muted">{{ $animation->publicationLabel() }}</p>
        @endif
        @if ($animation->contenu_sensible)
            <p class="adm-muted"><span class="adm-tag adm-tag--pending">Contenu sensible</span> signalé par l’auteur.</p>
        @endif
        @if ($animation->description)
            <p class="adc-item__text">{{ \Illuminate\Support\Str::limit($animation->description, 280) }}</p>
        @endif
        @if ($animation->tags)
            <p class="adm-muted">#{{ implode(' #', $animation->tags) }}</p>
        @endif
        <p class="adm-muted">
            {{ $animation->rights_certified_at ? 'Droits certifiés par l’auteur le '.$animation->rights_certified_at->locale('fr')->translatedFormat('j F Y') : 'Droits non certifiés' }}
            @if ($url !== '#')
                · <a href="{{ $url }}" target="_blank" rel="noopener" class="adm-link">Ouvrir la fiche<span class="sr-only"> (nouvel onglet)</span> ↗</a>
            @endif
        </p>
    </div>
    <div class="adc-item__actions">
        <form method="POST" action="{{ route('admin.moderation.approve', ['animation', $animation->id]) }}">
            @csrf
            <button type="submit" class="adm-btn adc-full">Publier</button>
        </form>
        <details class="adc-details" @if ($hasError) open @endif>
            <summary class="adm-btn adm-btn--danger adc-full">Refuser…</summary>
            <form method="POST" action="{{ route('admin.moderation.reject', ['animation', $animation->id]) }}" class="adc-details__form">
                @csrf
                <input type="hidden" name="work" value="{{ $key }}">
                <label for="note-{{ $key }}" class="adm-label">Motif <span class="adm-muted">(envoyé à l’auteur)</span></label>
                <textarea id="note-{{ $key }}" name="note" rows="3" required maxlength="500" class="adm-textarea"
                    placeholder="Ex. : la musique utilisée appartient à quelqu’un d’autre."
                    @if ($hasError) aria-invalid="true" aria-describedby="note-{{ $key }}-error" @endif>{{ $hasError ? old('note') : '' }}</textarea>
                @if ($hasError)
                    <p id="note-{{ $key }}-error" class="adm-error">{{ $errors->first('note') }}</p>
                @endif
                <button type="submit" class="adm-btn adm-btn--danger">Confirmer le refus</button>
            </form>
        </details>
    </div>
</li>
