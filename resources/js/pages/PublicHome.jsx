import React from 'react';

const typeLabels = {
    document: {
        sectionTitle: 'Littérature',
        sectionText: 'Romans, récits et textes mis en avant dans un ordre qui change chaque jour.',
        emptyText: "Aucune œuvre littéraire publiée pour l'instant.",
        accent: '#2f6b4f',
        border: '#d8e2d6',
    },
};

const DragonGlyph = ({ className = '' }) => (
    <svg viewBox="0 0 120 120" aria-hidden="true" className={className}>
        <defs>
            <linearGradient id="homeDragonGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stopColor="#f8d86f" />
                <stop offset="45%" stopColor="#c084fc" />
                <stop offset="100%" stopColor="#7a669f" />
            </linearGradient>
        </defs>
        <path
            d="M86 18c-17 2-31 12-38 26-7 13-8 28-2 41l-18 17 23-4c10 8 25 12 40 8 13-4 22-14 27-26-8 6-18 8-29 6 6-3 11-8 15-14-9 2-18 1-26-4 12-1 21-7 26-17-8 2-15 1-21-2l3-11Z"
            fill="url(#homeDragonGradient)"
            opacity="0.95"
        />
        <circle cx="83" cy="39" r="4" fill="#fff7ed" />
    </svg>
);

const FireballGlyph = ({ className = '' }) => (
    <svg viewBox="0 0 120 120" aria-hidden="true" className={className}>
        <defs>
            <linearGradient id="homeFireGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stopColor="#fff2b2" />
                <stop offset="35%" stopColor="#ffb703" />
                <stop offset="70%" stopColor="#ff7b54" />
                <stop offset="100%" stopColor="#c084fc" />
            </linearGradient>
        </defs>
        <path
            d="M67 14c4 15-3 23-11 32 11-2 20 6 20 17 0 11-9 19-20 19s-20-8-20-19c0-9 5-15 13-23 8-9 13-14 18-26Z"
            fill="url(#homeFireGradient)"
        />
        <path
            d="M58 48c7 7 9 14 5 21-3 6-9 10-16 10-9 0-16-7-16-16 0-7 4-12 10-17-1 8 3 13 10 15-2-6 0-10 7-13Z"
            fill="#fff7ed"
            opacity="0.82"
        />
    </svg>
);

const MoonGlyph = ({ className = '' }) => (
    <svg viewBox="0 0 120 120" aria-hidden="true" className={className}>
        <circle cx="62" cy="58" r="34" fill="#fff2c4" opacity="0.95" />
        <circle cx="78" cy="48" r="28" fill="#d9b8ff" opacity="0.55" />
        <circle cx="73" cy="45" r="28" fill="#fff8ef" />
    </svg>
);

const StarGlyph = ({ className = '' }) => (
    <svg viewBox="0 0 120 120" aria-hidden="true" className={className}>
        <path
            d="M60 10 68 52 110 60 68 68 60 110 52 68 10 60 52 52Z"
            fill="#fff7ed"
            opacity="0.92"
        />
        <circle cx="60" cy="60" r="8" fill="#ffcf5a" />
    </svg>
);

const MagicBookGlyph = ({ className = '' }) => (
    <svg viewBox="0 0 180 140" aria-hidden="true" className={className}>
        <defs>
            <linearGradient id="homeBookCover" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stopColor="#7a669f" />
                <stop offset="55%" stopColor="#c084fc" />
                <stop offset="100%" stopColor="#ffb703" />
            </linearGradient>
        </defs>
        <path d="M28 42c0-10 8-18 18-18h37c10 0 18 8 18 18v64H46c-10 0-18 8-18 18V42Z" fill="url(#homeBookCover)" />
        <path d="M152 42c0-10-8-18-18-18H97c-10 0-18 8-18 18v82c0-10 8-18 18-18h55V42Z" fill="#fff7ed" opacity="0.96" />
        <path d="M90 24v94" stroke="#f4d8ff" strokeWidth="4" strokeLinecap="round" />
        <path d="M48 56h34M48 72h26M109 56h25M109 72h31" stroke="#7a669f" strokeWidth="4" strokeLinecap="round" opacity="0.5" />
        <circle cx="136" cy="26" r="8" fill="#ffb703" opacity="0.95" />
        <path d="M136 8v10M136 34v10M118 26h10M144 26h10" stroke="#fff7ed" strokeWidth="4" strokeLinecap="round" />
    </svg>
);

