@php
    use App\Support\Fr;

    $formatBytes = function (int $bytes): string {
        if ($bytes >= 1024 ** 3) {
            return number_format($bytes / 1024 ** 3, 2, ',', ' ').' Go';
        }

        return $bytes >= 1024 ** 2
            ? number_format($bytes / 1024 ** 2, 1, ',', ' ').' Mo'
            : number_format($bytes / 1024, 0, ',', ' ').' Ko';
    };
    $quotaReached = $remaining === 0;
    // « Croquis, Couleur ou Couverture » : types actifs, sans « Autre ».
    $namedTypes = collect($types)->except(\App\Models\Illustration::OTHER_TYPE)->values();
    $namedTypes = $namedTypes->count() >= 2 ? $namedTypes->slice(0, -1)->implode(', ').' ou '.$namedTypes->last() : $namedTypes->implode('');
    $socialFields = [
        'instagram' => 'Instagram',
        'twitter' => 'X (Twitter)',
        'facebook' => 'Facebook',
        'youtube' => 'YouTube',
        'behance' => 'Behance',
        'website' => 'Site web',
    ];
    $typeTone = fn (?string $type) => match ($type) {
        'croquis' => 'a2',
        'couverture' => 'a3',
        'couleur' => 'a1',
        'autre' => 'other',
        default => 'ghost',
    };
@endphp

