@php
    /** @var \App\Models\Animation $animation */
    use App\Models\Animation;
    use App\Support\AnimationStudio;
    use App\Support\ComicStudio;
    use App\Support\Ffmpeg;

    [$statusLabel, $statusTone] = $status;
    $ready = $animation->isReady();
    $tz = ComicStudio::timezone();
    $action = $ready
        ? old('action', $animation->isScheduled() ? Animation::STATUS_SCHEDULED : Animation::STATUS_PUBLISHED)
        : Animation::STATUS_DRAFT;
    $scheduled = old('scheduled_at', $animation->isScheduled() ? $animation->published_at->copy()->setTimezone($tz)->format('Y-m-d\TH:i') : '');
    $min = now()->setTimezone($tz)->addMinutes(5)->format('Y-m-d\TH:i');
    $duration = (float) ($animation->duree_s ?? 0);
    $posterSecond = old('poster_seconde', $animation->poster_seconde ?? min(1, max(0, $duration - 0.1)));
@endphp

<x-world-layout world="neo" :title="'Gérer · '.$animation->titre" description="Gestion d’une animation." robots-noindex>
    <div class="nd cs as">
        <section class="nd-hero">
            <div class="scanlines only-nuit" aria-hidden="true"></div>
            <div class="w-container nd-hero__inner">
                <div class="nd-hero__text">
                    <p class="neo-badge">Animation · {{ $animation->modeLabel() }}@if ($animation->genreLabel()) · {{ $animation->genreLabel() }}@endif</p>
                    <h1 class="nd-title font-bebas"><span class="glitch nd-title__name">{{ $animation->titre }}</span></h1>
                    <p class="cs-status cs-status--{{ $statusTone }}">{{ $statusLabel }}</p>
                    @if ($animation->isRejected() && $animation->moderation_note)
                        <p class="nd-hint">Motif de l’équipe : {{ $animation->moderation_note }}</p>
                    @endif
                    <div class="nd-actions">
                        <a href="{{ route('animations.studio.edit', $animation) }}" class="neo-btn neo-btn--a2">Modifier</a>
                        @if ($publicUrl && $ready)
                            <a href="{{ $publicUrl }}" class="neo-btn neo-btn--ghost">{{ $animation->isVisible() ? 'Voir la page publique' : 'Aperçu de la page' }} ↗</a>
                        @endif
                        <a href="{{ route('dashboard') }}#mes-animations" class="neo-btn neo-btn--ghost">Mon espace artiste</a>
                    </div>
                </div>
            </div>
        </section>

        <div class="w-container nd-body">
            @include('comics.studio._flash')

            {{-- Conversion : suivi en direct tant qu'elle n'est pas terminée --}}
            <section class="nd-section cs-first" aria-labelledby="as-conv-title"
                x-data="animationStatus(@js(route('animations.studio.status', $animation)), @js($state))">
                <h2 id="as-conv-title" class="nd-h2 font-bebas">Vidéo</h2>

                <div class="as-state" role="status" aria-live="polite">
                    <p class="as-state__line">
                        <span class="as-pill as-pill--{{ $animation->statut_traitement }}" x-bind:class="'as-pill--' + state.statut" x-text="state.libelle">{{ $state['libelle'] }}</span>
                        <template x-if="state.en_cours"><span class="cs-spinner" aria-hidden="true"></span></template>
                        <span class="nd-hint" x-show="state.en_cours" @unless ($state['en_cours']) x-cloak @endunless>
                            La conversion se fait en arrière-plan : tu peux quitter cette page et revenir plus tard.
                        </span>
                    </p>
                    <p class="as-error" x-show="state.erreur" x-text="state.erreur" @unless ($state['erreur']) x-cloak @endunless>{{ $state['erreur'] }}</p>
                </div>

                @if ($animation->statut_traitement === Animation::FAILED)
                    <div class="nd-row cs-actions">
                        @if ($ffmpegAvailable)
                            <form method="POST" action="{{ route('animations.studio.retry', $animation) }}">
                                @csrf
                                <button type="submit" class="neo-btn neo-btn--a2">Relancer la conversion</button>
                            </form>
                        @endif
                        <a href="{{ route('animations.studio.edit', $animation) }}" class="neo-btn neo-btn--ghost">Envoyer une autre vidéo</a>
                    </div>
                @endif

                @if ($ready)
                    <div class="as-preview">
                        <video class="as-preview__video" src="{{ $animation->mp4Url() }}" @if ($animation->posterUrl()) poster="{{ $animation->posterUrl() }}" @endif
                            controls playsinline preload="metadata" @if ($animation->isLoop()) muted loop @endif
                            @if ($animation->largeur && $animation->hauteur) width="{{ $animation->largeur }}" height="{{ $animation->hauteur }}" @endif
                            aria-label="Aperçu de « {{ $animation->titre }} »"></video>
                        <dl class="as-facts">
                            <div><dt>Durée</dt><dd>{{ $animation->durationLabel() ?? '—' }}</dd></div>
                            <div><dt>Dimensions</dt><dd>{{ $animation->largeur && $animation->hauteur ? $animation->largeur.' × '.$animation->hauteur.' px' : '—' }}</dd></div>
                            <div><dt>Son</dt><dd>{{ $animation->isLoop() ? 'Aucun (boucle muette)' : 'Conservé' }}</dd></div>
                            <div><dt>Stockage</dt><dd>{{ $state['taille'] }}</dd></div>
                        </dl>
                    </div>
                @endif
            </section>

            @if ($ready)
                {{-- Poster --}}
                <section class="nd-section" aria-labelledby="as-poster-title">
                    <h2 id="as-poster-title" class="nd-h2 font-bebas">Poster</h2>
                    <p class="nd-hint">L’image affichée avant la lecture et dans les grilles. Choisis l’instant de la vidéo à utiliser.</p>
                    <form method="POST" action="{{ route('animations.studio.poster', $animation) }}" class="neo-card nd-panel as-poster"
                        x-data="{ t: {{ (float) $posterSecond }}, seek() { const v = this.$refs.video; if (v && v.readyState > 0) { v.currentTime = Math.min(this.t, v.duration || this.t); } } }">
                        @csrf
                        <div class="nd-panel__body as-poster__body">
                            <video x-ref="video" class="as-poster__video" src="{{ $animation->mp4Url() }}" muted playsinline preload="auto"
                                x-on:loadedmetadata="seek()" aria-hidden="true" tabindex="-1"></video>
                            <div class="nd-form">
                                <div class="nd-field">
                                    <label for="as-poster-range">Instant du poster : <strong><span x-text="Number(t).toFixed(1).replace('.', ',')">{{ number_format((float) $posterSecond, 1, ',', ' ') }}</span> s</strong></label>
                                    <input id="as-poster-range" type="range" min="0" max="{{ $duration }}" step="0.1" x-model.number="t" x-on:input="seek()" class="as-range"
                                        aria-valuetext="{{ number_format((float) $posterSecond, 1, ',', ' ') }} secondes" x-bind:aria-valuetext="Number(t).toFixed(1).replace('.', ',') + ' secondes'">
                                </div>
                                <div class="nd-field">
                                    <label for="as-poster-seconde">Ou en secondes</label>
                                    <input id="as-poster-seconde" name="poster_seconde" type="number" min="0" max="{{ $duration }}" step="0.1" class="neo-input as-number"
                                        x-model.number="t" x-on:input="seek()" value="{{ $posterSecond }}" @error('poster_seconde') aria-invalid="true" aria-describedby="poster-error" @enderror>
                                    @error('poster_seconde')<p id="poster-error" class="nd-error">{{ $message }}</p>@enderror
                                </div>
                                @if ($animation->posterUrl())
                                    <figure class="as-poster__current">
                                        <img src="{{ $animation->posterUrl() }}" alt="" width="160" loading="lazy">
                                        <figcaption class="nd-hint">Poster actuel</figcaption>
                                    </figure>
                                @endif
                                <button type="submit" class="neo-btn neo-btn--a2" @disabled(! $ffmpegAvailable)>Utiliser cette image</button>
                            </div>
                        </div>
                    </form>
                </section>
            @endif

            {{-- Publication --}}
            <section class="nd-section" aria-labelledby="as-publish-title">
                <h2 id="as-publish-title" class="nd-h2 font-bebas">Publication</h2>
                <div class="neo-card neo-card--a2 nd-panel as-publish">
                    <div class="nd-panel__body">
                        @unless ($ready)
                            <p class="nd-flash nd-flash--warn">{{ $animation->statut_traitement === Animation::FAILED
                                ? 'La conversion a échoué : relance-la ou envoie une autre vidéo avant de publier.'
                                : 'Tu pourras publier dès la fin de la conversion. En attendant, tu peux garder l’animation en brouillon.' }}</p>
                        @endunless
                        @error('publication')<p class="nd-error" role="alert">{{ $message }}</p>@enderror

                        <form method="POST" action="{{ route('animations.studio.publish', $animation) }}" class="nd-form" x-data="{ action: @js($action) }">
                            @csrf
                            <fieldset class="nd-field">
                                <legend>Quand ?</legend>
                                <div class="cs-radios">
                                    <label class="cs-radio">
                                        <input type="radio" name="action" value="{{ Animation::STATUS_PUBLISHED }}" x-model="action" @checked($action === Animation::STATUS_PUBLISHED) @disabled(! $ready)>
                                        <span><strong>Publier maintenant</strong></span>
                                    </label>
                                    <label class="cs-radio">
                                        <input type="radio" name="action" value="{{ Animation::STATUS_SCHEDULED }}" x-model="action" @checked($action === Animation::STATUS_SCHEDULED) @disabled(! $ready)>
                                        <span><strong>Programmer</strong> : mise en ligne automatique à la date choisie.</span>
                                    </label>
                                    <label class="cs-radio">
                                        <input type="radio" name="action" value="{{ Animation::STATUS_DRAFT }}" x-model="action" @checked($action === Animation::STATUS_DRAFT)>
                                        <span><strong>Enregistrer en brouillon</strong> : visible par toi seul.</span>
                                    </label>
                                </div>
                            </fieldset>

                            <div class="nd-field" x-show="action === '{{ Animation::STATUS_SCHEDULED }}'" @if ($action !== Animation::STATUS_SCHEDULED) x-cloak @endif>
                                <label for="as-date">Date et heure de sortie <span class="nd-hint">({{ ComicStudio::timezoneLabel() }})</span></label>
                                <input id="as-date" type="datetime-local" name="scheduled_at" class="neo-input" value="{{ $scheduled }}" min="{{ $min }}"
                                    x-bind:required="action === '{{ Animation::STATUS_SCHEDULED }}'"
                                    @error('scheduled_at') aria-invalid="true" aria-describedby="as-date-error" @enderror>
                                @error('scheduled_at')<p id="as-date-error" class="nd-error">{{ $message }}</p>@enderror
                            </div>

                            @if ($needsReview)
                                <p class="nd-hint">L’équipe relit chaque nouvelle animation avant sa mise en ligne. Une animation programmée sort à l’heure prévue si elle a été validée, sinon dès sa validation.</p>
                            @endif

                            <button type="submit" class="neo-btn neo-btn--a1 nd-submit"
                                x-text="action === '{{ Animation::STATUS_DRAFT }}' ? 'Enregistrer le brouillon' : (action === '{{ Animation::STATUS_SCHEDULED }}' ? 'Programmer la sortie' : 'Publier maintenant')">{{ $ready ? 'Publier maintenant' : 'Enregistrer le brouillon' }}</button>
                        </form>
                    </div>
                </div>
            </section>

            {{-- Statistiques --}}
            <section class="nd-section" aria-labelledby="as-stats-title">
                <h2 id="as-stats-title" class="nd-h2 font-bebas">Statistiques</h2>
                <dl class="nd-stats cs-stats">
                    <div class="nd-stat nd-stat--a1"><dt>Vues</dt><dd><span class="font-bebas">{{ $stats['views'] }}</span></dd></div>
                    <div class="nd-stat nd-stat--a2"><dt>J’aime</dt><dd><span class="font-bebas">{{ $stats['likes'] }}</span></dd></div>
                    <div class="nd-stat nd-stat--a3"><dt>Favoris</dt><dd><span class="font-bebas">{{ $stats['favorites'] }}</span></dd></div>
                </dl>
                <p class="nd-hint">Une vue est comptée après quelques secondes de lecture{{ $animation->isLoop() ? '' : ' ou au lancement du clip' }}.</p>
            </section>

            {{-- Zone de danger --}}
            <section class="nd-section" aria-labelledby="as-danger-title">
                <h2 id="as-danger-title" class="nd-h2 font-bebas">Supprimer l’animation</h2>
                <form method="POST" action="{{ route('animations.studio.destroy', $animation) }}" class="cs-danger"
                    onsubmit="return confirm(@js('Supprimer définitivement « '.$animation->titre.' » et ses fichiers ? Cette action est irréversible.'))">
                    @csrf
                    @method('DELETE')
                    <p class="nd-hint">Supprime définitivement la vidéo d’origine, ses versions converties, ses J’aime et ses mises en favori.</p>
                    <button type="submit" class="nd-delete">Supprimer « {{ $animation->titre }} »</button>
                </form>
            </section>
        </div>
    </div>

    <script>
        // Suivi de la conversion : interroge l'état toutes les 3 s tant qu'elle est en cours, puis recharge la page.
        function animationStatus(url, initial) {
            return {
                state: initial,
                timer: null,
                init() {
                    if (this.state.en_cours) {
                        this.schedule();
                    }
                },
                schedule() {
                    this.timer = setTimeout(() => this.poll(), 3000);
                },
                poll() {
                    fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
                        .then((response) => (response.ok ? response.json() : null))
                        .then((data) => {
                            if (!data) {
                                this.schedule();
                                return;
                            }
                            this.state = data;
                            if (data.en_cours) {
                                this.schedule();
                            } else {
                                window.location.reload();
                            }
                        })
                        .catch(() => this.schedule());
                },
            };
        }
    </script>
</x-world-layout>