const PublicHome = ({
    illustrations = [],
    documents = [],
    illustrationsIndexUrl = '#illustrations',
    literaryWorksIndexUrl = '#litterature',
    featuredArtist = null,
    featuredWriter = null,
}) => {
    return (
        <div className="min-h-screen bg-[#fbf7ef] text-[#201a16]">
            <HomeIntro featuredArtist={featuredArtist} featuredWriter={featuredWriter} />

            <IllustrationSection works={illustrations} indexUrl={illustrationsIndexUrl} />

            <LiterarySection works={documents} indexUrl={literaryWorksIndexUrl} />
        </div>
    );
};

const HomeIntro = ({ featuredArtist, featuredWriter }) => (
    <section className="relative overflow-hidden text-[#181818]">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_82%_18%,rgba(255,183,3,0.24),transparent_18%),radial-gradient(circle_at_18%_74%,rgba(126,207,154,0.16),transparent_20%),linear-gradient(135deg,rgba(255,255,255,0.96)_0%,rgba(238,238,238,0.9)_45%,rgba(246,236,255,0.72)_100%)]" />
        <div className="absolute -left-12 top-24 h-32 w-32 rotate-12 rounded-[2rem] border-2 border-[#ff7b54] bg-white/60" />
        <div className="absolute right-10 top-20 h-16 w-16 rounded-full bg-[#14b8a6]" />
        <div className="absolute right-28 top-40 h-6 w-28 -rotate-12 bg-[#ffb703]" />
        <div className="absolute bottom-24 left-10 h-5 w-20 rotate-6 bg-[#ef476f]" />
        <div className="absolute bottom-10 left-1/3 h-24 w-24 rounded-full bg-[#c084fc]/25 blur-2xl" />
        <MoonGlyph className="absolute right-10 top-8 h-24 w-24 opacity-75 md:right-20 md:h-32 md:w-32" />
        <StarGlyph className="absolute left-1/3 top-14 hidden h-8 w-8 opacity-75 md:block" />
        <DragonGlyph className="absolute left-4 top-24 h-28 w-28 opacity-55 md:left-10 md:h-36 md:w-36" />
        <FireballGlyph className="absolute right-24 top-28 h-16 w-16 opacity-70 md:h-20 md:w-20" />

        <div className="float-soft absolute left-10 top-44 hidden -rotate-6 rounded-[1.4rem] border-2 border-black/15 bg-white px-5 py-3 shadow-[0_14px_34px_rgba(0,0,0,0.08)] lg:block">
            <p className="text-[10px] font-black uppercase tracking-[0.34em] text-[#ef476f]">Neo Tokyo</p>
            <p className="mt-1 text-xl font-black uppercase leading-none text-[#181818]">PIXEL</p>
            <p className="text-sm font-semibold uppercase tracking-[0.24em] text-[#14b8a6]">Gallery</p>
        </div>
        <div className="float-soft absolute right-12 top-52 hidden rotate-[8deg] rounded-[1.4rem] border-2 border-black/15 bg-[#304438] px-5 py-3 text-white shadow-[0_18px_40px_rgba(48,68,56,0.2)] lg:block">
            <p className="text-[10px] font-black uppercase tracking-[0.34em] text-[#ffb703]">Zouglou Notes</p>
            <p className="mt-1 text-xl font-black uppercase leading-none">Livre ouvert</p>
            <p className="text-sm font-semibold uppercase tracking-[0.24em] text-[#f4d8ff]">Babi Pages</p>
        </div>
        <div className="absolute left-1/2 top-20 hidden rounded-full border border-black/10 bg-white/85 px-4 py-2 shadow-[0_10px_24px_rgba(0,0,0,0.08)] md:block">
            <span className="text-[10px] font-black uppercase tracking-[0.28em] text-[#14b8a6]">Creative Feed</span>
        </div>
        <div className="float-soft absolute bottom-24 right-24 hidden -rotate-[10deg] rounded-[1.3rem] border-2 border-black/10 bg-white px-4 py-3 shadow-[0_16px_30px_rgba(0,0,0,0.08)] md:block">
            <p className="text-[10px] font-black uppercase tracking-[0.32em] text-[#ff7b54]">Maquis Canvas</p>
            <div className="mt-2 flex gap-2">
                <span className="h-3 w-8 bg-[#ef476f]" />
                <span className="h-3 w-12 bg-[#ffb703]" />
                <span className="h-3 w-6 bg-[#14b8a6]" />
            </div>
        </div>

        <div className="relative mx-auto max-w-6xl px-6 py-10">
            <div className="relative overflow-hidden rounded-[2rem] border border-white/50 bg-white/80 p-6 shadow-[0_28px_90px_rgba(89,57,138,0.16)] backdrop-blur md:p-8">
                <MagicBookGlyph className="pointer-events-none absolute -bottom-5 right-4 hidden h-28 w-36 opacity-70 md:block md:h-36 md:w-48" />
                <div className="absolute right-6 top-6 hidden gap-3 lg:flex">
                    <div className="rounded-[1.2rem] border border-black/10 bg-[#181818] px-4 py-3 text-white">
                        <p className="text-[10px] font-black uppercase tracking-[0.28em] text-[#ffb703]">City Pop</p>
                        <p className="mt-1 text-sm font-black uppercase">Abidjan</p>
                    </div>
                    <div className="rounded-[1.2rem] border border-black/10 bg-white px-4 py-3">
                        <p className="text-[10px] font-black uppercase tracking-[0.28em] text-[#7a669f]">Babi Plume</p>
                        <p className="mt-1 text-sm font-black uppercase text-[#181818]">Nouchi Signal</p>
                    </div>
                </div>

                <span className="inline-flex w-fit rounded-full border border-black/10 bg-[#f6f6f6] px-4 py-1 text-xs uppercase tracking-[0.28em] text-[#4b4b4b]">
                    Vitrine créative
                </span>
                <h1 className="home-banner-title font-display mt-5 max-w-3xl text-3xl font-bold leading-tight text-[#181818] md:text-5xl">
                    Art visuel et littérature dans une même vitrine créative
                </h1>

                <div className="home-banner-actions mt-6 flex flex-wrap gap-2">
                    <a href="#illustrations" className="motion-soft rounded-full bg-[#181818] px-4 py-2 text-xs font-bold text-white shadow-[0_14px_28px_rgba(0,0,0,0.12)] transition-all hover:-translate-y-0.5 hover:bg-[#2b2b2b]">
                        Découvrir les illustrations
                    </a>
                    <a href="#litterature" className="motion-soft rounded-full bg-gradient-to-r from-[#7a669f] via-[#c084fc] to-[#ffb703] px-4 py-2 text-xs font-bold text-white shadow-[0_14px_28px_rgba(122,102,159,0.2)] transition-all hover:-translate-y-0.5">
                        Découvrir les œuvres littéraires
                    </a>
                </div>
            </div>

            <div className="mt-6 grid gap-4 lg:grid-cols-2">
                <FeaturedSpotlightCard type="artist" feature={featuredArtist} />
                <FeaturedSpotlightCard type="writer" feature={featuredWriter} />
            </div>
        </div>
    </section>
);