<x-world-layout world="neo" title="Mon espace artiste" description="Gère ton studio : illustrations, portfolios immersifs et profil public." robots-noindex>
    <div class="nd">
        {{-- En-tête du studio --}}
        <section class="nd-hero">
            <div class="scanlines only-nuit" aria-hidden="true"></div>
            <div class="w-container nd-hero__inner">
                <div class="nd-hero__text">
                    <p class="neo-badge">Espace artiste · <span class="only-nuit">スタジオ</span><span class="only-jour">Studio</span></p>
                    <h1 class="nd-title font-bebas">
                        Studio <span class="glitch nd-title__name">{{ $user->name }}</span>
                    </h1>
                    <p class="nd-lead">Publie tes illustrations, compose tes portfolios immersifs et soigne ta vitrine publique.</p>
                    <div class="nd-actions">
                        <a href="{{ route('public.artist', $artist) }}" class="neo-btn neo-btn--a1">Voir ma vitrine ↗</a>
                        <a href="{{ route('artist-portfolios.create') }}" class="neo-btn neo-btn--ghost">Créer un portfolio</a>
                        <a href="{{ route('collection') }}" class="neo-btn neo-btn--ghost">Ma collection</a>
                        <a href="{{ route('profile.edit') }}" class="neo-btn neo-btn--ghost">Compte et mot de passe</a>
                    </div>
                </div>

                <dl class="nd-stats">
                    <div class="nd-stat nd-stat--a1">
                        <dt>Œuvres</dt>
                        <dd><span class="font-bebas">{{ $illustrations->count() }}</span><small>/ {{ $quota }}</small></dd>
                    </div>
                    <div class="nd-stat nd-stat--a2">
                        <dt>Portfolios</dt>
                        <dd><span class="font-bebas">{{ $portfolios->count() }}</span></dd>
                    </div>
                    <div class="nd-stat nd-stat--a3">
                        <dt>Abonnés</dt>
                        <dd><span class="font-bebas">{{ $stats['followers'] }}</span></dd>
                    </div>
                    <div class="nd-stat nd-stat--a2">
                        <dt>Vues</dt>
                        <dd><span class="font-bebas">{{ $stats['views'] }}</span></dd>
                    </div>
                    <div class="nd-stat nd-stat--a1">
                        <dt>J’aime</dt>
                        <dd><span class="font-bebas">{{ $stats['likes'] }}</span></dd>
                    </div>
                    <div class="nd-stat nd-stat--a3">
                        <dt>Favoris</dt>
                        <dd><span class="font-bebas">{{ $stats['favorites'] }}</span></dd>
                    </div>
                    <div class="nd-stat nd-stat--wide">
                        <dt>Stockage</dt>
                        <dd>
                            <span class="nd-meter" role="meter" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $storageRatio }}" aria-label="Stockage utilisé">
                                <span style="width: {{ $storageRatio }}%"></span>
                            </span>
                            <small>{{ $formatBytes($storageUsedBytes) }} sur {{ $formatBytes($storageLimitBytes) }}</small>
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        {{-- Sommaire de l'espace (surtout utile sur téléphone : la page est longue). --}}
        <nav class="nd-jump" aria-label="Sections de mon espace">
            <div class="w-container nd-jump__inner">
                <a href="#publier">Publier</a>
                <a href="#mes-illustrations">Mes illustrations</a>
                @if (\App\Support\ComicImages::enabled() && Route::has('comics.studio.index'))
                    <a href="#mes-recits-dessines">Récits dessinés</a>
                @endif
                @if (\App\Support\AnimationMedia::enabled() && Route::has('animations.studio.index'))
                    <a href="#mes-animations">Animations</a>
                @endif
                <a href="#mes-portfolios">Portfolios</a>
                <a href="#vitrine">Ma vitrine</a>
            </div>
        </nav>

        <div class="w-container nd-body">
            {{-- Messages --}}
            @if (session('success'))
                <p class="nd-flash nd-flash--ok" role="status">{{ session('success') }}</p>
            @endif
            @if ($errors->any())
                <div class="nd-flash nd-flash--error" role="alert">
                    <p><strong>Impossible d'enregistrer :</strong></p>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if ($untypedCount > 0)
                <p class="nd-flash nd-flash--warn">
                    {{ Fr::count($untypedCount, 'œuvre') }} sans type {{ $untypedCount >= 2 ? 'apparaissent' : 'apparaît' }} dans le filtre « Autre » de la galerie.
                    <a href="#mes-illustrations">Classe-{{ $untypedCount >= 2 ? 'les' : 'la' }} ci-dessous</a> pour qu'elles ressortent dans {{ $namedTypes }}.
                </p>
            @endif

            <div class="nd-grid">
                {{-- Publier --}}
                <section id="publier" class="neo-card nd-panel" aria-labelledby="publier-titre">
                    <div class="neo-card__head">
                        <h2 id="publier-titre" class="neo-card__name">Publier une œuvre</h2>
                        <span class="neo-card__tag">{{ Fr::count($remaining, 'place') }} {{ $remaining >= 2 ? 'libres' : 'libre' }}</span>
                    </div>
                    <div class="nd-panel__body">
                        @if ($quotaReached)
                            <p class="nd-note">Quota de {{ $quota }} illustrations atteint. Supprime une œuvre pour en publier une nouvelle.</p>
                        @else
                            <form method="POST" action="{{ route('illustrations.store') }}" enctype="multipart/form-data" class="nd-form"
                                x-data="{
                                    previews: [],
                                    max: {{ min($maxBatch, $remaining) }},
                                    pick(event) {
                                        this.previews.forEach((p) => URL.revokeObjectURL(p.url));
                                        this.previews = Array.from(event.target.files).map((file) => ({ name: file.name, url: URL.createObjectURL(file) }));
                                    },
                                    get single() { return this.previews.length <= 1; },
                                }">
                                @csrf
                                <div class="nd-field">
                                    <label for="upload-image">
                                        Images <span class="nd-hint">jusqu'à {{ min($maxBatch, $remaining) }} à la fois · JPG, PNG ou WEBP · {{ $maxImageMb }} Mo max chacune</span>
                                    </label>
                                    <input id="upload-image" name="images[]" type="file" accept="image/*" class="nd-file" multiple required x-on:change="pick($event)">
                                    <p class="nd-error" x-show="previews.length > max" x-cloak role="alert">
                                        Trop d'images : <span x-text="max"></span> au maximum pour cet envoi.
                                    </p>
                                    <ul class="nd-previews" x-show="previews.length" x-cloak role="list">
                                        <template x-for="preview in previews" :key="preview.url">
                                            <li><img :src="preview.url" :alt="'Aperçu : ' + preview.name"></li>
                                        </template>
                                    </ul>
                                </div>

                                <div class="nd-field" x-show="single">
                                    <label for="upload-title">Titre <span class="nd-hint">sinon, le nom du fichier</span></label>
                                    <input id="upload-title" name="title" type="text" class="neo-input" value="{{ old('title') }}" maxlength="255">
                                </div>
                                <p class="nd-hint" x-show="! single" x-cloak>
                                    Envoi groupé : chaque œuvre prend le nom de son fichier comme titre. Tu pourras modifier titres et descriptions ensuite.
                                </p>

                                <fieldset class="nd-field">
                                    <legend>Type <span class="nd-required">obligatoire</span></legend>
                                    <div class="nd-choices">
                                        @foreach ($types as $value => $label)
                                            <label class="nd-choice">
                                                <input type="radio" name="type" value="{{ $value }}" @checked(old('type') === $value) required>
                                                <span>{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>

                                <div class="nd-field" x-show="single">
                                    <label for="upload-desc">Description <span class="nd-hint">facultatif</span></label>
                                    <textarea id="upload-desc" name="description" rows="3" maxlength="2000" class="neo-input nd-textarea" placeholder="Technique, inspiration, histoire de l'œuvre…">{{ old('description') }}</textarea>
                                </div>

                                <div class="nd-field">
                                    <label for="upload-tags">Mots-clés <span class="nd-hint">séparés par des virgules · {{ \App\Models\Illustration::MAX_TAGS }} au plus</span></label>
                                    <input id="upload-tags" name="tags" type="text" class="neo-input" value="{{ old('tags') }}" maxlength="300" placeholder="manga, Abidjan, nuit">
                                </div>

                                <x-publication-fields id-prefix="upload" />

                                <x-rights-checkbox what="ces images" id-prefix="upload-rights" />

                                <button type="submit" class="neo-btn neo-btn--a1 nd-submit" x-bind:disabled="previews.length > max"
                                    x-text="previews.length >= 2 ? 'Envoyer les ' + previews.length + ' illustrations' : 'Envoyer l’illustration'">Envoyer l’illustration</button>
                            </form>
                        @endif
                    </div>
                </section>

                {{-- Profil public --}}
                <section id="vitrine" class="neo-card neo-card--a2 nd-panel" aria-labelledby="profil-titre">
                    <div class="neo-card__head">
                        <h2 id="profil-titre" class="neo-card__name">Ma vitrine</h2>
                        <span class="neo-card__tag">Profil public</span>
                    </div>
                    <div class="nd-panel__body">
                        <form method="POST" action="{{ route('artist.profile.update') }}" enctype="multipart/form-data" class="nd-form">
                            @csrf
                            @method('PATCH')

                            <div class="nd-field">
                                <label for="profile-bio">Bio</label>
                                <textarea id="profile-bio" name="bio" rows="5" class="neo-input nd-textarea" maxlength="3000" placeholder="Ton univers, tes influences, tes techniques…">{{ old('bio', $artist->bio) }}</textarea>
                            </div>

                            <div class="nd-field">
                                <label for="profile-banner">Bannière <span class="nd-hint">5 Mo max</span></label>
                                @if ($artist->banner_path)
                                    <img src="{{ asset('storage/'.$artist->banner_path) }}" alt="Bannière actuelle" class="nd-banner">
                                @endif
                                <input id="profile-banner" name="banner_image" type="file" accept="image/*" class="nd-file">
                            </div>

                            <div class="nd-socials">
                                @foreach ($socialFields as $field => $label)
                                    <div class="nd-field">
                                        <label for="social-{{ $field }}">{{ $label }}</label>
                                        <input id="social-{{ $field }}" name="{{ $field }}" type="text" class="neo-input" value="{{ old($field, $artist->{$field}) }}" maxlength="255"
                                            placeholder="{{ $field === 'website' ? 'monsite.com' : 'identifiant ou lien' }}">
                                    </div>
                                @endforeach
                            </div>

                            <button type="submit" class="neo-btn neo-btn--a2 nd-submit">Enregistrer ma vitrine</button>
                        </form>
                    </div>
                </section>
            </div>

            {{-- Récits dessinés (masqué si la rubrique est désactivée : réglage bd.active) --}}
            @if (\App\Support\ComicImages::enabled() && Route::has('comics.studio.index'))
                @include('comics.studio._section', [
                    'comics' => \App\Support\ComicStudio::comicsFor($user),
                    'storage' => \App\Support\ComicStudio::storage($user),
                ])
            @endif

            {{-- Animations (masqué si la rubrique est désactivée : réglage anim.active) --}}
            @if (\App\Support\AnimationMedia::enabled() && Route::has('animations.studio.index'))
                @include('animations.studio._section', [
                    'animations' => \App\Support\AnimationStudio::animationsFor($user),
                    'storage' => \App\Support\AnimationMedia::storage($user),
                ])
            @endif

            {{-- Portfolios --}}
            <section id="mes-portfolios" class="nd-section" aria-labelledby="portfolios-titre">
                <div class="nd-section__head">
                    <h2 id="portfolios-titre" class="nd-h2 font-bebas">Mes portfolios <span class="only-nuit font-jp">ポートフォリオ</span></h2>
                    <a href="{{ route('artist-portfolios.create') }}" class="neo-btn neo-btn--a3">+ Nouveau portfolio</a>
                </div>

                @if ($portfolios->isEmpty())
                    <p class="nd-empty">Aucun portfolio pour le moment. Compose une visite immersive scène par scène, avec ambiance sonore et thèmes.</p>
                @else
                    <div class="nd-portfolios">
                        @foreach ($portfolios as $portfolio)
                            <article class="neo-card neo-card--a3 nd-portfolio">
                                <div class="nd-panel__body">
                                    <p class="nd-kicker">{{ Fr::count($portfolio->items_count, 'scène') }}</p>
                                    <h3 class="nd-portfolio__title font-bebas">{{ $portfolio->title }}</h3>
                                    @if ($portfolio->description)
                                        <p class="nd-portfolio__desc">{{ $portfolio->description }}</p>
                                    @endif
                                    <div class="nd-row">
                                        <a href="{{ route('artist-portfolios.show', $portfolio) }}" class="neo-btn neo-btn--a1">Voir en immersion</a>
                                        <a href="{{ route('artist-portfolios.edit', $portfolio) }}" class="neo-btn neo-btn--ghost">Modifier</a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Illustrations --}}
            <section id="mes-illustrations" class="nd-section" aria-labelledby="illustrations-titre">
                <div class="nd-section__head">
                    <h2 id="illustrations-titre" class="nd-h2 font-bebas">Mes illustrations <span class="only-nuit font-jp">作品</span></h2>
                    <span class="nd-count">{{ Fr::count($illustrations->count(), 'œuvre') }} sur {{ $quota }}</span>
                </div>

                @if ($illustrations->isEmpty())
                    <p class="nd-empty">Tu n'as pas encore publié d'illustration. Utilise le formulaire « Publier une œuvre » ci-dessus.</p>
                @else
                    <ul class="nd-works" role="list">
                        @foreach ($illustrations as $illustration)
                            <li class="nd-work {{ $illustration->type ? '' : 'nd-work--untyped' }}">
                                <a href="{{ route('public.illustration', $illustration) }}" class="nd-work__media">
                                    <img src="{{ $illustration->thumbnailUrl() }}" alt="{{ $illustration->title }} par {{ $user->name }}" loading="lazy" class="neo-thumb">
                                    <span class="nd-work__type neo-btn--{{ $typeTone($illustration->type) }}">{{ $illustration->typeLabel() ?? 'Sans type' }}</span>
                                </a>
                                <div class="nd-work__body">
                                    <p class="nd-work__title">{{ $illustration->title }}</p>
                                    @unless ($illustration->isVisible())
                                        <p class="nd-status">{{ $illustration->statusLabel() }}</p>
                                        @if ($illustration->isRejected() && $illustration->moderation_note)
                                            <p class="nd-hint">Motif : {{ $illustration->moderation_note }}</p>
                                        @endif
                                    @endunless
                                    <p class="nd-work__stats">
                                        <span title="Vues">👁 {{ $illustration->view_count }}<span class="sr-only"> vues</span></span>
                                        <span title="J’aime">♥ {{ $illustration->likes_count }}<span class="sr-only"> J’aime</span></span>
                                        <span title="Favoris">★ {{ $illustration->favorited_by_count }}<span class="sr-only"> favoris</span></span>
                                    </p>
                                    <a href="{{ route('illustrations.edit', $illustration) }}" class="neo-btn neo-btn--a2 nd-work__edit">Modifier</a>

                                    <form method="POST" action="{{ route('illustrations.update', $illustration) }}" class="nd-work__type-form">
                                        @csrf
                                        @method('PATCH')
                                        <label for="type-{{ $illustration->id }}" class="nd-hint">Type</label>
                                        <div class="nd-row nd-row--tight">
                                            <select id="type-{{ $illustration->id }}" name="type" class="neo-input nd-select" required onchange="this.form.requestSubmit()">
                                                @unless ($illustration->type)
                                                    <option value="" selected disabled>À choisir…</option>
                                                @endunless
                                                @foreach (\App\Models\IllustrationType::options($illustration->type) as $value => $label)
                                                    <option value="{{ $value }}" @selected($illustration->type === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <noscript><button type="submit" class="neo-btn neo-btn--a2">OK</button></noscript>
                                        </div>
                                    </form>

                                    <form method="POST" action="{{ route('illustrations.destroy', $illustration) }}"
                                        onsubmit="return confirm('Supprimer définitivement « {{ e(addslashes($illustration->title)) }} » ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="nd-delete">Supprimer</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</x-world-layout>
