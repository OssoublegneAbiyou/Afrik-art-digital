import React, { useEffect, useMemo, useRef, useState } from 'react';

const commonsFile = (fileName) => `https://commons.wikimedia.org/wiki/Special:Redirect/file/${encodeURIComponent(fileName)}`;

const moods = [
    {
        id: 'pulse',
        name: 'Pulse Rouge',
        tag: 'energie galerie',
        bg: '#160f1f',
        surface: '#fff3ea',
        accent: '#ef476f',
        second: '#ffb703',
        ink: '#fffaf4',
    },
    {
        id: 'lagoon',
        name: 'Lagune Neon',
        tag: 'calme profond',
        bg: '#071d2c',
        surface: '#e8fff8',
        accent: '#14b8a6',
        second: '#7dd3fc',
        ink: '#f4fffb',
    },
    {
        id: 'sun',
        name: 'Soleil Studio',
        tag: 'chaleur vivante',
        bg: '#241303',
        surface: '#fff6df',
        accent: '#ff7b54',
        second: '#ffb703',
        ink: '#fff8ed',
    },
    {
        id: 'night',
        name: 'Nocturne Violet',
        tag: 'visite intime',
        bg: '#130b2d',
        surface: '#f5edff',
        accent: '#8b5cf6',
        second: '#f472b6',
        ink: '#fbf7ff',
    },
    {
        id: 'earth',
        name: 'Terre Cuite',
        tag: 'matiere chaude',
        bg: '#24120d',
        surface: '#fff1e6',
        accent: '#c65f3a',
        second: '#f2c572',
        ink: '#fff7ef',
    },
    {
        id: 'gallery-white',
        name: 'Galerie Blanche',
        tag: 'minimal premium',
        bg: '#f6f1e8',
        surface: '#ffffff',
        accent: '#202020',
        second: '#d9a441',
        ink: '#181818',
    },
    {
        id: 'savanna',
        name: 'Savane Or',
        tag: 'nature solaire',
        bg: '#1c1707',
        surface: '#fff8dd',
        accent: '#d9a441',
        second: '#7aa95c',
        ink: '#fff9df',
    },
    {
        id: 'indigo',
        name: 'Indigo Profond',
        tag: 'nuit digitale',
        bg: '#07112f',
        surface: '#edf4ff',
        accent: '#4f7cff',
        second: '#b7f5ff',
        ink: '#f7fbff',
    },
    {
        id: 'rose',
        name: 'Rose Atelier',
        tag: 'doux vivant',
        bg: '#2a111a',
        surface: '#fff0f4',
        accent: '#ff6f91',
        second: '#ffd166',
        ink: '#fff8fb',
    },
    {
        id: 'graphite',
        name: 'Graphite Luxe',
        tag: 'sobre intense',
        bg: '#101010',
        surface: '#f8fafc',
        accent: '#a7f3d0',
        second: '#f8fafc',
        ink: '#ffffff',
    },
];