const Hero = () => (
    <section className="border-b border-[#e8ddcf] bg-[#fffdf8]">
        <div className="mx-auto max-w-6xl px-6 py-6">
            <div className="flex flex-wrap items-center justify-between gap-4">
                <h1 className="home-banner-title font-display max-w-2xl text-2xl font-bold leading-tight text-[#201a16] md:text-4xl">
                    Art visuel et littérature dans une même vitrine créative
                </h1>

                <div className="home-banner-actions flex flex-wrap gap-2">
                    <a href="#illustrations" className="rounded-full bg-[#9a4f2c] px-4 py-2 text-xs font-bold text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-[#7e3f23] hover:shadow-md">
                        Découvrir les illustrations
                    </a>
                    <a href="#litterature" className="rounded-full border border-[#2f6b4f]/30 bg-white px-4 py-2 text-xs font-bold text-[#2f6b4f] shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-[#f3f7f1] hover:shadow-md">
                        Découvrir les œuvres littéraires
                    </a>
                </div>
            </div>
        </div>
    </section>
);

const DailyFeatures = ({ featuredArtist, featuredWriter }) => (
    <section className="mx-auto max-w-6xl px-6 pb-2 pt-6">
        <div className="grid gap-4 lg:grid-cols-2">
            <FeatureCard type="artist" feature={featuredArtist} />
            <FeatureCard type="writer" feature={featuredWriter} />
        </div>
    </section>
);

