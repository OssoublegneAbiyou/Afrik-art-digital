<x-admin-layout title="À valider" intro="Œuvres et récits des membres en attente de publication. Vérifiez qu’ils sont originaux : pas de personnages, logos ou visuels appartenant à d’autres. Le collectif est responsable de ce qu’il publie.">
    @unless ($moderationEnabled)
        <p class="adm-flash adm-flash--ok" role="note">
            La validation avant publication est désactivée : les nouvelles œuvres sont publiées directement.
            @if (Route::has('admin.settings.edit') && auth()->user()->can('manage-settings'))
                <a href="{{ route('admin.settings.edit') }}" class="adm-link">Modifier ce réglage</a>
            @endif
        </p>
    @endunless

    <section class="adm-card" aria-labelledby="queue-title">
        <h2 id="queue-title" class="adm-card__title">
            {{ $queue->isEmpty() ? 'Rien à valider' : \App\Support\Fr::count($queue->count(), 'œuvre').' en attente' }}
        </h2>

        @if ($queue->isEmpty())
            <p class="adm-empty">Rien à valider pour le moment.</p>
        @else
            <ul class="adm-list" role="list">
                @foreach ($queue as $work)
                    @php
                        $type = \App\Support\Moderation::typeOf($work);
                        $key = $type.'-'.$work->id;
                        $url = \App\Support\Moderation::publicUrl($work);
                        $author = $work->authorUser();
                        $hasError = $errors->has('note') && old('work') === $key;
                    @endphp
                    @if ($type === 'comic_chapter')
                        {{-- Chapitre de récit dessiné : planches en grille. --}}
                        @include('comics.studio.admin.moderation-item', ['chapter' => $work, 'key' => $key, 'url' => $url, 'author' => $author, 'hasError' => $hasError])
                        @continue
                    @endif
                    @if ($type === 'animation')
                        {{-- Animation : lecteur vidéo dans l'aperçu. --}}
                        @include('animations.studio.admin.moderation-item', ['animation' => $work, 'key' => $key, 'url' => $url, 'author' => $author, 'hasError' => $hasError])
                        @continue
                    @endif
                    <li class="adc-item" id="oeuvre-{{ $key }}">
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="adc-item__media">
                            <img src="{{ \App\Support\Moderation::thumbnailUrl($work) }}" alt="Aperçu de « {{ $work->title }} » (s’ouvre dans un nouvel onglet)" class="adc-item__img" loading="lazy">
                        </a>
                        <div class="adc-item__body">
                            <p class="adc-kicker">{{ \App\Support\Moderation::kindLabel($work) }}</p>
                            <h3 class="adc-item__title">{{ $work->title }}</h3>
                            <p class="adm-muted">
                                par {{ $author?->name ?? 'Auteur inconnu' }}
                                · soumise le <time datetime="{{ $work->updated_at->toIso8601String() }}">{{ $work->updated_at->locale('fr')->translatedFormat('j F Y à H\hi') }}</time>
                            </p>
                            @if ($work->isScheduled())
                                <p class="adm-muted">{{ $work->publicationLabel() }}</p>
                            @endif
                            @if ($work->description)
                                <p class="adc-item__text">{{ \Illuminate\Support\Str::limit(strip_tags($work->description), 280) }}</p>
                            @endif
                            @if ($work->tags)
                                <p class="adm-muted">#{{ implode(' #', $work->tags) }}</p>
                            @endif
                            <p class="adm-muted">
                                {{ $work->rights_certified_at ? 'Droits certifiés par l’auteur le '.$work->rights_certified_at->locale('fr')->translatedFormat('j F Y') : 'Droits non certifiés' }}
                                · <a href="{{ $url }}" target="_blank" rel="noopener" class="adm-link">Ouvrir en grand<span class="sr-only"> (nouvel onglet)</span> ↗</a>
                            </p>
                        </div>
                        <div class="adc-item__actions">
                            <form method="POST" action="{{ route('admin.moderation.approve', [$type, $work->id]) }}">
                                @csrf
                                <button type="submit" class="adm-btn adc-full">Publier</button>
                            </form>
                            <details class="adc-details" @if ($hasError) open @endif>
                                <summary class="adm-btn adm-btn--danger adc-full">Refuser…</summary>
                                <form method="POST" action="{{ route('admin.moderation.reject', [$type, $work->id]) }}" class="adc-details__form">
                                    @csrf
                                    <input type="hidden" name="work" value="{{ $key }}">
                                    <label for="note-{{ $key }}" class="adm-label">Motif <span class="adm-muted">(envoyé à l’auteur)</span></label>
                                    <textarea id="note-{{ $key }}" name="note" rows="3" required maxlength="500" class="adm-textarea"
                                        placeholder="Ex. : l’image reprend des personnages protégés."
                                        @if ($hasError) aria-invalid="true" aria-describedby="note-{{ $key }}-error" @endif>{{ $hasError ? old('note') : '' }}</textarea>
                                    @if ($hasError)
                                        <p id="note-{{ $key }}-error" class="adm-error">{{ $errors->first('note') }}</p>
                                    @endif
                                    <button type="submit" class="adm-btn adm-btn--danger">Confirmer le refus</button>
                                </form>
                            </details>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-admin-layout>