const tracks = [
    { id: 'abidjan', name: 'Deep', artist: 'Alex-Productions / Wikimedia Commons', tempo: 'dark ambient', url: commonsFile('Alex-Productions - Deep (Dark Ambient Background music).oga'), frequency: 146.83 },
    { id: 'lagos', name: 'Untold', artist: 'Amouth / Wikimedia Commons', tempo: '2 min 21', url: commonsFile('Amouth - Untold.ogg'), frequency: 174.61 },
    { id: 'savane', name: 'Ambient', artist: 'Brenticus / Wikimedia Commons', tempo: '4 min 25', url: commonsFile('Brenticus - Ambient.ogg'), frequency: 130.81 },
    { id: 'douala', name: 'Slow Memory', artist: 'Oleg Mazur / Wikimedia Commons', tempo: 'melancolique', url: commonsFile('Memory - A Slow Ambient Subtle Melancholic Track - by Oleg Mazur.ogg'), frequency: 196 },
    { id: 'peaceful', name: 'Peaceful', artist: 'Wikimedia Commons', tempo: '2 min 41', url: commonsFile('Peaceful.ogg'), frequency: 164.81 },
    { id: 'pulse-abel', name: '78 Pulse', artist: 'Kjartan Abel / Wikimedia Commons', tempo: '78 bpm', url: commonsFile('78-PULSE-by-Kjartan-Abel.ogg'), frequency: 155.56 },
    { id: 'tranholm', name: 'Siege at Tranholm', artist: 'Wikimedia Commons', tempo: 'cinematique', url: commonsFile('Siege at Tranholm.oga'), frequency: 110 },
    { id: 'aurora', name: 'Aurora', artist: 'Scott Buckley / Wikimedia Commons', tempo: '8 min 19', url: commonsFile('Scott Buckley - Aurora.mp3'), frequency: 220 },
    { id: 'silence', name: 'Art of Silence', artist: 'Uniq / Wikimedia Commons', tempo: 'minimal', url: commonsFile('Uniq - Art Of Silence V2.ogg'), frequency: 123.47 },
    { id: 'forest', name: 'Forest', artist: 'SoundAudio / Wikimedia Commons', tempo: 'nature', url: commonsFile('SoundAudio - Forest (relaxing music).opus'), frequency: 185 },
];

const stickerSet = ['spark', 'love', 'fire', 'moon', 'star', 'paint', 'wave', 'mask'];

const reactions = [
    { id: 'heart', label: 'Coeur', mark: '♥', base: 247 },
    { id: 'fire', label: 'Feu', mark: '◆', base: 89 },
    { id: 'spark', label: 'Eclat', mark: '✦', base: 132 },
    { id: 'paint', label: 'Art', mark: '●', base: 56 },
];

const fallbackArtworks = [
    {
        id: 'demo-1',
        title: 'Harmonie Rouge',
        imageUrl: null,
        meta: 'Acrylique sur toile',
    },
    {
        id: 'demo-2',
        title: 'Ocean Bleu Profond',
        imageUrl: null,
        meta: 'Huile sur toile',
    },
    {
        id: 'demo-3',
        title: "Foret de l'Ame",
        imageUrl: null,
        meta: 'Techniques mixtes',
    },
];

const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

const GalleryGlyph = ({ mood, index }) => (
    <svg viewBox="0 0 720 520" className="h-full w-full" aria-hidden="true">
        <defs>
            <linearGradient id={`paint-${index}`} x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stopColor={mood.accent} />
                <stop offset="50%" stopColor={mood.second} />
                <stop offset="100%" stopColor={mood.bg} />
            </linearGradient>
            <filter id={`soft-${index}`}>
                <feGaussianBlur stdDeviation="18" />
            </filter>
        </defs>
        <rect width="720" height="520" fill={mood.bg} />
        <circle cx="168" cy="118" r="130" fill={mood.accent} opacity="0.34" filter={`url(#soft-${index})`} />
        <circle cx="556" cy="340" r="168" fill={mood.second} opacity="0.26" filter={`url(#soft-${index})`} />
        <path
            d="M70 348C166 204 236 222 326 264c116 54 168-34 316-134v268H70Z"
            fill={`url(#paint-${index})`}
            opacity="0.88"
        />
        <path
            d="M80 160c88-52 184-50 288 8 74 41 158 43 252 6"
            fill="none"
            stroke={mood.ink}
            strokeWidth="10"
            strokeLinecap="round"
            opacity="0.72"
        />
        <path
            d="M110 410c128-58 244-66 348-24 58 23 106 27 150 12"
            fill="none"
            stroke={mood.ink}
            strokeWidth="6"
            strokeLinecap="round"
            opacity="0.44"
        />
        {Array.from({ length: 18 }).map((_, i) => (
            <circle
                key={i}
                cx={80 + ((i * 53) % 590)}
                cy={54 + ((i * 89) % 390)}
                r={6 + (i % 5) * 3}
                fill={i % 2 ? mood.second : mood.ink}
                opacity={0.26 + (i % 4) * 0.08}
            />
        ))}
    </svg>
);