const FeaturedSpotlightCard = ({ type, feature }) => {
    const isArtist = type === 'artist';
    const label = isArtist ? 'Artiste du jour' : 'Écrivain du jour';
    const imageUrl = isArtist ? feature?.highlightImageUrl : feature?.highlight?.coverImageUrl;
    const count = isArtist ? feature?.illustrationsCount || 0 : feature?.documentsCount || 0;
    const countLabel = isArtist ? 'illustration(s)' : 'œuvre(s)';
    const fallbackTitle = isArtist ? 'Illustrateur à découvrir' : 'Auteur à découvrir';
    const fallbackBio = isArtist
        ? "Chaque jour, un profil visuel prend la scène sur la page d'accueil."
        : 'Chaque jour, une plume prend la lumière avec un texte et un univers à explorer.';
    const cardClass = isArtist
        ? 'border-black/10 bg-white shadow-[0_18px_56px_rgba(0,0,0,0.08)]'
        : 'border-[#e7d5f5] bg-[linear-gradient(180deg,rgba(255,251,246,0.92),rgba(250,243,255,0.9))] shadow-[0_18px_56px_rgba(122,102,159,0.14)]';
    const mediaClass = isArtist ? 'bg-[#f5f5f5]' : 'bg-[#faf8ff]';
    const accent = isArtist ? '#ef476f' : '#7a669f';

    return (
        <a href={feature?.profileUrl || '#'} className={`motion-soft group relative block overflow-hidden rounded-[1.7rem] border transition-all hover:-translate-y-1 ${cardClass}`}>
            <div className="absolute right-4 top-4 z-10 hidden rounded-full border border-black/10 bg-white/80 px-3 py-1 lg:block">
                <span className="text-[10px] font-black uppercase tracking-[0.24em]" style={{ color: accent }}>
                    {isArtist ? 'Babi Art Line' : 'Babi Plume'}
                </span>
            </div>
            <div className="absolute bottom-4 right-5 z-10 hidden -rotate-6 rounded-[1rem] border border-black/10 bg-white/80 px-3 py-2 lg:block">
                <span className="text-[10px] font-black uppercase tracking-[0.24em]" style={{ color: isArtist ? '#14b8a6' : '#b46d46' }}>
                    {isArtist ? 'Garba Energy' : 'Maquis Manuscrit'}
                </span>
            </div>
            <div className="grid gap-0 md:grid-cols-[0.7fr_1fr]">
                <div className={`min-h-[155px] ${mediaClass}`}>
                    {imageUrl ? (
                        <img src={imageUrl} alt={feature?.name || label} className="motion-soft h-full w-full object-cover transition-transform group-hover:scale-[1.03]" loading="lazy" />
                    ) : (
                        <div className="flex h-full items-center justify-center p-6 text-center">
                            <div>
                                <p className="text-[10px] font-black uppercase tracking-[0.28em]" style={{ color: accent }}>
                                    {label}
                                </p>
                                <p className="mt-2 text-2xl font-black uppercase leading-none text-[#181818]">
                                    {isArtist ? 'Studio' : 'Récit'}
                                </p>
                            </div>
                        </div>
                    )}
                </div>
                <div className="p-5">
                    <span className="inline-flex rounded-full border border-black/10 bg-white/75 px-3 py-1 text-[0.68rem] font-black uppercase tracking-[0.18em]" style={{ color: accent }}>
                        {label}
                    </span>
                    <h2 className="mt-3 text-xl font-bold text-[#201a16]">
                        {feature?.name || fallbackTitle}
                    </h2>
                    <p className="mt-2 line-clamp-2 text-sm leading-6 text-[#525252]">
                        {feature?.bio || fallbackBio}
                    </p>
                    <div className="mt-4 flex items-center justify-between gap-3">
                        <span className="rounded-full border border-black/10 bg-white/80 px-3 py-1.5 text-[0.68rem] text-[#4b4b4b]">
                            {count} {countLabel}
                        </span>
                        <span className="rounded-full px-4 py-2 text-xs font-semibold text-white" style={{ backgroundColor: isArtist ? '#181818' : '#304438' }}>
                            Voir le profil
                        </span>
                    </div>
                </div>
            </div>
        </a>
    );
};

