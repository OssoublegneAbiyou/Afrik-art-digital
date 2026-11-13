import React, { useEffect, useMemo, useRef, useState } from 'react';

const commonsFile = (fileName) => `https://commons.wikimedia.org/wiki/Special:Redirect/file/${encodeURIComponent(fileName)}`;

const moods = [
    {
        id: 'pulse',
        name: 'Pulse Rouge',
        tag: 'énergie galerie',
        bg: '#160f1f',
        surface: '#fff3ea',
        accent: '#ef476f',
        second: '#ffb703',
        ink: '#fffaf4',
    },
    {
        id: 'lagoon',
        name: 'Lagune Néon',
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
        tag: 'matière chaude',
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
    { id: 'douala', name: 'Slow Memory', artist: 'Oleg Mazur / Wikimedia Commons', tempo: 'mélancolique', url: commonsFile('Memory - A Slow Ambient Subtle Melancholic Track - by Oleg Mazur.ogg'), frequency: 196 },
    { id: 'peaceful', name: 'Peaceful', artist: 'Wikimedia Commons', tempo: '2 min 41', url: commonsFile('Peaceful.ogg'), frequency: 164.81 },
    { id: 'pulse-abel', name: '78 Pulse', artist: 'Kjartan Abel / Wikimedia Commons', tempo: '78 bpm', url: commonsFile('78-PULSE-by-Kjartan-Abel.ogg'), frequency: 155.56 },
    { id: 'tranholm', name: 'Siege at Tranholm', artist: 'Wikimedia Commons', tempo: 'cinématique', url: commonsFile('Siege at Tranholm.oga'), frequency: 110 },
    { id: 'aurora', name: 'Aurora', artist: 'Scott Buckley / Wikimedia Commons', tempo: '8 min 19', url: commonsFile('Scott Buckley - Aurora.mp3'), frequency: 220 },
    { id: 'silence', name: 'Art of Silence', artist: 'Uniq / Wikimedia Commons', tempo: 'minimal', url: commonsFile('Uniq - Art Of Silence V2.ogg'), frequency: 123.47 },
    { id: 'forest', name: 'Forest', artist: 'SoundAudio / Wikimedia Commons', tempo: 'nature', url: commonsFile('SoundAudio - Forest (relaxing music).opus'), frequency: 185 },
];


const stickerSet = ['spark', 'love', 'fire', 'moon', 'star', 'paint', 'wave', 'mask'];

const stickerGlyphs = {
    love: '♥',
    fire: '◆',
    moon: '◐',
    star: '✦',
    paint: '●',
    wave: '≈',
    mask: '◈',
    spark: '✧',
};

const stickerLabels = {
    love: 'cœur',
    fire: 'losange',
    moon: 'lune',
    star: 'étoile',
    paint: 'tache de peinture',
    wave: 'vague',
    mask: 'masque',
    spark: 'étincelle',
};

// Numérotation des salles : « Salle IV » et kanji « 第四室 ».
const toRoman = (value) => {
    const table = [[1000, 'M'], [900, 'CM'], [500, 'D'], [400, 'CD'], [100, 'C'], [90, 'XC'], [50, 'L'], [40, 'XL'], [10, 'X'], [9, 'IX'], [5, 'V'], [4, 'IV'], [1, 'I']];
    let rest = value;
    return table.reduce((out, [n, s]) => {
        while (rest >= n) {
            out += s;
            rest -= n;
        }
        return out;
    }, '');
};

const kanjiDigits = ['', '一', '二', '三', '四', '五', '六', '七', '八', '九'];
const toKanji = (value) => {
    if (value < 10) return kanjiDigits[value];
    if (value < 100) {
        const tens = Math.floor(value / 10);
        return `${tens > 1 ? kanjiDigits[tens] : ''}十${kanjiDigits[value % 10]}`;
    }
    return String(value);
};

const NEO = { bg: '#0A0119', pink: '#FF2A6D', cyan: '#05D9E8', yellow: '#FCEE0A', ink: '#F5F0FF', muted: '#CFC3EA', dim: '#A99BC9' };

const Equalizer = ({ on }) => (
    <span className={`nb-eq ${on ? 'is-on' : ''}`} aria-hidden="true">
        {Array.from({ length: 7 }).map((_, index) => <i key={index} />)}
    </span>
);

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

const pluralFr = (count, singular) => `${count} ${count >= 2 ? `${singular}s` : singular}`;


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
    const artworks = useMemo(() => illustrations.map((artwork, index) => ({
        ...artwork,
        mood: moods.find((mood) => mood.id === artwork.theme) || moods[index % moods.length],
        track: tracks.find((track) => track.id === artwork.music) || tracks[index % tracks.length],
        room: `Salle ${toRoman(index + 1)}`,
        roomKanji: `第${toKanji(index + 1)}室`,
    })), [illustrations]);

    const [activeIndex, setActiveIndex] = useState(0);
    const [selectedMoodId, setSelectedMoodId] = useState(artworks[0]?.mood.id || moods[0].id);
    const [trackIndex, setTrackIndex] = useState(0);
    const [isPlaying, setIsPlaying] = useState(false);
    const [isSlideshow, setIsSlideshow] = useState(false);
    const [guided, setGuided] = useState(false);
    const [stickers, setStickers] = useState([]);
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
        if (artworks.length === 0) return;
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

    const changeTrack = (index) => {
        setTrackIndex(index);
        setIsPlaying(true);
    };

    const favoriteAction = activeArtwork?.favoriteAction;
    const favoriteMethod = activeArtwork?.isFavorite ? 'DELETE' : 'POST';
    const favoritesCount = activeArtwork?.favoritesCount || 0;
    const followersCount = artist.followersCount || 0;
    const artistName = artist.name || 'Artiste';

    return (
        <div
            className="relative min-h-screen overflow-hidden transition-[background] duration-700"
            style={{
                color: NEO.ink,
                background: `radial-gradient(circle at 16% 6%, ${selectedMood.accent}40, transparent 32%), radial-gradient(circle at 88% 18%, rgba(5,217,232,.20), transparent 28%), radial-gradient(circle at 70% 100%, rgba(255,42,109,.18), transparent 40%), linear-gradient(160deg, ${NEO.bg}, #07010F 72%)`,
            }}
        >
            <div className="scanlines" aria-hidden="true" />
            <div className="grid3d" aria-hidden="true" style={{ opacity: 0.35 }} />

            <div className="relative mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:py-10">
                <section className="grid gap-5 lg:min-h-[calc(100vh-8rem)] lg:grid-cols-[minmax(0,1fr)_21rem]">
                    <div className="nb-im-panel relative overflow-hidden" style={{ borderColor: NEO.cyan, boxShadow: '0 0 40px rgba(5,217,232,.18)' }}>
                        {artist.bannerUrl && (
                            <img
                                src={artist.bannerUrl}
                                alt=""
                                className="absolute inset-0 h-full w-full object-cover opacity-15 mix-blend-screen"
                            />
                        )}

                        <div className="relative z-10 flex min-h-full flex-col">
                            <div className="flex flex-wrap items-center justify-between gap-3 border-b-2 px-4 py-4 sm:px-6" style={{ borderColor: '#3B1F5E' }}>
                                <div className="min-w-0">
                                    <p className="nb-im-kicker">
                                        {portfolio?.title || 'Galerie privée'} <span aria-hidden="true" style={{ color: NEO.pink }}>· 夜</span>
                                    </p>
                                    <h1 className="font-bebas mt-1 text-4xl leading-none sm:text-6xl" style={{ overflowWrap: 'anywhere' }}>
                                        <a href={artist.profileUrl} className="glitch" style={{ color: NEO.ink, textDecoration: 'none' }}>{artistName}</a>
                                    </h1>
                                </div>
                                <div className="flex flex-wrap items-center gap-2" role="group" aria-label="Mode de visite">
                                    {canEdit && editUrl && (
                                        <a href={editUrl} className="nb-im-btn">Modifier</a>
                                    )}
                                    <button type="button" onClick={() => setGuided(false)} className="nb-im-btn" aria-pressed={!guided}>
                                        Immersif
                                    </button>
                                    <button type="button" onClick={() => setIsSlideshow((value) => !value)} className="nb-im-btn" aria-pressed={isSlideshow} disabled={artworks.length === 0}>
                                        Diaporama
                                    </button>
                                    <button type="button" onClick={() => setGuided(true)} className="nb-im-btn" aria-pressed={guided}>
                                        Guide
                                    </button>
                                </div>
                            </div>

                            {artworks.length === 0 ? (
                                <div className="grid flex-1 place-items-center p-10 text-center">
                                    <div>
                                        <p className="font-jp text-5xl" aria-hidden="true" style={{ color: NEO.pink, textShadow: '0 0 18px rgba(255,42,109,.7)' }}>空室</p>
                                        <p className="mt-4" style={{ color: NEO.muted }}>Ce portfolio ne contient pas encore d'œuvre.</p>
                                    </div>
                                </div>
                            ) : (
                                <div className="grid flex-1 gap-0 xl:grid-cols-[9rem_minmax(0,1fr)]">
                                    <nav className="order-2 flex gap-3 overflow-x-auto border-t-2 p-4 xl:order-1 xl:flex-col xl:border-r-2 xl:border-t-0" style={{ borderColor: '#3B1F5E' }} aria-label="Salles du portfolio">
                                        {artworks.map((artwork, index) => (
                                            <button
                                                key={artwork.id}
                                                type="button"
                                                onClick={() => setActiveIndex(index)}
                                                className="group relative h-20 w-20 shrink-0 overflow-hidden border-2 text-left transition xl:h-24 xl:w-full"
                                                style={{
                                                    borderColor: index === activeIndex ? NEO.pink : '#3B1F5E',
                                                    boxShadow: index === activeIndex ? '0 0 18px rgba(255,42,109,.55)' : 'none',
                                                    background: '#1A0B33',
                                                }}
                                                aria-label={`${artwork.room} : ${artwork.title}`}
                                                aria-current={index === activeIndex ? 'true' : undefined}
                                            >
                                                {artwork.imageUrl ? (
                                                    <img src={artwork.thumbnailUrl || artwork.imageUrl} alt="" loading="lazy" className="h-full w-full object-cover transition duration-500 group-hover:scale-105" />
                                                ) : (
                                                    <GalleryGlyph mood={artwork.mood} index={index} />
                                                )}
                                                <span className="absolute left-0 top-0 px-1.5 py-0.5 text-[10px] font-bold" style={{ background: NEO.bg, color: NEO.cyan }} aria-hidden="true">
                                                    {toRoman(index + 1)}
                                                </span>
                                            </button>
                                        ))}
                                    </nav>

                                    <div className="order-1 flex min-h-[34rem] gap-3 p-4 sm:gap-4 sm:p-6 xl:order-2">
                                        <span className="nb-im-kanji flick hidden self-start pt-2 sm:block" aria-hidden="true">{activeArtwork?.roomKanji}</span>
                                        <button
                                            type="button"
                                            onClick={nextArtwork}
                                            className="group relative min-h-[28rem] w-full flex-1 overflow-hidden border-2 text-left"
                                            style={{ borderColor: selectedMood.accent, background: 'rgba(7,1,15,.55)', boxShadow: `0 0 34px ${selectedMood.accent}55, -12px 12px 0 rgba(255,42,109,.35)` }}
                                            aria-label={`${activeArtwork?.title} par ${artistName} — œuvre suivante`}
                                        >
                                            <div className="absolute inset-0 transition duration-700 group-hover:scale-[1.015]">
                                                {activeArtwork?.imageUrl ? (
                                                    <img
                                                        src={activeArtwork.imageUrl}
                                                        alt={`${activeArtwork.title} par ${artistName}`}
                                                        className="h-full w-full object-contain"
                                                    />
                                                ) : (
                                                    <GalleryGlyph mood={selectedMood} index={activeIndex} />
                                                )}
                                            </div>
                                            <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(10,1,25,0.05)_0%,rgba(10,1,25,0.05)_45%,rgba(10,1,25,0.85)_100%)]" />

                                            <span className="absolute right-3 top-3 px-2 py-1 text-xs font-bold tracking-[0.16em]" style={{ background: NEO.cyan, color: NEO.bg }} aria-hidden="true">
                                                {activeArtwork?.roomKanji} · {activeIndex + 1}/{artworks.length}
                                            </span>

                                            <div className="pointer-events-none absolute inset-0">
                                                {stickers.map((sticker) => (
                                                    <span
                                                        key={sticker.id}
                                                        className="absolute grid h-12 w-12 place-items-center rounded-full border-2 text-xl font-black"
                                                        style={{
                                                            left: `${sticker.x}%`,
                                                            top: `${sticker.y}%`,
                                                            transform: `translate(-50%, -50%) rotate(${sticker.rotation}deg)`,
                                                            borderColor: NEO.yellow,
                                                            background: 'rgba(13,2,33,.7)',
                                                            color: NEO.yellow,
                                                            boxShadow: '0 0 16px rgba(252,238,10,.45)',
                                                        }}
                                                    >
                                                        {stickerGlyphs[sticker.kind]}
                                                    </span>
                                                ))}
                                            </div>

                                            <div className="absolute bottom-0 left-0 right-0 p-5 sm:p-8">
                                                <div className="max-w-2xl">
                                                    <p className="nb-im-kicker">{activeArtwork?.room}</p>
                                                    <h2 className="font-bebas mt-2 text-4xl leading-none sm:text-6xl" style={{ color: NEO.ink, overflowWrap: 'anywhere' }}>
                                                        {activeArtwork?.title}
                                                    </h2>
                                                    <p className="mt-3 max-w-xl text-sm leading-7" style={{ color: NEO.muted }}>
                                                        {guided
                                                            ? (activeArtwork?.meta || `${artistName} transforme cette œuvre en scène : thème ${selectedMood.name}, ambiance ${selectedTrack.name}.`)
                                                            : activeArtwork?.meta || artist.bio || 'Touchez la scène pour passer à la prochaine œuvre.'}
                                                    </p>
                                                    {guided && activeArtwork?.guideAudioUrl && (
                                                        <audio
                                                            ref={guideAudioRef}
                                                            controls
                                                            src={activeArtwork.guideAudioUrl}
                                                            className="pointer-events-auto mt-4 w-full max-w-md"
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
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    <aside className="grid content-start gap-4" aria-label="Panneau de visite">
                        <section className="nb-im-panel p-4" style={{ borderColor: NEO.pink }}>
                            <div className="flex items-start justify-between gap-4">
                                <div className="min-w-0">
                                    <p className="nb-im-kicker">Ambiance sonore</p>
                                    <div className="mt-2 flex items-center gap-3">
                                        <Equalizer on={isPlaying} />
                                        <h2 className="truncate text-lg font-bold" style={{ color: NEO.ink }}>{activeArtwork?.customMusicUrl ? 'Musique importée' : selectedTrack.name}</h2>
                                    </div>
                                    <p className="mt-1 text-sm" style={{ color: NEO.dim }}>{selectedTrack.artist} · {selectedTrack.tempo}</p>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => setIsPlaying((value) => !value)}
                                    className="grid h-12 w-12 shrink-0 place-items-center rounded-full text-sm font-black transition hover:scale-105"
                                    style={{ backgroundColor: NEO.pink, color: NEO.bg, boxShadow: '0 0 18px rgba(255,42,109,.6)' }}
                                    aria-label={isPlaying ? 'Pause' : 'Lecture'}
                                    aria-pressed={isPlaying}
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
                                            className="flex min-h-[44px] items-center justify-between gap-2 border-2 px-3 py-2 text-left text-sm transition"
                                            style={{
                                                borderColor: index === trackIndex ? NEO.cyan : '#3B1F5E',
                                                background: index === trackIndex ? 'rgba(5,217,232,.12)' : 'rgba(7,1,15,.4)',
                                                color: index === trackIndex ? NEO.ink : NEO.muted,
                                            }}
                                            aria-pressed={index === trackIndex}
                                        >
                                            <span className="flex items-center gap-2">
                                                {index === trackIndex && <Equalizer on={isPlaying} />}
                                                {track.name}
                                            </span>
                                            <span className="text-xs" style={{ color: NEO.dim }}>{track.tempo}</span>
                                        </button>
                                    ))}
                                </div>
                            ) : (
                                <p className="mt-4 border-2 px-3 py-2 text-xs" style={{ borderColor: '#3B1F5E', color: NEO.dim }}>
                                    Ambiance choisie par l'artiste pour cette scène.
                                </p>
                            )}
                            {activeArtwork?.customMusicUrl && (
                                <p className="mt-3 border-2 px-3 py-2 text-xs" style={{ borderColor: '#3B1F5E', color: NEO.muted }}>
                                    Cette scène utilise la musique importée par l’artiste.
                                </p>
                            )}
                        </section>

                        {artworks.length > 0 && (
                            <section className="nb-im-panel p-4">
                                <div className="flex items-center justify-between gap-3">
                                    <p className="nb-im-kicker">Salles du portfolio</p>
                                    <span className="text-xs font-bold" style={{ color: NEO.yellow }}>{activeIndex + 1}/{artworks.length}</span>
                                </div>
                                <div className="mt-4 grid gap-2">
                                    {artworks.map((artwork, index) => (
                                        <button
                                            key={`${artwork.id}-list`}
                                            type="button"
                                            onClick={() => setActiveIndex(index)}
                                            className="grid grid-cols-[3rem_minmax(0,1fr)] items-center gap-3 border-2 p-2 text-left transition"
                                            style={{
                                                borderColor: index === activeIndex ? NEO.pink : '#3B1F5E',
                                                background: index === activeIndex ? 'rgba(255,42,109,.12)' : 'rgba(7,1,15,.4)',
                                            }}
                                            aria-current={index === activeIndex ? 'true' : undefined}
                                        >
                                            <span className="h-12 overflow-hidden" style={{ background: '#24104A' }}>
                                                {artwork.imageUrl ? (
                                                    <img src={artwork.thumbnailUrl || artwork.imageUrl} alt="" loading="lazy" className="h-full w-full object-cover" />
                                                ) : (
                                                    <GalleryGlyph mood={artwork.mood} index={index} />
                                                )}
                                            </span>
                                            <span className="min-w-0">
                                                <span className="block truncate text-sm font-bold" style={{ color: NEO.ink }}>
                                                    <span aria-hidden="true" className="font-jp mr-1.5" style={{ color: NEO.pink }}>{artwork.roomKanji}</span>
                                                    {artwork.title}
                                                </span>
                                                <span className="mt-1 block truncate text-xs" style={{ color: NEO.dim }}>
                                                    {artwork.mood.name} · {artwork.customMusicUrl ? 'Musique importée' : artwork.track.name}
                                                    {artwork.guideAudioUrl ? ' · Guide' : ''}
                                                </span>
                                            </span>
                                        </button>
                                    ))}
                                </div>
                            </section>
                        )}

                        {canEdit && (
                            <section className="nb-im-panel p-4">
                                <p className="nb-im-kicker">Thèmes par œuvre</p>
                                <div className="mt-4 grid grid-cols-2 gap-2">
                                    {moods.map((mood) => (
                                        <button
                                            key={mood.id}
                                            type="button"
                                            onClick={() => setSelectedMoodId(mood.id)}
                                            className="border-2 p-3 text-left transition"
                                            style={{
                                                borderColor: mood.id === selectedMoodId ? NEO.cyan : '#3B1F5E',
                                                background: mood.id === selectedMoodId ? 'rgba(5,217,232,.12)' : 'rgba(7,1,15,.4)',
                                            }}
                                            aria-pressed={mood.id === selectedMoodId}
                                        >
                                            <span className="mb-3 flex gap-1.5" aria-hidden="true">
                                                <span className="h-3 w-6" style={{ backgroundColor: mood.accent }} />
                                                <span className="h-3 w-6" style={{ backgroundColor: mood.second }} />
                                            </span>
                                            <span className="block text-sm font-bold" style={{ color: NEO.ink }}>{mood.name}</span>
                                            <span className="mt-1 block text-xs" style={{ color: NEO.dim }}>{mood.tag}</span>
                                        </button>
                                    ))}
                                </div>
                            </section>
                        )}

                        {canEdit && artworks.length > 0 && (
                            <section className="nb-im-panel p-4">
                                <p className="nb-im-kicker">Stickers de visite</p>
                                <div className="mt-4 grid grid-cols-4 gap-2">
                                    {stickerSet.map((sticker) => (
                                        <button
                                            key={sticker}
                                            type="button"
                                            onClick={() => dropSticker(sticker)}
                                            className="grid h-12 place-items-center border-2 text-lg font-black transition hover:-translate-y-0.5"
                                            style={{ borderColor: '#3B1F5E', background: 'rgba(7,1,15,.4)', color: NEO.yellow }}
                                            aria-label={`Ajouter un sticker ${stickerLabels[sticker]}`}
                                            title={`Ajouter un sticker ${stickerLabels[sticker]}`}
                                        >
                                            <span aria-hidden="true">{stickerGlyphs[sticker]}</span>
                                        </button>
                                    ))}
                                </div>
                            </section>
                        )}

                        {(favoritesCount > 0 || followersCount > 0) && (
                            <section className="nb-im-panel p-4">
                                <p className="nb-im-kicker">Statistiques</p>
                                <div className="mt-4 flex flex-wrap gap-2">
                                    {favoritesCount > 0 && (
                                        <span className="border-2 px-3 py-2 text-sm font-bold" style={{ borderColor: NEO.pink, color: NEO.ink }} title="Favoris sur cette œuvre">
                                            <span aria-hidden="true" style={{ color: NEO.pink }}>♥ </span>{pluralFr(favoritesCount, 'favori')}
                                        </span>
                                    )}
                                    {followersCount > 0 && (
                                        <span className="border-2 px-3 py-2 text-sm font-bold" style={{ borderColor: NEO.cyan, color: NEO.ink }} title="Abonnés de l’artiste">
                                            <span aria-hidden="true" style={{ color: NEO.cyan }}>● </span>{pluralFr(followersCount, 'abonné')}
                                        </span>
                                    )}
                                </div>
                            </section>
                        )}

                        <section className="nb-im-panel p-4" style={{ borderColor: NEO.cyan }}>
                            <p className="nb-im-kicker">Artiste</p>
                            <p className="font-bebas mt-2 text-3xl leading-none">
                                <a href={artist.profileUrl} style={{ color: NEO.ink, textDecoration: 'none' }}>{artistName}</a>
                            </p>
                            {artist.bio && (
                                <p className="mt-3 text-sm leading-7" style={{ color: NEO.muted }}>{artist.bio}</p>
                            )}
                            {socialLinks.length > 0 && (
                                <div className="mt-4 flex flex-wrap gap-2">
                                    {socialLinks.map((link) => (
                                        <a key={link.label} href={link.url} target="_blank" rel="noopener noreferrer" className="nb-im-btn">
                                            {link.label} <span aria-hidden="true">↗</span>
                                        </a>
                                    ))}
                                </div>
                            )}
                            <div className="mt-4 grid gap-2">
                                {follow ? (
                                    <form method="POST" action={follow.action}>
                                        <input type="hidden" name="_token" value={csrfToken} />
                                        {isFollowing && <input type="hidden" name="_method" value="DELETE" />}
                                        <button type="submit" className={`nb-im-btn w-full ${isFollowing ? '' : 'nb-im-btn--pink'}`} aria-pressed={isFollowing}>
                                            {isFollowing ? 'Ne plus suivre' : 'Suivre cet artiste'}
                                        </button>
                                    </form>
                                ) : (
                                    <a href={loginUrl} className="nb-im-btn nb-im-btn--pink w-full">
                                        Connectez-vous pour suivre
                                    </a>
                                )}

                                {favoriteAction ? (
                                    <form method="POST" action={favoriteAction}>
                                        <input type="hidden" name="_token" value={csrfToken} />
                                        {favoriteMethod === 'DELETE' && <input type="hidden" name="_method" value="DELETE" />}
                                        <button type="submit" className="nb-im-btn nb-im-btn--solid w-full" aria-pressed={!!activeArtwork?.isFavorite}>
                                            <span aria-hidden="true">♥</span>&nbsp;{activeArtwork?.isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'}
                                        </button>
                                    </form>
                                ) : loginUrl && artworks.length > 0 ? (
                                    <a href={loginUrl} className="nb-im-btn nb-im-btn--solid w-full">
                                        Connectez-vous pour ajouter aux favoris
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