const ArtistImmersivePortfolio = ({
    portfolio = null,
    artist = {},
    illustrations = [],
    csrfToken = '',
    follow = null,
    isFollowing = false,
    canEdit = false,
    editUrl = null,
    loginUrl = '',
    socialLinks = [],
}) => {
    const artworks = useMemo(() => {
        const source = illustrations.length > 0 ? illustrations : fallbackArtworks;

        return source.map((artwork, index) => ({
            ...artwork,
            mood: moods.find((mood) => mood.id === artwork.theme) || moods[index % moods.length],
            track: tracks.find((track) => track.id === artwork.music) || tracks[index % tracks.length],
            room: ['Salle I', 'Salle II', 'Salle III', 'Salle IV'][index % 4],
        }));
    }, [illustrations]);

    const [activeIndex, setActiveIndex] = useState(0);
    const [selectedMoodId, setSelectedMoodId] = useState(artworks[0]?.mood.id || moods[0].id);
    const [trackIndex, setTrackIndex] = useState(0);
    const [isPlaying, setIsPlaying] = useState(false);
    const [isSlideshow, setIsSlideshow] = useState(false);
    const [guided, setGuided] = useState(false);
    const [stickers, setStickers] = useState([]);
    const [reactionCounts, setReactionCounts] = useState(() => Object.fromEntries(reactions.map((item) => [item.id, item.base])));
    const audioRef = useRef(null);
    const defaultMusicRef = useRef(null);
    const customMusicRef = useRef(null);
    const guideAudioRef = useRef(null);
    const activeArtwork = artworks[activeIndex] || artworks[0];
    const selectedMood = moods.find((mood) => mood.id === selectedMoodId) || activeArtwork?.mood || moods[0];
    const selectedTrack = tracks[trackIndex] || activeArtwork?.track || tracks[0];

    useEffect(() => {
        if (!activeArtwork) return;

        setSelectedMoodId(activeArtwork.mood.id);
        setTrackIndex(tracks.findIndex((track) => track.id === activeArtwork.track.id));
        setStickers([]);
    }, [activeArtwork?.id]);

    useEffect(() => {
        if (!isPlaying || activeArtwork?.customMusicUrl || selectedTrack?.url || typeof window === 'undefined') return undefined;

        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return undefined;

        const context = audioRef.current?.context || new AudioContext();
        const master = context.createGain();
        const low = context.createOscillator();
        const shimmer = context.createOscillator();
        const pulse = context.createGain();

        low.type = 'sine';
        shimmer.type = 'triangle';
        low.frequency.value = selectedTrack.frequency;
        shimmer.frequency.value = selectedTrack.frequency * 2.01;
        master.gain.value = 0.028;
        pulse.gain.value = 0.35;

        low.connect(pulse);
        shimmer.connect(pulse);
        pulse.connect(master);
        master.connect(context.destination);
        low.start();
        shimmer.start();

        const interval = window.setInterval(() => {
            const now = context.currentTime;
            pulse.gain.cancelScheduledValues(now);
            pulse.gain.setValueAtTime(0.05, now);
            pulse.gain.linearRampToValueAtTime(0.42, now + 0.08);
            pulse.gain.exponentialRampToValueAtTime(0.06, now + 0.42);
        }, selectedTrack.id === 'savane' ? 860 : 640);

        audioRef.current = { context };

        return () => {
            window.clearInterval(interval);
            low.stop();
            shimmer.stop();
            master.disconnect();
        };
    }, [isPlaying, selectedTrack.id, selectedTrack.frequency, selectedTrack.url, activeArtwork?.customMusicUrl]);

    useEffect(() => {
        const audio = defaultMusicRef.current;
        if (!audio) return;

        audio.pause();
        audio.currentTime = 0;
        if (isPlaying && !activeArtwork?.customMusicUrl && selectedTrack?.url) {
            audio.play().catch(() => setIsPlaying(false));
        }
    }, [activeArtwork?.id, activeArtwork?.customMusicUrl, isPlaying, selectedTrack?.url]);

    useEffect(() => {
        const audio = customMusicRef.current;
        if (!audio) return;

        audio.pause();
        audio.currentTime = 0;
        if (isPlaying && activeArtwork?.customMusicUrl) {
            audio.play().catch(() => setIsPlaying(false));
        }
    }, [activeArtwork?.id, activeArtwork?.customMusicUrl, isPlaying]);

    useEffect(() => {
        if (!isSlideshow || artworks.length <= 1) return undefined;

        const guide = guideAudioRef.current;
        const delay = guided && guide?.duration && Number.isFinite(guide.duration)
            ? Math.max(30, Math.ceil(guide.duration) + 2) * 1000
            : 30000;

        const timeout = window.setTimeout(nextArtwork, delay);
        return () => window.clearTimeout(timeout);
    }, [isSlideshow, activeIndex, guided, activeArtwork?.guideAudioUrl, artworks.length]);

    const nextArtwork = () => {
        setActiveIndex((index) => (index + 1) % artworks.length);
    };

    const dropSticker = (kind) => {
        const id = `${kind}-${Date.now()}-${Math.random()}`;
        setStickers((items) => [
            ...items,
            {
                id,
                kind,
                x: clamp(12 + Math.random() * 76, 6, 88),
                y: clamp(12 + Math.random() * 72, 8, 84),
                rotation: Math.round(-18 + Math.random() * 36),
            },
        ]);
    };

    const react = (id) => {
        setReactionCounts((counts) => ({ ...counts, [id]: counts[id] + 1 }));
    };

    const changeTrack = (index) => {
        setTrackIndex(index);
        setIsPlaying(true);
    };

    const favoriteAction = activeArtwork?.favoriteAction;
    const favoriteMethod = activeArtwork?.isFavorite ? 'DELETE' : 'POST';

    return (
        <div
            className="min-h-screen overflow-hidden text-white transition-colors duration-700"
            style={{
                background: `radial-gradient(circle at 18% 8%, ${selectedMood.accent}55, transparent 30%), radial-gradient(circle at 86% 20%, ${selectedMood.second}44, transparent 24%), linear-gradient(145deg, ${selectedMood.bg}, #08070c 74%)`,
            }}
        >
            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:py-10">
                <section className="grid min-h-[calc(100vh-8rem)] gap-5 lg:grid-cols-[minmax(0,1fr)_21rem]">
                    <div className="relative overflow-hidden rounded-[1.5rem] border border-white/15 bg-black/22 shadow-[0_30px_110px_rgba(0,0,0,0.38)] backdrop-blur">
                        {artist.bannerUrl && (
                            <img
                                src={artist.bannerUrl}
                                alt=""
                                className="absolute inset-0 h-full w-full object-cover opacity-20 mix-blend-screen"
                            />
                        )}
                        <div className="absolute inset-0 opacity-40">
                            {Array.from({ length: 12 }).map((_, index) => (
                                <span
                                    key={index}
                                    className="absolute h-1.5 w-1.5 rounded-full bg-white"
                                    style={{
                                        left: `${8 + ((index * 17) % 86)}%`,
                                        top: `${8 + ((index * 29) % 78)}%`,
                                        opacity: 0.22 + (index % 4) * 0.12,
                                    }}
                                />
                            ))}
                        </div>

                        <div className="relative z-10 flex min-h-full flex-col">
                            <header className="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-4 py-4 sm:px-6">
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-[0.28em] text-white/56">
                                        {portfolio?.title || 'Galerie vivante'}
                                    </p>
                                    <h1 className="mt-1 text-2xl font-semibold tracking-[-0.03em] text-white sm:text-4xl">
                                        {artist.name || 'Artiste'}
                                    </h1>
                                </div>
                                <div className="flex flex-wrap items-center gap-2">
                                    {canEdit && editUrl && (
                                        <a
                                            href={editUrl}
                                            className="rounded-full border border-white/15 bg-white/8 px-4 py-2 text-sm font-semibold text-white/78 transition hover:bg-white/14"
                                        >
                                            Modifier
                                        </a>
                                    )}
                                    <button
                                        type="button"
                                        onClick={() => setGuided(false)}
                                        className={`rounded-full px-4 py-2 text-sm font-semibold transition ${!guided ? 'bg-white text-[#17121f]' : 'border border-white/15 bg-white/8 text-white/78 hover:bg-white/14'}`}
                                    >
                                        Immersif
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setIsSlideshow((value) => !value)}
                                        className={`rounded-full px-4 py-2 text-sm font-semibold transition ${isSlideshow ? 'bg-white text-[#17121f]' : 'border border-white/15 bg-white/8 text-white/78 hover:bg-white/14'}`}
                                    >
                                        Diaporama
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setGuided(true)}
                                        className={`rounded-full px-4 py-2 text-sm font-semibold transition ${guided ? 'bg-white text-[#17121f]' : 'border border-white/15 bg-white/8 text-white/78 hover:bg-white/14'}`}
                                    >
                                        Guide
                                    </button>
                                </div>
                            </header>

                            <div className="grid flex-1 gap-0 xl:grid-cols-[9rem_minmax(0,1fr)]">
                                <nav className="order-2 flex gap-3 overflow-x-auto border-t border-white/10 p-4 xl:order-1 xl:flex-col xl:border-r xl:border-t-0">
                                    {artworks.map((artwork, index) => (
                                        <button
                                            key={artwork.id}
                                            type="button"
                                            onClick={() => setActiveIndex(index)}
                                            className={`group h-20 w-20 shrink-0 overflow-hidden rounded-xl border text-left transition xl:h-24 xl:w-full ${index === activeIndex ? 'border-white bg-white/18' : 'border-white/12 bg-white/7 hover:bg-white/12'}`}
                                            title={artwork.title}
                                        >
                                            {artwork.imageUrl ? (
                                                <img src={artwork.imageUrl} alt="" className="h-full w-full object-cover transition duration-500 group-hover:scale-105" />
                                            ) : (
                                                <GalleryGlyph mood={artwork.mood} index={index} />
                                            )}
                                        </button>
                                    ))}
                                </nav>

                                <main className="order-1 grid min-h-[34rem] place-items-center p-4 sm:p-6 xl:order-2">
                                    <button
                                        type="button"
                                        onClick={nextArtwork}
                                        className="group relative h-full min-h-[28rem] w-full overflow-hidden rounded-[1.25rem] border border-white/12 bg-black/18 text-left shadow-[0_24px_80px_rgba(0,0,0,0.34)]"
                                    >
                                        <div className="absolute inset-0 transition duration-700 group-hover:scale-[1.015]">
                                            {activeArtwork?.imageUrl ? (
                                                <img
                                                    src={activeArtwork.imageUrl}
                                                    alt={activeArtwork.title}
                                                    className="h-full w-full object-contain"
                                                />
                                            ) : (
                                                <GalleryGlyph mood={selectedMood} index={activeIndex} />
                                            )}
                                        </div>
                                        <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(0,0,0,0.08)_0%,rgba(0,0,0,0.08)_45%,rgba(0,0,0,0.72)_100%)]" />

                                        <div className="pointer-events-none absolute inset-0">
                                            {stickers.map((sticker) => (
                                                <span
                                                    key={sticker.id}
                                                    className="absolute grid h-12 w-12 place-items-center rounded-full border border-white/25 bg-white/18 text-xl font-black text-white shadow-[0_10px_34px_rgba(0,0,0,0.25)] backdrop-blur"
                                                    style={{
                                                        left: `${sticker.x}%`,
                                                        top: `${sticker.y}%`,
                                                        transform: `translate(-50%, -50%) rotate(${sticker.rotation}deg)`,
                                                    }}
                                                >
                                                    {sticker.kind === 'love' && '♥'}
                                                    {sticker.kind === 'fire' && '◆'}
                                                    {sticker.kind === 'moon' && '◐'}
                                                    {sticker.kind === 'star' && '✦'}
                                                    {sticker.kind === 'paint' && '●'}
                                                    {sticker.kind === 'wave' && '≈'}
                                                    {sticker.kind === 'mask' && '◈'}
                                                    {sticker.kind === 'spark' && '✧'}
                                                </span>
                                            ))}
                                        </div>

                                        <div className="absolute bottom-0 left-0 right-0 p-5 sm:p-8">
                                            <div className="max-w-2xl">
                                                <p className="text-xs font-semibold uppercase tracking-[0.28em] text-white/58">{activeArtwork?.room}</p>
                                                <h2 className="mt-2 text-3xl font-semibold tracking-[-0.04em] text-white sm:text-5xl">
                                                    {activeArtwork?.title}
                                                </h2>
                                                <p className="mt-3 max-w-xl text-sm leading-7 text-white/72">
                                                    {guided
                                                        ? (activeArtwork?.meta || `${artist.name || 'L artiste'} transforme cette oeuvre en scene: theme ${selectedMood.name}, ambiance ${selectedTrack.name}.`)
                                                        : activeArtwork?.meta || artist.bio || 'Touchez la scene pour passer a la prochaine oeuvre.'}
                                                </p>
                                                {guided && activeArtwork?.guideAudioUrl && (
                                                    <audio
                                                        ref={guideAudioRef}
                                                        controls
                                                        src={activeArtwork.guideAudioUrl}
                                                        className="mt-4 w-full max-w-md"
                                                    >
                                                        Votre navigateur ne peut pas lire cet audio.
                                                    </audio>
                                                )}
                                            </div>
                                        </div>
                                        {activeArtwork?.customMusicUrl && (
                                            <audio ref={customMusicRef} src={activeArtwork.customMusicUrl} loop />
                                        )}
                                        {!activeArtwork?.customMusicUrl && selectedTrack?.url && (
                                            <audio ref={defaultMusicRef} src={selectedTrack.url} loop />
                                        )}
                                    </button>
                                </main>
                            </div>
                        </div>
                    </div>

                    <aside className="grid content-start gap-4">
                        <section className="rounded-[1.25rem] border border-white/14 bg-white/10 p-4 shadow-[0_24px_70px_rgba(0,0,0,0.22)] backdrop-blur">
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-[0.24em] text-white/52">Ambiance sonore</p>
                                    <h2 className="mt-2 text-lg font-semibold text-white">{activeArtwork?.customMusicUrl ? 'Musique importee' : selectedTrack.name}</h2>
                                    <p className="mt-1 text-sm text-white/58">{selectedTrack.artist} · {selectedTrack.tempo}</p>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => setIsPlaying((value) => !value)}
                                    className="grid h-12 w-12 shrink-0 place-items-center rounded-full text-sm font-black text-[#17121f] transition hover:scale-105"
                                    style={{ backgroundColor: selectedMood.second }}
                                    aria-label={isPlaying ? 'Pause' : 'Lecture'}
                                >
                                    {isPlaying ? 'II' : '▶'}
                                </button>
                            </div>
                            {canEdit ? (
                            <div className="mt-4 grid gap-2">
                                {tracks.map((track, index) => (
                                    <button
                                        key={track.id}
                                        type="button"
                                        onClick={() => changeTrack(index)}
                                        className={`flex items-center justify-between rounded-xl border px-3 py-2 text-left text-sm transition ${index === trackIndex ? 'border-white/30 bg-white/18 text-white' : 'border-white/10 bg-black/12 text-white/66 hover:bg-white/10'}`}
                                    >
                                        <span>{track.name}</span>
                                        <span className="text-xs text-white/45">{track.tempo}</span>
                                    </button>
                                ))}
                            </div>
                            ) : (
                                <p className="mt-4 rounded-xl border border-white/10 bg-black/12 px-3 py-2 text-xs text-white/58">
                                    Ambiance choisie par l'artiste pour cette scene.
                                </p>
                            )}
                            {activeArtwork?.customMusicUrl && (
                                <p className="mt-3 rounded-xl border border-white/10 bg-white/8 px-3 py-2 text-xs text-white/62">
                                    Cette scene utilise la musique importee par l artiste.
                                </p>
                            )}
                            <div className="mt-4 flex h-8 items-end gap-1">
                                {Array.from({ length: 18 }).map((_, index) => (
                                    <span
                                        key={index}
                                        className={`w-full rounded-full transition ${isPlaying ? 'animate-pulse' : ''}`}
                                        style={{
                                            height: `${8 + ((index * 13) % 22)}px`,
                                            backgroundColor: index % 2 ? selectedMood.accent : selectedMood.second,
                                            opacity: isPlaying ? 0.78 : 0.28,
                                        }}
                                    />
                                ))}
                            </div>
                        </section>

                        <section className="rounded-[1.25rem] border border-white/14 bg-white/10 p-4 backdrop-blur">
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-xs font-semibold uppercase tracking-[0.24em] text-white/52">Images du portfolio</p>
                                <span className="text-xs text-white/45">{activeIndex + 1}/{artworks.length}</span>
                            </div>
                            <div className="mt-4 grid gap-2">
                                {artworks.map((artwork, index) => (
                                    <button
                                        key={`${artwork.id}-list`}
                                        type="button"
                                        onClick={() => setActiveIndex(index)}
                                        className={`grid grid-cols-[3rem_minmax(0,1fr)] items-center gap-3 rounded-xl border p-2 text-left transition ${index === activeIndex ? 'border-white/30 bg-white/18' : 'border-white/10 bg-black/12 hover:bg-white/10'}`}
                                    >
                                        <span className="h-12 overflow-hidden rounded-lg bg-black/20">
                                            {artwork.imageUrl ? (
                                                <img src={artwork.imageUrl} alt="" className="h-full w-full object-cover" />
                                            ) : (
                                                <GalleryGlyph mood={artwork.mood} index={index} />
                                            )}
                                        </span>
                                        <span className="min-w-0">
                                            <span className="block truncate text-sm font-semibold text-white">{artwork.title}</span>
                                            <span className="mt-1 block truncate text-xs text-white/50">
                                                {artwork.mood.name} · {artwork.customMusicUrl ? 'Musique importee' : artwork.track.name}
                                                {artwork.guideAudioUrl ? ' · Guide' : ''}
                                            </span>
                                        </span>
                                    </button>
                                ))}
                            </div>
                        </section>

                        {canEdit && (
                        <section className="rounded-[1.25rem] border border-white/14 bg-white/10 p-4 backdrop-blur">
                            <p className="text-xs font-semibold uppercase tracking-[0.24em] text-white/52">Themes par oeuvre</p>
                            <div className="mt-4 grid grid-cols-2 gap-2">
                                {moods.map((mood) => (
                                    <button
                                        key={mood.id}
                                        type="button"
                                        onClick={() => setSelectedMoodId(mood.id)}
                                        className={`rounded-xl border p-3 text-left transition ${mood.id === selectedMoodId ? 'border-white/36 bg-white/18' : 'border-white/10 bg-black/12 hover:bg-white/10'}`}
                                    >
                                        <span className="mb-3 flex gap-1.5">
                                            <span className="h-3 w-6 rounded-full" style={{ backgroundColor: mood.accent }} />
                                            <span className="h-3 w-6 rounded-full" style={{ backgroundColor: mood.second }} />
                                        </span>
                                        <span className="block text-sm font-semibold text-white">{mood.name}</span>
                                        <span className="mt-1 block text-xs text-white/48">{mood.tag}</span>
                                    </button>
                                ))}
                            </div>
                        </section>
                        )}

                        {canEdit && (
                        <section className="rounded-[1.25rem] border border-white/14 bg-white/10 p-4 backdrop-blur">
                            <p className="text-xs font-semibold uppercase tracking-[0.24em] text-white/52">Stickers de visite</p>
                            <div className="mt-4 grid grid-cols-4 gap-2">
                                {stickerSet.map((sticker) => (
                                    <button
                                        key={sticker}
                                        type="button"
                                        onClick={() => dropSticker(sticker)}
                                        className="grid h-12 place-items-center rounded-xl border border-white/10 bg-black/14 text-lg font-black text-white transition hover:-translate-y-0.5 hover:bg-white/16"
                                        title={`Ajouter ${sticker}`}
                                    >
                                        {sticker === 'love' && '♥'}
                                        {sticker === 'fire' && '◆'}
                                        {sticker === 'moon' && '◐'}
                                        {sticker === 'star' && '✦'}
                                        {sticker === 'paint' && '●'}
                                        {sticker === 'wave' && '≈'}
                                        {sticker === 'mask' && '◈'}
                                        {sticker === 'spark' && '✧'}
                                    </button>
                                ))}
                            </div>
                        </section>
                        )}

                        <section className="rounded-[1.25rem] border border-white/14 bg-white/10 p-4 backdrop-blur">
                            <p className="text-xs font-semibold uppercase tracking-[0.24em] text-white/52">Reactions</p>
                            <div className="mt-4 flex flex-wrap gap-2">
                                {reactions.map((reaction) => (
                                    <button
                                        key={reaction.id}
                                        type="button"
                                        onClick={() => react(reaction.id)}
                                        className="rounded-full border border-white/12 bg-black/14 px-3 py-2 text-sm font-semibold text-white/78 transition hover:bg-white/14"
                                        title={reaction.label}
                                    >
                                        {reaction.mark} {reactionCounts[reaction.id]}
                                    </button>
                                ))}
                            </div>
                        </section>

                        <section className="rounded-[1.25rem] border border-white/14 bg-white/10 p-4 backdrop-blur">
                            <p className="text-xs font-semibold uppercase tracking-[0.24em] text-white/52">Artiste</p>
                            <p className="mt-3 text-sm leading-7 text-white/70">
                                {artist.bio || "Cet artiste n'a pas encore redige sa biographie. La visite immersive peut deja servir de portfolio vivant."}
                            </p>
                            <div className="mt-4 flex flex-wrap gap-2">
                                {socialLinks.map((link) => (
                                    <a
                                        key={link.label}
                                        href={link.url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="rounded-full border border-white/12 bg-white/10 px-3 py-2 text-xs font-semibold text-white/76 transition hover:bg-white/16"
                                    >
                                        {link.label}
                                    </a>
                                ))}
                            </div>
                            <div className="mt-4 grid gap-2">
                                {follow ? (
                                    <form method="POST" action={follow.action}>
                                        <input type="hidden" name="_token" value={csrfToken} />
                                        {isFollowing && <input type="hidden" name="_method" value="DELETE" />}
                                        <button type="submit" className="w-full rounded-xl bg-white px-4 py-3 text-sm font-semibold text-[#17121f] transition hover:-translate-y-0.5">
                                            {isFollowing ? 'Ne plus suivre' : 'Suivre cet artiste'}
                                        </button>
                                    </form>
                                ) : (
                                    <a href={loginUrl} className="block rounded-xl bg-white px-4 py-3 text-center text-sm font-semibold text-[#17121f] transition hover:-translate-y-0.5">
                                        Connectez-vous pour suivre
                                    </a>
                                )}

                                {favoriteAction ? (
                                    <form method="POST" action={favoriteAction}>
                                        <input type="hidden" name="_token" value={csrfToken} />
                                        {favoriteMethod === 'DELETE' && <input type="hidden" name="_method" value="DELETE" />}
                                        <button type="submit" className="w-full rounded-xl border border-white/16 bg-white/10 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/16">
                                            {activeArtwork?.isFavorite ? 'Retirer des favoris' : 'Ajouter cette oeuvre aux favoris'}
                                        </button>
                                    </form>
                                ) : loginUrl ? (
                                    <a href={loginUrl} className="block rounded-xl border border-white/16 bg-white/10 px-4 py-3 text-center text-sm font-semibold text-white transition hover:bg-white/16">
                                        Connectez-vous pour favoris
                                    </a>
                                ) : null}
                            </div>
                        </section>
                    </aside>
                </section>
            </div>
        </div>
    );
};

export default ArtistImmersivePortfolio;