const FeatureCard = ({ type, feature }) => {
    const isArtist = type === 'artist';
    const label = isArtist ? 'Artiste du jour' : 'Écrivain du jour';
    const accent = isArtist ? '#9a4f2c' : '#2f6b4f';
    const imageUrl = isArtist ? feature?.highlightImageUrl : feature?.highlight?.coverImageUrl;
    const count = isArtist ? feature?.illustrationsCount || 0 : feature?.documentsCount || 0;
    const countLabel = isArtist ? 'illustration(s)' : 'œuvre(s)';

    return (
        <a href={feature?.profileUrl || '#'} className="block overflow-hidden rounded-xl border border-[#e8ddcf] bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div className="grid gap-0 md:grid-cols-[0.78fr_1fr]">
                <div className="min-h-[170px] bg-[#efe4d6]">
                    {imageUrl ? (
                        <img src={imageUrl} alt={feature?.name || label} className="h-full w-full object-cover" loading="lazy" />
                    ) : (
                        <div className="flex h-full items-center justify-center p-6 text-center text-xs font-semibold uppercase tracking-[0.18em]" style={{ color: accent }}>
                            {label}
                        </div>
                    )}
                </div>
                <div className="p-4">
                    <span className="inline-flex rounded-full border border-[#e8ddcf] bg-[#fbf7ef] px-2.5 py-1 text-[0.68rem] uppercase tracking-[0.18em]" style={{ color: accent }}>
                        {label}
                    </span>
                    <h2 className="mt-3 text-lg font-bold text-[#201a16]">
                        {feature?.name || (isArtist ? 'Illustrateur à découvrir' : 'Auteur à découvrir')}
                    </h2>
                    <p className="mt-2 line-clamp-2 text-xs leading-5 text-[#6a5a4d]">
                        {feature?.bio || (isArtist ? "Chaque jour, un profil visuel prend la scène sur la page d'accueil." : 'Chaque jour, une plume prend la lumière avec un texte et un univers à explorer.')}
                    </p>
                    <div className="mt-4 flex items-center justify-between gap-3">
                        <span className="rounded-full bg-[#fbf7ef] px-2.5 py-1.5 text-[0.68rem] text-[#6a5a4d]">
                            {count} {countLabel}
                        </span>
                        <span className="text-xs font-semibold" style={{ color: accent }}>Voir le profil</span>
                    </div>
                </div>
            </div>
        </a>
    );
};

