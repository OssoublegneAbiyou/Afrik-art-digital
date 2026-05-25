@php
    $themeLabels = [
        'pulse' => 'Pulse Rouge',
        'lagoon' => 'Lagune Neon',
        'sun' => 'Soleil Studio',
        'night' => 'Nocturne Violet',
        'earth' => 'Terre Cuite',
        'gallery-white' => 'Galerie Blanche',
        'savanna' => 'Savane Or',
        'indigo' => 'Indigo Profond',
        'rose' => 'Rose Atelier',
        'graphite' => 'Graphite Luxe',
    ];

    $musicLabels = [
        'abidjan' => 'Libre - Deep',
        'lagos' => 'Libre - Untold',
        'savane' => 'Libre - Ambient',
        'douala' => 'Libre - Slow Memory',
        'peaceful' => 'Libre - Peaceful',
        'pulse-abel' => 'Libre - 78 Pulse',
        'tranholm' => 'Libre - Tranholm',
        'aurora' => 'Libre - Aurora',
        'silence' => 'Libre - Art of Silence',
        'forest' => 'Libre - Forest Room',
    ];

    $illustrationPreviewData = $illustrations->mapWithKeys(fn ($illustration) => [
        $illustration->id => [
            'title' => $illustration->title,
            'imageUrl' => asset('storage/' . $illustration->image_path),
        ],
    ]);

    $themePreviewData = [
        'pulse' => ['label' => 'Pulse Rouge', 'bg' => '#160f1f', 'accent' => '#ef476f', 'second' => '#ffb703'],
        'lagoon' => ['label' => 'Lagune Neon', 'bg' => '#071d2c', 'accent' => '#14b8a6', 'second' => '#7dd3fc'],
        'sun' => ['label' => 'Soleil Studio', 'bg' => '#241303', 'accent' => '#ff7b54', 'second' => '#ffb703'],
        'night' => ['label' => 'Nocturne Violet', 'bg' => '#130b2d', 'accent' => '#8b5cf6', 'second' => '#f472b6'],
        'earth' => ['label' => 'Terre Cuite', 'bg' => '#24120d', 'accent' => '#c65f3a', 'second' => '#f2c572'],
        'gallery-white' => ['label' => 'Galerie Blanche', 'bg' => '#f6f1e8', 'accent' => '#202020', 'second' => '#d9a441'],
        'savanna' => ['label' => 'Savane Or', 'bg' => '#1c1707', 'accent' => '#d9a441', 'second' => '#7aa95c'],
        'indigo' => ['label' => 'Indigo Profond', 'bg' => '#07112f', 'accent' => '#4f7cff', 'second' => '#b7f5ff'],
        'rose' => ['label' => 'Rose Atelier', 'bg' => '#2a111a', 'accent' => '#ff6f91', 'second' => '#ffd166'],
        'graphite' => ['label' => 'Graphite Luxe', 'bg' => '#101010', 'accent' => '#a7f3d0', 'second' => '#f8fafc'],
    ];

    $musicPreviewData = [
        'abidjan' => ['label' => 'Libre - Deep', 'artist' => 'Alex-Productions', 'tempo' => 'dark ambient'],
        'lagos' => ['label' => 'Libre - Untold', 'artist' => 'Amouth', 'tempo' => '2 min 21'],
        'savane' => ['label' => 'Libre - Ambient', 'artist' => 'Brenticus', 'tempo' => '4 min 25'],
        'douala' => ['label' => 'Libre - Slow Memory', 'artist' => 'Oleg Mazur', 'tempo' => 'melancolique'],
        'peaceful' => ['label' => 'Libre - Peaceful', 'artist' => 'Wikimedia Commons', 'tempo' => 'lent'],
        'pulse-abel' => ['label' => 'Libre - 78 Pulse', 'artist' => 'Kjartan Abel', 'tempo' => '78 bpm'],
        'tranholm' => ['label' => 'Libre - Tranholm', 'artist' => 'Wikimedia Commons', 'tempo' => 'cinematique'],
        'aurora' => ['label' => 'Libre - Aurora', 'artist' => 'Scott Buckley', 'tempo' => '8 min 19'],
        'silence' => ['label' => 'Libre - Art of Silence', 'artist' => 'Uniq', 'tempo' => 'minimal'],
        'forest' => ['label' => 'Libre - Forest Room', 'artist' => 'SoundAudio', 'tempo' => 'nature'],
    ];

    $existingItems = old('items')
        ? collect(old('items'))
        : ($portfolio?->items ?? collect())->map(fn ($item) => [
            'illustration_id' => $item->illustration_id,
            'theme' => $item->theme,
            'music' => $item->music,
            'existing_custom_music_path' => $item->custom_music_path,
            'existing_custom_music_size_bytes' => $item->custom_music_size_bytes,
            'description' => $item->description,
            'existing_guide_audio_path' => $item->guide_audio_path,
            'existing_guide_audio_size_bytes' => $item->guide_audio_size_bytes,
        ]);

    if ($existingItems->isEmpty()) {
        $existingItems = collect([[
            'illustration_id' => optional($illustrations->first())->id,
            'theme' => 'pulse',
            'music' => 'abidjan',
            'existing_custom_music_path' => '',
            'existing_custom_music_size_bytes' => 0,
            'description' => '',
            'existing_guide_audio_path' => '',
            'existing_guide_audio_size_bytes' => 0,
        ]]);
    }