const IllustrationSection = ({ works, indexUrl }) => (
    <section id="illustrations" className="relative mt-8 overflow-hidden text-[#181818]">
        <div className="absolute inset-0 bg-[linear-gradient(135deg,rgba(255,255,255,0.96),rgba(238,238,238,0.94))]" />
        <div className="absolute -left-12 top-24 h-32 w-32 rotate-12 rounded-[2rem] border-2 border-[#ff7b54] bg-white/60" />
        <div className="absolute right-10 top-20 h-16 w-16 rounded-full bg-[#14b8a6]" />
        <div className="absolute right-28 top-40 h-6 w-28 -rotate-12 bg-[#ffb703]" />
        <div className="absolute bottom-24 left-10 h-5 w-20 rotate-6 bg-[#ef476f]" />

        <div className="absolute left-10 top-44 hidden -rotate-6 rounded-[1.4rem] border-2 border-black/15 bg-white px-5 py-3 shadow-[0_14px_34px_rgba(0,0,0,0.08)] lg:block">
            <p className="text-[10px] font-black uppercase tracking-[0.34em] text-[#ef476f]">Neo Tokyo</p>
            <p className="mt-1 text-xl font-black uppercase leading-none text-[#181818]">PIXEL</p>
            <p className="text-sm font-semibold uppercase tracking-[0.24em] text-[#14b8a6]">Gallery</p>
        </div>
        <div className="absolute right-12 top-52 hidden rotate-[8deg] rounded-[1.4rem] border-2 border-black/15 bg-[#181818] px-5 py-3 text-white shadow-[0_18px_40px_rgba(0,0,0,0.18)] lg:block">
            <p className="text-[10px] font-black uppercase tracking-[0.34em] text-[#ffb703]">Live Drop</p>
            <p className="mt-1 text-xl font-black uppercase leading-none text-[#f5f5f5]">KANVA</p>
            <p className="text-sm font-semibold uppercase tracking-[0.24em] text-[#ef476f]">Signal 09</p>
        </div>
        <div className="absolute left-1/3 top-16 hidden rounded-full border border-black/10 bg-white/85 px-4 py-2 shadow-[0_10px_24px_rgba(0,0,0,0.08)] md:block">
            <span className="text-[10px] font-black uppercase tracking-[0.28em] text-[#14b8a6]">Creative Feed</span>
        </div>
        <div className="absolute bottom-20 right-24 hidden -rotate-[10deg] rounded-[1.3rem] border-2 border-black/10 bg-white px-4 py-3 shadow-[0_16px_30px_rgba(0,0,0,0.08)] md:block">
            <p className="text-[10px] font-black uppercase tracking-[0.32em] text-[#ff7b54]">Street Mode</p>
            <div className="mt-2 flex gap-2">
                <span className="h-3 w-8 bg-[#ef476f]" />
                <span className="h-3 w-12 bg-[#ffb703]" />
                <span className="h-3 w-6 bg-[#14b8a6]" />
            </div>
        </div>

        <div className="relative mx-auto max-w-6xl px-6 py-12">
            <div className="relative rounded-[2rem] border border-black/10 bg-white/88 p-6 shadow-[0_28px_80px_rgba(0,0,0,0.08)] backdrop-blur md:p-8">
                <div className="absolute right-6 top-6 hidden gap-3 lg:flex">
                    <div className="rounded-[1.2rem] border border-black/10 bg-[#181818] px-4 py-3 text-white">
                        <p className="text-[10px] font-black uppercase tracking-[0.28em] text-[#ffb703]">City Pop</p>
                        <p className="mt-1 text-sm font-black uppercase">Abidjan</p>
                    </div>
                    <div className="rounded-[1.2rem] border border-black/10 bg-white px-4 py-3">
                        <p className="text-[10px] font-black uppercase tracking-[0.28em] text-[#14b8a6]">District</p>
                        <p className="mt-1 text-sm font-black uppercase text-[#181818]">Shibuya Line</p>
                    </div>
                </div>

                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <span className="inline-flex w-fit items-center gap-3 rounded-full border border-black/10 bg-[#f6f6f6] px-4 py-1 text-xs uppercase tracking-[0.28em] text-[#4b4b4b]">
                            Galerie artiste
                        </span>
                        <h2 className="mt-4 text-3xl font-semibold uppercase tracking-[-0.04em] text-[#141414] md:text-5xl">
                            Illustrations
                        </h2>
                        <p className="mt-3 max-w-2xl text-sm leading-7 text-[#525252]">
                            Une sélection vivante d'œuvres visuelles, présentée avec l'énergie du studio illustrateur.
                        </p>
                    </div>
                    <a href={indexUrl} className="rounded-full bg-[#181818] px-5 py-3 text-sm font-semibold text-white shadow-[0_16px_34px_rgba(0,0,0,0.14)] transition hover:-translate-y-0.5">
                        Voir plus
                    </a>
                </div>

                {works.length === 0 && (
                    <div className="mt-8 rounded-[1.2rem] border border-dashed border-orange-200 bg-[#fffaf4] p-4 text-sm text-[#6f5c75]">
                        Aucune illustration publiée pour l'instant.
                    </div>
                )}

                <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {works.map((work) => (
                        <a
                            key={work.id}
                            href={work.detailUrl}
                            className="group overflow-hidden rounded-[1.55rem] border border-black/10 bg-white shadow-[0_16px_42px_rgba(0,0,0,0.06)] transition hover:-translate-y-1"
                        >
                            <img
                                src={work.imageUrl}
                                alt={work.title}
                                className="aspect-square w-full bg-[#f5f5f5] object-contain transition duration-300 group-hover:scale-105"
                                loading="lazy"
                            />
                            <div className="space-y-3 p-4">
                                <p className="text-sm font-semibold text-[#181818]">{work.title}</p>
                                <p className="text-xs font-semibold uppercase tracking-[0.18em] text-[#ef476f]">{work.artistName}</p>
                                <span className="inline-flex w-full justify-center rounded-xl bg-[#181818] px-3 py-2 text-xs font-semibold text-white transition group-hover:bg-[#2b2b2b]">
                                    Voir l'œuvre
                                </span>
                            </div>
                        </a>
                    ))}
                </div>
            </div>
        </div>
    </section>
);

const LiterarySection = ({ works, indexUrl }) => {
    const labels = typeLabels.document;

    return (
        <section id="litterature" className="relative overflow-hidden text-[#2f3a31]">
            <div className="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(255,247,237,0.26),_transparent_24%),radial-gradient(circle_at_82%_18%,_rgba(255,183,3,0.24),_transparent_18%),radial-gradient(circle_at_20%_78%,_rgba(126,207,154,0.18),_transparent_20%),linear-gradient(180deg,rgba(122,102,159,0.9)_0%,rgba(192,132,252,0.58)_24%,rgba(252,246,255,0.74)_58%,rgba(255,217,128,0.7)_100%)]" />
            <div className="absolute left-8 top-16 h-28 w-28 rounded-full bg-[#7ecf9a]/15 blur-2xl" />
            <div className="absolute right-16 top-12 h-36 w-36 rounded-full bg-[#ffb703]/20 blur-3xl" />
            <div className="absolute bottom-10 left-1/3 h-24 w-24 rounded-full bg-[#c084fc]/25 blur-2xl" />
            <MoonGlyph className="absolute right-10 top-8 h-24 w-24 opacity-80 md:right-20 md:h-32 md:w-32" />
            <StarGlyph className="absolute left-1/3 top-14 hidden h-8 w-8 opacity-75 md:block" />
            <StarGlyph className="absolute right-1/4 top-36 hidden h-6 w-6 opacity-70 md:block" />
            <StarGlyph className="absolute bottom-28 left-16 hidden h-7 w-7 opacity-70 md:block" />
            <DragonGlyph className="absolute left-4 top-24 h-28 w-28 opacity-70 md:left-10 md:h-36 md:w-36" />
            <DragonGlyph className="absolute bottom-24 right-6 h-20 w-20 rotate-[18deg] opacity-60 md:right-14 md:h-28 md:w-28" />
            <FireballGlyph className="absolute right-24 top-28 h-16 w-16 opacity-80 md:h-20 md:w-20" />
            <FireballGlyph className="absolute bottom-36 left-1/4 h-14 w-14 opacity-70 md:h-16 md:w-16" />

            <div className="relative mx-auto max-w-6xl px-6 py-12">
                <div className="relative rounded-[2rem] border border-white/40 bg-[linear-gradient(135deg,rgba(255,250,245,0.84),rgba(246,236,255,0.68))] p-6 shadow-[0_28px_90px_rgba(89,57,138,0.24)] backdrop-blur md:p-8">
                    <MagicBookGlyph className="pointer-events-none absolute -bottom-4 right-4 hidden h-28 w-36 opacity-85 md:block md:h-36 md:w-48" />
                    <div className="relative flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <span className="inline-flex rounded-full border border-[#c9b9dc] bg-white/75 px-4 py-1 text-xs uppercase tracking-[0.28em] text-[#7a669f]">
                                Espace auteur
                            </span>
                            <h2 className="mt-5 text-3xl font-semibold tracking-[-0.04em] text-[#304438] md:text-5xl">
                                {labels.sectionTitle}
                            </h2>
                            <p className="mt-4 max-w-2xl text-sm leading-7 text-[#5b6f63]">
                                {labels.sectionText}
                            </p>
                        </div>
                        <a href={indexUrl} className="rounded-full bg-[#304438] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5">
                            Voir plus
                        </a>
                    </div>
                </div>

                {works.length === 0 && (
                    <div className="mt-8 rounded-[1.5rem] border border-[#e7d5f5] bg-white/85 p-8 text-center text-[#5b5670] shadow-[0_18px_56px_rgba(122,102,159,0.14)]">
                        {labels.emptyText}
                    </div>
                )}

                <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {works.map((work) => (
                        <a
                            key={work.id}
                            href={work.readUrl}
                            className="group overflow-hidden rounded-[1.5rem] border border-[#dbeee5] bg-white shadow-[0_16px_40px_rgba(45,212,191,0.12)] transition hover:-translate-y-1"
                        >
                            <img
                                src={work.coverImageUrl}
                                alt={work.title}
                                className="h-48 w-full bg-[#faf8ff] object-cover transition duration-300 group-hover:scale-[1.03]"
                                loading="lazy"
                            />
                            <div className="space-y-3 p-4">
                                <div className="flex items-center justify-between gap-3">
                                    <p className="text-sm font-semibold text-[#21453f]">{work.title}</p>
                                    {work.fileType && (
                                        <span className="rounded-full bg-[#eef9f3] px-3 py-1 text-[11px] font-semibold text-[#147d6d]">
                                            {work.fileType}
                                        </span>
                                    )}
                                </div>
                                <p className="text-sm font-semibold text-[#53665a]">{work.writerName}</p>
                                {work.description && (
                                    <p className="line-clamp-2 text-sm leading-6 text-[#44645f]">{work.description}</p>
                                )}
                                <span className="inline-flex rounded-full bg-gradient-to-r from-[#7a669f] via-[#c084fc] to-[#ffb703] px-4 py-2 text-xs font-semibold text-white shadow-[0_16px_34px_rgba(122,102,159,0.22)]">
                                    Lire
                                </span>
                            </div>
                        </a>
                    ))}
                </div>
            </div>
        </section>
    );
};

export default PublicHome;