@endphp

<x-app-layout>
    <div class="min-h-screen bg-[linear-gradient(180deg,#fff8ef_0%,#fffdf8_45%,#eef7f2_100%)] px-6 py-10 text-[#181818]">
        <div class="mx-auto max-w-6xl">
            <div class="rounded-[2rem] border border-black/10 bg-white/90 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.08)]">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <span class="inline-flex rounded-full border border-orange-200 bg-[#fff7f1] px-4 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-[#ef476f]">
                            Portfolio immersif
                        </span>
                        <h1 class="mt-4 text-3xl font-semibold uppercase tracking-[-0.04em] md:text-5xl">
                            {{ $portfolio ? 'Modifier le portfolio' : 'Creer mon portfolio' }}
                        </h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-[#525252]">
                            Configure chaque illustration comme une scene: theme, musique, description et audio guide.
                        </p>
                    </div>

                    <a href="{{ route('dashboard') }}" class="rounded-full border border-black/10 bg-white px-5 py-3 text-sm font-semibold shadow-sm">
                        Retour espace artiste
                    </a>
                </div>

                @if ($errors->any())
                    <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        Verifie les champs du portfolio. Une illustration, un theme et une musique sont requis pour chaque scene.
                    </div>
                @endif

                @if (session('success'))
                    <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($illustrations->isEmpty())
                    <div class="mt-8 rounded-[1.5rem] border border-dashed border-orange-200 bg-[#fffaf4] p-6 text-sm text-[#6f5c75]">
                        Ajoute d'abord au moins une illustration dans ton espace artiste avant de créer un portfolio.
                    </div>
                @else
                    <form
                        method="POST"
                        action="{{ $portfolio ? route('artist-portfolios.update', $portfolio) : route('artist-portfolios.store') }}"
                        enctype="multipart/form-data"
                        class="mt-8 grid gap-6"
                    >
                        @csrf
                        @if ($portfolio)
                            @method('PATCH')
                        @endif

                        <section class="grid gap-4 rounded-[1.5rem] border border-black/10 bg-[#f9f9f9] p-5 md:grid-cols-2">
                            <label class="grid gap-2 text-sm font-semibold">
                                Titre du portfolio
                                <input
                                    type="text"
                                    name="title"
                                    value="{{ old('title', $portfolio?->title ?? 'Portfolio immersif') }}"
                                    class="rounded-xl border border-black/10 bg-white px-4 py-3 text-sm"
                                    required
                                >
                            </label>

                            <label class="grid gap-2 text-sm font-semibold">
                                Description generale
                                <input
                                    type="text"
                                    name="description"
                                    value="{{ old('description', $portfolio?->description) }}"
                                    class="rounded-xl border border-black/10 bg-white px-4 py-3 text-sm"
                                    placeholder="Univers, intention, exposition..."
                                >
                            </label>
                        </section>

                        <section class="grid gap-5 rounded-[1.5rem] border border-black/10 bg-[#111111] p-4 text-white shadow-[0_20px_70px_rgba(0,0,0,0.18)] lg:grid-cols-[minmax(0,1fr)_16rem]">
                            <div id="liveStagePreview" class="relative min-h-[22rem] overflow-hidden rounded-[1.25rem] border border-white/10 bg-[#160f1f]">
                                <img id="livePreviewImage" src="" alt="" class="absolute inset-0 h-full w-full object-contain p-6 transition duration-500">
                                <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(0,0,0,0.04)_0%,rgba(0,0,0,0.12)_45%,rgba(0,0,0,0.76)_100%)]"></div>
                                <div class="pointer-events-none absolute left-6 top-6 flex gap-2">
                                    <span id="liveAccentOne" class="h-3 w-12 rounded-full bg-[#ef476f]"></span>
                                    <span id="liveAccentTwo" class="h-3 w-8 rounded-full bg-[#ffb703]"></span>
                                </div>
                                <div class="absolute bottom-0 left-0 right-0 p-6">
                                    <p id="liveSceneNumber" class="text-xs font-semibold uppercase tracking-[0.28em] text-white/55">Scene 1</p>
                                    <h2 id="livePreviewTitle" class="mt-2 text-3xl font-semibold tracking-[-0.04em]">Apercu de la scene</h2>
                                    <p id="livePreviewDescription" class="mt-3 max-w-2xl text-sm leading-7 text-white/70">Choisis une illustration, un theme et une musique pour voir l'ambiance.</p>
                                </div>
                            </div>

                            <aside class="grid content-start gap-3">
                                <div class="rounded-[1rem] border border-white/10 bg-white/10 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/45">Theme</p>
                                    <p id="liveThemeLabel" class="mt-2 text-lg font-semibold">Pulse Rouge</p>
                                    <div class="mt-3 flex gap-2">
                                        <span id="liveThemeSwatchOne" class="h-8 w-14 rounded-full bg-[#ef476f]"></span>
                                        <span id="liveThemeSwatchTwo" class="h-8 w-14 rounded-full bg-[#ffb703]"></span>
                                    </div>
                                </div>

                                <div class="rounded-[1rem] border border-white/10 bg-white/10 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/45">Musique</p>
                                    <p id="liveMusicLabel" class="mt-2 text-lg font-semibold">Abidjan Dream</p>
                                    <p id="liveMusicMeta" class="mt-1 text-sm text-white/55">Electronic soul · 86 bpm</p>
                                    <div class="mt-4 flex h-8 items-end gap-1">
                                        @foreach (range(1, 18) as $bar)
                                            <span class="music-preview-bar w-full rounded-full bg-white/50" style="height: {{ 8 + (($bar * 7) % 22) }}px"></span>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="rounded-[1rem] border border-white/10 bg-white/10 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/45">Guide audio</p>
                                    <p id="liveAudioLabel" class="mt-2 text-sm text-white/70">Aucun fichier selectionne</p>
                                </div>
                            </aside>
                        </section>

                        <div id="portfolioItems" class="grid gap-5">
                            @foreach ($existingItems as $index => $item)
                                <section class="portfolio-item rounded-[1.5rem] border border-black/10 bg-white p-5 shadow-[0_14px_36px_rgba(0,0,0,0.05)]" data-index="{{ $index }}">
                                    <div class="mb-4 flex items-center justify-between gap-3">
                                        <h2 class="text-lg font-semibold uppercase tracking-[0.1em]">Scene <span class="scene-number">{{ $index + 1 }}</span></h2>
                                        <button type="button" class="remove-item rounded-full border border-red-200 bg-red-50 px-4 py-2 text-xs font-semibold text-red-700">
                                            Retirer
                                        </button>
                                    </div>

                                    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_18rem]">
                                        <div>
                                            <div class="grid gap-4 lg:grid-cols-[1fr_1fr]">
                                                <label class="grid gap-2 text-sm font-semibold">
                                                    Illustration
                                                    <select name="items[{{ $index }}][illustration_id]" class="preview-illustration rounded-xl border border-black/10 bg-[#fafafa] px-4 py-3 text-sm" required>
                                                        @foreach ($illustrations as $illustration)
                                                            <option value="{{ $illustration->id }}" @selected((int) ($item['illustration_id'] ?? 0) === $illustration->id)>
                                                                {{ $illustration->title }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </label>

                                                <label class="grid gap-2 text-sm font-semibold">
                                                    Theme de cette illustration
                                                    <select name="items[{{ $index }}][theme]" class="preview-theme rounded-xl border border-black/10 bg-[#fafafa] px-4 py-3 text-sm" required>
                                                        @foreach ($themes as $theme)
                                                            <option value="{{ $theme }}" @selected(($item['theme'] ?? 'pulse') === $theme)>{{ $themeLabels[$theme] }}</option>
                                                        @endforeach
                                                    </select>
                                                </label>

                                                <label class="grid gap-2 text-sm font-semibold">
                                                    Musique d'ambiance
                                                    <select name="items[{{ $index }}][music]" class="preview-music rounded-xl border border-black/10 bg-[#fafafa] px-4 py-3 text-sm" required>
                                                        @foreach ($music as $track)
                                                            <option value="{{ $track }}" @selected(($item['music'] ?? 'abidjan') === $track)>{{ $musicLabels[$track] }}</option>
                                                        @endforeach
                                                    </select>
                                                    <span class="text-xs font-normal text-[#6f5c75]">Bibliotheque de musiques libres. Ta musique importee prend le dessus si tu ajoutes un fichier.</span>
                                                </label>

                                                <label class="grid gap-2 text-sm font-semibold">
                                                    Ma musique importee
                                                    <input type="file" name="items[{{ $index }}][custom_music]" accept="audio/*" class="preview-custom-music rounded-xl border border-black/10 bg-[#fafafa] px-4 py-3 text-sm">
                                                    <input type="hidden" name="items[{{ $index }}][existing_custom_music_path]" value="{{ $item['existing_custom_music_path'] ?? '' }}">
                                                    <input type="hidden" name="items[{{ $index }}][existing_custom_music_size_bytes]" value="{{ $item['existing_custom_music_size_bytes'] ?? 0 }}">
                                                    @if (! empty($item['existing_custom_music_path']))
                                                        <span class="text-xs text-[#6f5c75]">Musique deja ajoutee. Choisis un nouveau fichier pour la remplacer.</span>
                                                    @endif
                                                </label>

                                                <label class="grid gap-2 text-sm font-semibold">
                                                    Audio guide optionnel
                                                    <input type="file" name="items[{{ $index }}][guide_audio]" accept="audio/*" class="preview-audio rounded-xl border border-black/10 bg-[#fafafa] px-4 py-3 text-sm">
                                                    <input type="hidden" name="items[{{ $index }}][existing_guide_audio_path]" value="{{ $item['existing_guide_audio_path'] ?? '' }}">
                                                    <input type="hidden" name="items[{{ $index }}][existing_guide_audio_size_bytes]" value="{{ $item['existing_guide_audio_size_bytes'] ?? 0 }}">
                                                    @if (! empty($item['existing_guide_audio_path']))
                                                        <span class="text-xs text-[#6f5c75]">Audio deja ajoute. Choisis un nouveau fichier pour le remplacer.</span>
                                                    @endif
                                                </label>
                                            </div>

                                            <label class="mt-4 grid gap-2 text-sm font-semibold">
                                                Description de la scene
                                                <textarea name="items[{{ $index }}][description]" rows="4" class="preview-description rounded-xl border border-black/10 bg-[#fafafa] px-4 py-3 text-sm" placeholder="Ce que le visiteur doit ressentir ou comprendre...">{{ $item['description'] ?? '' }}</textarea>
                                            </label>
                                        </div>

                                        <button type="button" class="scene-preview-trigger overflow-hidden rounded-[1.25rem] border border-black/10 bg-[#160f1f] text-left text-white shadow-[0_16px_42px_rgba(0,0,0,0.12)]">
                                            <div class="scene-preview-stage relative h-64">
                                                <img class="scene-preview-image absolute inset-0 h-full w-full object-cover opacity-90" src="" alt="">
                                                <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(0,0,0,0.05),rgba(0,0,0,0.72))]"></div>
                                                <div class="absolute left-4 top-4 flex gap-1.5">
                                                    <span class="scene-preview-swatch-one h-3 w-8 rounded-full bg-[#ef476f]"></span>
                                                    <span class="scene-preview-swatch-two h-3 w-6 rounded-full bg-[#ffb703]"></span>
                                                </div>
                                                <div class="absolute bottom-0 left-0 right-0 p-4">
                                                    <p class="scene-preview-theme text-[10px] font-semibold uppercase tracking-[0.22em] text-white/55">Pulse Rouge</p>
                                                    <h3 class="scene-preview-title mt-1 text-lg font-semibold">Scene</h3>
                                                    <p class="scene-preview-music mt-1 text-xs text-white/65">Abidjan Dream</p>
                                                </div>
                                            </div>
                                        </button>
                                    </div>
                                </section>
                            @endforeach
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <button type="button" id="addPortfolioItem" class="rounded-full border border-black/10 bg-white px-5 py-3 text-sm font-semibold shadow-sm">
                                Ajouter une autre illustration
                            </button>

                            <div class="flex flex-wrap gap-3">
                                @if ($portfolio)
                                    <a href="{{ route('artist-portfolios.show', $portfolio) }}" class="rounded-full border border-black/10 bg-white px-5 py-3 text-sm font-semibold shadow-sm">
                                        Voir en immersion
                                    </a>
                                @endif
                                <button type="submit" class="rounded-full bg-[#181818] px-6 py-3 text-sm font-semibold text-white shadow-[0_16px_34px_rgba(0,0,0,0.14)]">
                                    Terminer le portfolio
                                </button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <template id="portfolioItemTemplate">
        <section class="portfolio-item rounded-[1.5rem] border border-black/10 bg-white p-5 shadow-[0_14px_36px_rgba(0,0,0,0.05)]" data-index="__INDEX__">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold uppercase tracking-[0.1em]">Scene <span class="scene-number">__NUMBER__</span></h2>
                <button type="button" class="remove-item rounded-full border border-red-200 bg-red-50 px-4 py-2 text-xs font-semibold text-red-700">Retirer</button>
            </div>
            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_18rem]">
                <div>
                    <div class="grid gap-4 lg:grid-cols-[1fr_1fr]">
                        <label class="grid gap-2 text-sm font-semibold">Illustration
                            <select name="items[__INDEX__][illustration_id]" class="preview-illustration rounded-xl border border-black/10 bg-[#fafafa] px-4 py-3 text-sm" required>
                                @foreach ($illustrations as $illustration)
                                    <option value="{{ $illustration->id }}">{{ $illustration->title }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="grid gap-2 text-sm font-semibold">Theme de cette illustration
                            <select name="items[__INDEX__][theme]" class="preview-theme rounded-xl border border-black/10 bg-[#fafafa] px-4 py-3 text-sm" required>
                                @foreach ($themes as $theme)
                                    <option value="{{ $theme }}">{{ $themeLabels[$theme] }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="grid gap-2 text-sm font-semibold">Musique d'ambiance
                            <select name="items[__INDEX__][music]" class="preview-music rounded-xl border border-black/10 bg-[#fafafa] px-4 py-3 text-sm" required>
                                @foreach ($music as $track)
                                    <option value="{{ $track }}">{{ $musicLabels[$track] }}</option>
                                @endforeach
                            </select>
                            <span class="text-xs font-normal text-[#6f5c75]">Bibliotheque de musiques libres. Ta musique importee prend le dessus si tu ajoutes un fichier.</span>
                        </label>
                        <label class="grid gap-2 text-sm font-semibold">Ma musique importee
                            <input type="file" name="items[__INDEX__][custom_music]" accept="audio/*" class="preview-custom-music rounded-xl border border-black/10 bg-[#fafafa] px-4 py-3 text-sm">
                            <input type="hidden" name="items[__INDEX__][existing_custom_music_path]" value="">
                            <input type="hidden" name="items[__INDEX__][existing_custom_music_size_bytes]" value="0">
                        </label>
                        <label class="grid gap-2 text-sm font-semibold">Audio guide optionnel
                            <input type="file" name="items[__INDEX__][guide_audio]" accept="audio/*" class="preview-audio rounded-xl border border-black/10 bg-[#fafafa] px-4 py-3 text-sm">
                            <input type="hidden" name="items[__INDEX__][existing_guide_audio_path]" value="">
                            <input type="hidden" name="items[__INDEX__][existing_guide_audio_size_bytes]" value="0">
                        </label>
                    </div>
                    <label class="mt-4 grid gap-2 text-sm font-semibold">Description de la scene
                        <textarea name="items[__INDEX__][description]" rows="4" class="preview-description rounded-xl border border-black/10 bg-[#fafafa] px-4 py-3 text-sm" placeholder="Ce que le visiteur doit ressentir ou comprendre..."></textarea>
                    </label>
                </div>
                <button type="button" class="scene-preview-trigger overflow-hidden rounded-[1.25rem] border border-black/10 bg-[#160f1f] text-left text-white shadow-[0_16px_42px_rgba(0,0,0,0.12)]">
                    <div class="scene-preview-stage relative h-64">
                        <img class="scene-preview-image absolute inset-0 h-full w-full object-cover opacity-90" src="" alt="">
                        <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(0,0,0,0.05),rgba(0,0,0,0.72))]"></div>
                        <div class="absolute left-4 top-4 flex gap-1.5">
                            <span class="scene-preview-swatch-one h-3 w-8 rounded-full bg-[#ef476f]"></span>
                            <span class="scene-preview-swatch-two h-3 w-6 rounded-full bg-[#ffb703]"></span>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 p-4">
                            <p class="scene-preview-theme text-[10px] font-semibold uppercase tracking-[0.22em] text-white/55">Pulse Rouge</p>
                            <h3 class="scene-preview-title mt-1 text-lg font-semibold">Scene</h3>
                            <p class="scene-preview-music mt-1 text-xs text-white/65">Abidjan Dream</p>
                        </div>
                    </div>
                </button>
            </div>
        </section>
    </template>

    <script>
        const illustrationPreviewData = @json($illustrationPreviewData);
        const themePreviewData = @json($themePreviewData);
        const musicPreviewData = @json($musicPreviewData);
        const list = document.getElementById('portfolioItems');
        const template = document.getElementById('portfolioItemTemplate');
        const addButton = document.getElementById('addPortfolioItem');
        let activePreviewItem = null;

        const live = {
            stage: document.getElementById('liveStagePreview'),
            image: document.getElementById('livePreviewImage'),
            title: document.getElementById('livePreviewTitle'),
            description: document.getElementById('livePreviewDescription'),
            scene: document.getElementById('liveSceneNumber'),
            theme: document.getElementById('liveThemeLabel'),
            music: document.getElementById('liveMusicLabel'),
            musicMeta: document.getElementById('liveMusicMeta'),
            audio: document.getElementById('liveAudioLabel'),
            accentOne: document.getElementById('liveAccentOne'),
            accentTwo: document.getElementById('liveAccentTwo'),
            swatchOne: document.getElementById('liveThemeSwatchOne'),
            swatchTwo: document.getElementById('liveThemeSwatchTwo'),
            bars: document.querySelectorAll('.music-preview-bar'),
        };

        function itemData(item) {
            const illustrationId = item.querySelector('.preview-illustration')?.value;
            const themeId = item.querySelector('.preview-theme')?.value || 'pulse';
            const musicId = item.querySelector('.preview-music')?.value || 'abidjan';
            const audioInput = item.querySelector('.preview-audio');
            const customMusicInput = item.querySelector('.preview-custom-music');
            const existingAudio = item.querySelector('[name$="[existing_guide_audio_path]"]')?.value;
            const existingMusic = item.querySelector('[name$="[existing_custom_music_path]"]')?.value;

            return {
                illustration: illustrationPreviewData[illustrationId] || {},
                theme: themePreviewData[themeId] || themePreviewData.pulse,
                music: musicPreviewData[musicId] || musicPreviewData.abidjan,
                description: item.querySelector('.preview-description')?.value || '',
                audioName: audioInput?.files?.[0]?.name || (existingAudio ? 'Audio guide deja ajoute' : 'Aucun fichier selectionne'),
                customMusicName: customMusicInput?.files?.[0]?.name || (existingMusic ? 'Musique importee deja ajoutee' : null),
                sceneNumber: Number(item.dataset.index || 0) + 1,
            };
        }

        function paintScenePreview(item) {
            const data = itemData(item);
            const stage = item.querySelector('.scene-preview-stage');
            const image = item.querySelector('.scene-preview-image');

            if (stage) stage.style.background = `radial-gradient(circle at top left, ${data.theme.accent}66, transparent 36%), linear-gradient(135deg, ${data.theme.bg}, #08070c)`;
            if (image) {
                image.src = data.illustration.imageUrl || '';
                image.alt = data.illustration.title || '';
            }
            item.querySelector('.scene-preview-swatch-one').style.backgroundColor = data.theme.accent;
            item.querySelector('.scene-preview-swatch-two').style.backgroundColor = data.theme.second;
            item.querySelector('.scene-preview-theme').textContent = data.theme.label;
            item.querySelector('.scene-preview-title').textContent = data.illustration.title || 'Scene';
            item.querySelector('.scene-preview-music').textContent = data.customMusicName || `${data.music.label} · ${data.music.tempo}`;
        }

        function paintLivePreview(item) {
            const data = itemData(item);
            live.stage.style.background = `radial-gradient(circle at 18% 8%, ${data.theme.accent}66, transparent 34%), radial-gradient(circle at 86% 20%, ${data.theme.second}55, transparent 26%), linear-gradient(145deg, ${data.theme.bg}, #08070c 74%)`;
            live.image.src = data.illustration.imageUrl || '';
            live.image.alt = data.illustration.title || '';
            live.title.textContent = data.illustration.title || 'Apercu de la scene';
            live.description.textContent = data.description || 'Ajoute une description pour sentir le mode guide de cette scene.';
            live.scene.textContent = `Scene ${data.sceneNumber}`;
            live.theme.textContent = data.theme.label;
            live.music.textContent = data.music.label;
            live.musicMeta.textContent = data.customMusicName ? `Musique importee · ${data.customMusicName}` : `${data.music.artist} · ${data.music.tempo}`;
            live.audio.textContent = data.audioName;
            live.accentOne.style.backgroundColor = data.theme.accent;
            live.accentTwo.style.backgroundColor = data.theme.second;
            live.swatchOne.style.backgroundColor = data.theme.accent;
            live.swatchTwo.style.backgroundColor = data.theme.second;
            live.bars.forEach((bar, index) => {
                bar.style.backgroundColor = index % 2 ? data.theme.accent : data.theme.second;
            });
        }

        function refreshPreviews(item = activePreviewItem) {
            list.querySelectorAll('.portfolio-item').forEach(paintScenePreview);
            activePreviewItem = item || list.querySelector('.portfolio-item');
            if (activePreviewItem) paintLivePreview(activePreviewItem);
        }

        function renumberItems() {
            list.querySelectorAll('.portfolio-item').forEach((item, index) => {
                item.dataset.index = index;
                item.querySelector('.scene-number').textContent = index + 1;
                item.querySelectorAll('[name]').forEach((field) => {
                    field.name = field.name.replace(/items\[\d+\]/, `items[${index}]`);
                });
            });
            refreshPreviews();
        }

        addButton?.addEventListener('click', () => {
            const index = list.querySelectorAll('.portfolio-item').length;
            const html = template.innerHTML.replaceAll('__INDEX__', index).replaceAll('__NUMBER__', index + 1);
            list.insertAdjacentHTML('beforeend', html);
            activePreviewItem = list.querySelector('.portfolio-item:last-child');
            refreshPreviews(activePreviewItem);
        });

        list?.addEventListener('click', (event) => {
            const preview = event.target.closest('.scene-preview-trigger');
            if (preview) {
                activePreviewItem = preview.closest('.portfolio-item');
                refreshPreviews(activePreviewItem);
                return;
            }

            if (event.target.classList.contains('remove-item')) {
                if (list.querySelectorAll('.portfolio-item').length === 1) return;
                event.target.closest('.portfolio-item').remove();
                activePreviewItem = list.querySelector('.portfolio-item');
                renumberItems();
            }
        });

        list?.addEventListener('input', (event) => {
            const item = event.target.closest('.portfolio-item');
            if (!item) return;
            activePreviewItem = item;
            refreshPreviews(item);
        });

        list?.addEventListener('change', (event) => {
            const item = event.target.closest('.portfolio-item');
            if (!item) return;
            activePreviewItem = item;
            refreshPreviews(item);
        });

        refreshPreviews(list?.querySelector('.portfolio-item'));
    </script>
</x-app-layout>
