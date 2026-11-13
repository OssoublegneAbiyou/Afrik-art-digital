import React, { useState } from 'react';

const formatStorage = (bytes = 0) => {
    if (bytes >= 1024 * 1024 * 1024) {
        return `${(bytes / (1024 * 1024 * 1024)).toFixed(2)} Go`;
    }

    if (bytes >= 1024 * 1024) {
        return `${(bytes / (1024 * 1024)).toFixed(1)} Mo`;
    }

    return `${Math.round(bytes / 1024)} Ko`;
};

const DragonGlyph = ({ className = '' }) => (
    <svg viewBox="0 0 120 120" aria-hidden="true" className={className}>
        <defs>
            <linearGradient id="dragonGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stopColor="#f8d86f" />
                <stop offset="45%" stopColor="#c084fc" />
                <stop offset="100%" stopColor="#7a669f" />
            </linearGradient>
        </defs>
        <path
            d="M86 18c-17 2-31 12-38 26-7 13-8 28-2 41l-18 17 23-4c10 8 25 12 40 8 13-4 22-14 27-26-8 6-18 8-29 6 6-3 11-8 15-14-9 2-18 1-26-4 12-1 21-7 26-17-8 2-15 1-21-2l3-11Z"
            fill="url(#dragonGradient)"
            opacity="0.95"
        />
        <circle cx="83" cy="39" r="4" fill="#fff7ed" />
    </svg>
);

const FireballGlyph = ({ className = '' }) => (
    <svg viewBox="0 0 120 120" aria-hidden="true" className={className}>
        <defs>
            <linearGradient id="fireGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stopColor="#fff2b2" />
                <stop offset="35%" stopColor="#ffb703" />
                <stop offset="70%" stopColor="#ff7b54" />
                <stop offset="100%" stopColor="#c084fc" />
            </linearGradient>
        </defs>
        <path
            d="M67 14c4 15-3 23-11 32 11-2 20 6 20 17 0 11-9 19-20 19s-20-8-20-19c0-9 5-15 13-23 8-9 13-14 18-26Z"
            fill="url(#fireGradient)"
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
            <linearGradient id="bookCover" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stopColor="#7a669f" />
                <stop offset="55%" stopColor="#c084fc" />
                <stop offset="100%" stopColor="#ffb703" />
            </linearGradient>
        </defs>
        <path
            d="M28 42c0-10 8-18 18-18h37c10 0 18 8 18 18v64H46c-10 0-18 8-18 18V42Z"
            fill="url(#bookCover)"
        />
        <path
            d="M152 42c0-10-8-18-18-18H97c-10 0-18 8-18 18v82c0-10 8-18 18-18h55V42Z"
            fill="#fff7ed"
            opacity="0.96"
        />
        <path d="M90 24v94" stroke="#f4d8ff" strokeWidth="4" strokeLinecap="round" />
        <path d="M48 56h34M48 72h26M109 56h25M109 72h31" stroke="#7a669f" strokeWidth="4" strokeLinecap="round" opacity="0.5" />
        <circle cx="136" cy="26" r="8" fill="#ffb703" opacity="0.95" />
        <path d="M136 8v10M136 34v10M118 26h10M144 26h10" stroke="#fff7ed" strokeWidth="4" strokeLinecap="round" />
    </svg>
);

const plural = (count, singular, pluralForm) => `${count} ${count >= 2 ? (pluralForm || `${singular}s`) : singular}`;

const Dashboard = ({
    accountType = 'artist',
    userName = '',
    bio = '',
    bannerUrl = null,
    illustrations = [],
    illustrationsCount = 0,
    remaining = 20,
    quota = 20,
    portfolios = [],
    documents = [],
    followedArtists = [],
    favoriteIllustrations = [],
    bookmarkedDocuments = [],
    storageLimitBytes = 0,
    storageUsedBytes = 0,
    storageLimitLabel = '',
    uploadAction = '',
    documentUploadAction = '',
    documentCreateUrl = '',
    documentGenres = {},
    illustrationTypes = {},
    profileAction = '',
    accountSettingsUrl = '',
    joinUrl = '',
    portfolioUrl = '',
    csrfToken = '',
    quotaError = '',
    storageError = '',
    uploadError = '',
    moderationEnabled = false,
    successMessage = '',
    social = {},
    theme = {},
}) => {
    const [artworkPreview, setArtworkPreview] = useState(null);
    const activeTheme = {
        background: theme.background || 'linear-gradient(180deg,#fff8ef 0%,#fffdf8 38%,#f4f8ff 100%)',
        panel: theme.panel || '#ffffff',
        text: theme.text || '#2b183d',
        accent: theme.accent || '#ef476f',
    };
    const rootStyle = {
        background: activeTheme.background,
        color: activeTheme.text,
    };
    const panelStyle = {
        backgroundColor: activeTheme.panel,
        color: activeTheme.text,
    };
    const accentStyle = {
        borderColor: activeTheme.accent,
        color: activeTheme.accent,
    };
    const quotaReached = illustrationsCount >= quota;
    const untypedDocuments = documents.filter((document) => !document.genre).length;
    const storageRatio = storageLimitBytes > 0
        ? Math.min(100, Math.round((storageUsedBytes / storageLimitBytes) * 100))
        : 0;

    if (accountType === 'visitor') {
        return (
            <div className="min-h-screen text-[#2b183d]" style={rootStyle}>
                <div className="relative overflow-hidden">
                    <div className="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(239,71,111,0.12),_transparent_28%),radial-gradient(circle_at_80%_18%,_rgba(20,184,166,0.14),_transparent_24%),radial-gradient(circle_at_20%_78%,_rgba(255,183,3,0.1),_transparent_24%)]" />
                    <div className="relative mx-auto max-w-6xl px-6 py-12">
                        <div className="rounded-[2rem] border border-white/70 bg-white/85 p-8 shadow-[0_24px_80px_rgba(121,91,255,0.08)] backdrop-blur" style={panelStyle}>
                            <span className="inline-flex rounded-full border bg-white/80 px-4 py-1 text-xs uppercase tracking-[0.28em]" style={accentStyle}>
                                Espace visiteur
                            </span>
                            <h1 className="mt-5 text-4xl font-semibold tracking-[-0.04em] text-[#2b183d] md:text-5xl">
                                Bonjour {userName || 'visiteur'}
                            </h1>
                            <p className="mt-4 max-w-2xl text-sm leading-7 text-[#6f5c75]">
                                Suivez les artistes que vous aimez et gardez vos illustrations préférées dans votre collection personnelle.
                            </p>
                            {accountSettingsUrl && (
                                <div className="mt-6">
                                    <a
                                        href={accountSettingsUrl}
                                        className="inline-flex rounded-full bg-[#2b183d] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5"
                                    >
                                        Modifier mon profil
                                    </a>
                                </div>
                            )}
                            {successMessage && (
                                <div className="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">
                                    {successMessage}
                                </div>
                            )}
                            {joinUrl && (
                                <p className="mt-6 rounded-[1.2rem] border border-dashed border-[#d9c8ff] bg-white/70 p-4 text-sm leading-7 text-[#4a3a63]">
                                    Envie de publier tes dessins ou tes textes ?{' '}
                                    <a href={joinUrl} className="inline-flex min-h-[44px] items-center font-semibold text-[#5b2a86] underline underline-offset-4">
                                        Demander à rejoindre le collectif
                                    </a>
                                </p>
                            )}
                        </div>

                        <div className="mt-10 grid gap-6 xl:grid-cols-3">
                            <section className="rounded-[1.8rem] border border-white/70 bg-white/85 p-6 shadow-[0_18px_50px_rgba(255,161,90,0.12)]">
                                <div className="flex items-center justify-between gap-4">
                                    <h2 className="text-xl font-semibold text-[#2b183d]">Artistes suivis</h2>
                                    <span className="rounded-full bg-[#fffaf4] px-4 py-2 text-xs text-[#7b627f]">
                                        {plural(followedArtists.length, 'artiste suivi', 'artistes suivis')}
                                    </span>
                                </div>
                                <div className="mt-6 grid gap-4">
                                    {followedArtists.length === 0 && (
                                        <p className="rounded-[1.2rem] border border-dashed border-orange-200 bg-[#fffaf4] p-4 text-sm text-[#6f5c75]">
                                            Vous ne suivez encore aucun artiste.
                                        </p>
                                    )}
                                    {followedArtists.map((artist) => (
                                        <a
                                            key={artist.id}
                                            href={artist.profileUrl}
                                            className="overflow-hidden rounded-[1.4rem] border border-orange-100 bg-[#fffaf4] transition hover:-translate-y-0.5"
                                        >
                                            {artist.bannerUrl && (
                                                <img src={artist.bannerUrl} alt={artist.name} className="h-28 w-full object-cover" />
                                            )}
                                            <div className="p-4">
                                                <p className="text-lg font-semibold text-[#2b183d]">{artist.name}</p>
                                                <p className="mt-2 text-sm text-[#6f5c75]">
                                                    {artist.bio || 'Un artiste que vous avez choisi de suivre.'}
                                                </p>
                                                <p className="mt-3 text-xs uppercase tracking-[0.22em] text-[#a03d5c]">
                                                    {plural(artist.illustrationsCount, 'illustration')}
                                                </p>
                                            </div>
                                        </a>
                                    ))}
                                </div>
                            </section>

                            <section className="rounded-[1.8rem] border border-white/70 bg-white/85 p-6 shadow-[0_18px_50px_rgba(45,212,191,0.12)]">
                                <div className="flex items-center justify-between gap-4">
                                    <h2 className="text-xl font-semibold text-[#21453f]">Illustrations favorites</h2>
                                    <span className="rounded-full bg-[#f7fff8] px-4 py-2 text-xs text-[#44645f]">
                                        {plural(favoriteIllustrations.length, 'favori')}
                                    </span>
                                </div>
                                <div className="mt-6 grid gap-4 sm:grid-cols-2">
                                    {favoriteIllustrations.length === 0 && (
                                        <p className="sm:col-span-2 rounded-[1.2rem] border border-dashed border-[#dbeee5] bg-[#f7fff8] p-4 text-sm text-[#44645f]">
                                            Vous n&apos;avez pas encore ajouté d&apos;illustration en favoris.
                                        </p>
                                    )}
                                    {favoriteIllustrations.map((illustration) => (
                                        <article key={illustration.id} className="overflow-hidden rounded-[1.4rem] border border-[#dbeee5] bg-[#f7fff8]">
                                            <button
                                                type="button"
                                                className="block w-full"
                                                onClick={() => setArtworkPreview(illustration)}
                                            >
                                                <img src={illustration.imageUrl} alt={illustration.title} className="aspect-square w-full bg-[#eef7ed] object-contain" />
                                            </button>
                                            <div className="space-y-3 p-4">
                                                <div>
                                                    <p className="text-base font-semibold text-[#21453f]">{illustration.title}</p>
                                                    <p className="mt-1 text-sm text-[#44645f]">{illustration.artistName}</p>
                                                </div>
                                                <div className="flex flex-wrap gap-3">
                                                    <button
                                                        type="button"
                                                        onClick={() => setArtworkPreview(illustration)}
                                                        className="rounded-full bg-[#21453f] px-4 py-2 text-xs font-semibold text-white shadow-sm"
                                                    >
                                                        Voir l&apos;œuvre
                                                    </button>
                                                    <a href={illustration.artistProfileUrl} className="rounded-full bg-white px-4 py-2 text-xs font-semibold text-[#21453f] shadow-sm">
                                                        Voir le profil
                                                    </a>
                                                    <form method="POST" action={illustration.unfavoriteUrl}>
                                                        <input type="hidden" name="_token" value={csrfToken} />
                                                        <input type="hidden" name="_method" value="DELETE" />
                                                        <button type="submit" className="rounded-full border border-red-200 bg-red-50 px-4 py-2 text-xs font-semibold text-red-700">
                                                            Retirer
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </article>
                                    ))}
                                </div>
                            </section>

                            <section id="bibliotheque" className="rounded-[1.8rem] border border-white/70 bg-white/85 p-6 shadow-[0_18px_50px_rgba(122,102,159,0.12)] xl:col-span-1">
                                <div className="flex items-center justify-between gap-4">
                                    <h2 className="text-xl font-semibold text-[#5e4b8b]">Livres marqués</h2>
                                    <span className="rounded-full bg-[#faf7ff] px-4 py-2 text-xs text-[#6d5aa8]">
                                        {plural(bookmarkedDocuments.length, 'marque-page')}
                                    </span>
                                </div>
                                <div className="mt-6 grid gap-4">
                                    {bookmarkedDocuments.length === 0 && (
                                        <p className="rounded-[1.2rem] border border-dashed border-[#e7dcff] bg-[#faf7ff] p-4 text-sm text-[#6d5aa8]">
                                            Vous n&apos;avez encore ajouté aucun livre à vos marque-pages.
                                        </p>
                                    )}
                                    {bookmarkedDocuments.map((document) => (
                                        <article key={document.id} className="overflow-hidden rounded-[1.4rem] border border-[#e7dcff] bg-[#faf7ff]">
                                            <img src={document.coverImageUrl} alt={document.title} className="h-40 w-full object-cover" />
                                            <div className="space-y-3 p-4">
                                                <div className="flex items-center justify-between gap-3">
                                                    <p className="text-base font-semibold text-[#5e4b8b]">{document.title}</p>
                                                    <span className="rounded-full bg-white px-3 py-1 text-[11px] font-semibold text-[#6d5aa8]">
                                                        {document.fileType}
                                                    </span>
                                                </div>
                                                <p className="text-sm text-[#6d5aa8]">{document.writerName}</p>
                                                <div className="flex flex-wrap gap-3">
                                                    <a href={document.writerProfileUrl} className="rounded-full bg-white px-4 py-2 text-xs font-semibold text-[#5e4b8b] shadow-sm">
                                                        Voir le profil
                                                    </a>
                                                    <a href={document.readUrl || document.fileUrl} className="rounded-full bg-white px-4 py-2 text-xs font-semibold text-[#5e4b8b] shadow-sm">
                                                        Lire
                                                    </a>
                                                    <form method="POST" action={document.unbookmarkUrl}>
                                                        <input type="hidden" name="_token" value={csrfToken} />
                                                        <input type="hidden" name="_method" value="DELETE" />
                                                        <button type="submit" className="rounded-full border border-red-200 bg-red-50 px-4 py-2 text-xs font-semibold text-red-700">
                                                            Retirer
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </article>
                                    ))}
                                </div>
                            </section>

                            {artworkPreview && (
                                <div
                                    className="fixed inset-0 z-[80] flex items-center justify-center bg-black/85 px-4 py-6"
                                    onClick={() => setArtworkPreview(null)}
                                >
                                    <div
                                        className="w-full max-w-6xl overflow-hidden rounded-2xl bg-[#111] shadow-2xl"
                                        onClick={(event) => event.stopPropagation()}
                                    >
                                        <div className="flex items-center justify-between gap-4 border-b border-white/10 px-5 py-4 text-white">
                                            <div className="min-w-0">
                                                <h2 className="truncate text-base font-semibold">{artworkPreview.title}</h2>
                                                {artworkPreview.artistName && (
                                                    <p className="mt-1 text-sm text-white/60">{artworkPreview.artistName}</p>
                                                )}
                                            </div>
                                            <button
                                                type="button"
                                                className="rounded-full bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/20"
                                                onClick={() => setArtworkPreview(null)}
                                            >
                                                Fermer
                                            </button>
                                        </div>
                                        <div className="flex max-h-[82vh] items-center justify-center bg-black p-4">
                                            <img src={artworkPreview.imageUrl} alt={artworkPreview.title} className="max-h-[78vh] max-w-full object-contain" />
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    if (accountType === 'writer') {
        return (
            <div className="min-h-screen text-[#2f3a31]" style={rootStyle}>
                <div className="relative overflow-hidden">
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
                    <div className="relative mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-12">
                        <div className="relative rounded-[1.6rem] border border-white/40 bg-[linear-gradient(135deg,rgba(255,250,245,0.84),rgba(246,236,255,0.68))] p-5 shadow-[0_28px_90px_rgba(89,57,138,0.24)] backdrop-blur sm:rounded-[2rem] sm:p-8" style={panelStyle}>
                            <span className="inline-flex rounded-full border bg-white/75 px-4 py-1 text-xs uppercase tracking-[0.28em]" style={accentStyle}>
                                Espace auteur
                            </span>
                            <h1 className="mt-5 text-4xl font-semibold tracking-[-0.04em] text-[#304438] md:text-5xl">
                                Le sanctuaire des récits de {userName || 'l’auteur'}
                            </h1>
                            <p className="mt-4 max-w-2xl text-sm leading-7 text-[#5b6f63]">
                                Gérez votre bio, publiez vos manuscrits et suivez votre bibliothèque numérique.
                            </p>
                            {accountSettingsUrl && (
                                <div className="mt-6">
                                    <a
                                        href={accountSettingsUrl}
                                        className="inline-flex rounded-full bg-[#304438] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5"
                                    >
                                        Modifier mon profil
                                    </a>
                                </div>
                            )}
                            <MagicBookGlyph className="pointer-events-none absolute -bottom-4 right-4 hidden h-28 w-36 opacity-85 md:block md:h-36 md:w-48" />
                        </div>

                        <div className="mt-8 grid gap-5 lg:mt-10 lg:grid-cols-[0.95fr_1.05fr]">
                            <section className="rounded-[1.5rem] border border-[#e7d5f5] bg-[linear-gradient(180deg,rgba(255,251,246,0.92),rgba(250,243,255,0.9))] p-4 shadow-[0_18px_56px_rgba(122,102,159,0.14)] sm:rounded-[2rem] sm:p-6">
                                <div className="flex items-center justify-between">
                                    <h2 className="text-lg font-semibold text-[#304438]">Profil auteur</h2>
                                    <span className="rounded-full border border-[#ecdff8] bg-white/85 px-3 py-1 text-xs text-[#7a669f]">
                                        {formatStorage(storageUsedBytes)} / {formatStorage(storageLimitBytes)}
                                    </span>
                                </div>
                                <div className="mt-4 h-3 overflow-hidden rounded-full bg-white/70">
                                    <div
                                        className="h-full rounded-full bg-gradient-to-r from-[#7a669f] via-[#c084fc] to-[#ffb703]"
                                        style={{ width: `${storageRatio}%` }}
                                    />
                                </div>
                                <p className="mt-2 text-sm text-[#5b5670]">
                                    Espace utilisé : {storageRatio} % sur {storageLimitLabel || formatStorage(storageLimitBytes)}.
                                </p>

                                {storageError && (
                                    <div className="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                                        {storageError}
                                    </div>
                                )}

                                {uploadError && (
                                    <div className="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700" role="alert">
                                        {uploadError}
                                    </div>
                                )}

                                {successMessage && (
                                    <div className="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700" role="status">
                                        {successMessage}
                                    </div>
                                )}

                                <form method="POST" action={profileAction} encType="multipart/form-data" className="mt-6 grid gap-3">
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <input type="hidden" name="_method" value="PATCH" />
                                    {bannerUrl && (
                                        <div className="overflow-hidden rounded-[1.2rem] border border-[#eadff7] bg-white/80">
                                            <img src={bannerUrl} alt="Bannière auteur" className="h-32 w-full object-cover" />
                                        </div>
                                    )}
                                    <label className="grid gap-2 rounded-[1.2rem] border border-dashed border-[#d8d2e8] bg-white/70 p-4 text-sm text-[#5b5670]">
                                        <span className="font-semibold text-[#304438]">Bannière du profil</span>
                                        <span>Ajoutez une image large qui apparaîtra sur votre page publique.</span>
                                        <input
                                            type="file"
                                            name="banner_image"
                                            accept="image/*"
                                            className="mobile-file-input w-full rounded-xl border border-[#d8d2e8] bg-white px-3 py-3 text-[#304438] file:rounded-full file:border-0 file:bg-gradient-to-r file:from-[#7a669f] file:via-[#c084fc] file:to-[#ffb703] file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white"
                                        />
                                    </label>
                                    <textarea
                                        name="bio"
                                        defaultValue={bio || ''}
                                        rows="5"
                                        placeholder="Présentez votre univers, vos inspirations, vos thèmes..."
                                        className="w-full rounded-[1.25rem] border border-[#eadff7] bg-white/90 px-4 py-3 text-sm text-[#21453f] placeholder:text-[#9d92b0] focus:border-[#c084fc] focus:outline-none focus:ring-2 focus:ring-[#c084fc]/20"
                                    />
                                    <input
                                        type="text"
                                        name="instagram"
                                        defaultValue={social.instagram || ''}
                                        placeholder="Instagram"
                                        className="w-full rounded-xl border border-[#eadff7] bg-white/90 px-3 py-2 text-sm text-[#21453f] placeholder:text-[#9d92b0] focus:border-[#c084fc] focus:outline-none focus:ring-2 focus:ring-[#c084fc]/20"
                                    />
                                    <input
                                        type="text"
                                        name="facebook"
                                        defaultValue={social.facebook || ''}
                                        placeholder="Facebook"
                                        className="w-full rounded-xl border border-[#eadff7] bg-white/90 px-3 py-2 text-sm text-[#21453f] placeholder:text-[#9d92b0] focus:border-[#c084fc] focus:outline-none focus:ring-2 focus:ring-[#c084fc]/20"
                                    />
                                    <input
                                        type="text"
                                        name="website"
                                        defaultValue={social.website || ''}
                                        placeholder="Site web"
                                        className="w-full rounded-xl border border-[#eadff7] bg-white/90 px-3 py-2 text-sm text-[#21453f] placeholder:text-[#9d92b0] focus:border-[#c084fc] focus:outline-none focus:ring-2 focus:ring-[#c084fc]/20"
                                    />
                                    <button
                                        type="submit"
                                        className="rounded-xl bg-gradient-to-r from-[#7a669f] via-[#c084fc] to-[#ffb703] px-4 py-2 text-sm font-semibold text-white shadow-[0_16px_34px_rgba(122,102,159,0.22)] transition hover:-translate-y-0.5"
                                    >
                                        Enregistrer le profil
                                    </button>
                                </form>
                            </section>

                            <section className="relative overflow-hidden rounded-[1.5rem] border border-[#d9d2eb] bg-[linear-gradient(180deg,rgba(255,255,255,0.92),rgba(248,244,255,0.92))] p-4 shadow-[0_18px_56px_rgba(122,102,159,0.16)] sm:rounded-[2rem] sm:p-6">
                                <div className="absolute -right-6 top-6 h-28 w-28 rounded-full bg-[#c084fc]/12 blur-2xl" />
                                <div className="absolute left-8 bottom-6 h-20 w-20 rounded-full bg-[#ffb703]/10 blur-xl" />
                                <DragonGlyph className="absolute right-8 top-14 hidden h-16 w-16 opacity-45 lg:block" />
                                <FireballGlyph className="absolute left-6 top-20 hidden h-14 w-14 opacity-55 lg:block" />
                                <div className="flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <h2 className="text-lg font-semibold">Publier une œuvre</h2>
                                    <span className="rounded-full bg-white px-3 py-1 text-xs text-[#7a669f] shadow-sm">
                                        PDF, TXT, DOC, DOCX
                                    </span>
                                </div>
                                <p className="mt-2 max-w-2xl text-sm leading-6 text-[#5b6f63]">
                                    Chaque œuvre comporte un texte et une image de couverture.
                                </p>
                                {documentCreateUrl && (
                                    <div className="mt-4 rounded-[1.25rem] border border-[#c9b8ff] bg-[#f4efff] p-4">
                                        <p className="text-sm font-semibold text-[#2a2350]">Écris ou colle ton texte directement sur le site</p>
                                        <p className="mt-1 text-sm leading-6 text-[#5e5785]">
                                            Chapitres, ambiances de lecture, taille du texte et reprise de lecture fonctionnent pleinement avec un texte écrit dans l&apos;éditeur.
                                        </p>
                                        <a href={documentCreateUrl} className="mt-3 inline-flex min-h-11 items-center rounded-full bg-[#2a2350] px-5 text-sm font-semibold text-white">
                                            Ouvrir l&apos;éditeur
                                        </a>
                                        <p className="mt-3 text-xs text-[#5e5785]">Ou envoie un fichier ci-dessous (PDF, TXT, DOC, DOCX).</p>
                                    </div>
                                )}

                                <form
                                    method="POST"
                                    action={documentUploadAction}
                                    encType="multipart/form-data"
                                    className="mt-5 grid gap-4 rounded-[1.35rem] border border-[#ded5ef] bg-[linear-gradient(180deg,rgba(255,255,255,0.82),rgba(249,244,255,0.82))] p-4 sm:mt-6 sm:rounded-[1.6rem] sm:p-5"
                                >
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <input
                                        type="text"
                                        name="title"
                                        placeholder="Titre de l'œuvre"
                                        className="mobile-field w-full rounded-xl border border-[#d8d2e8] bg-white px-4 py-3 text-[#304438] placeholder:text-[#8f88a1] focus:border-[#c084fc] focus:outline-none focus:ring-2 focus:ring-[#c084fc]/20 sm:text-sm"
                                        required
                                    />
                                    <textarea
                                        name="description"
                                        rows="4"
                                        placeholder="Résumé, note d'intention ou extrait..."
                                        className="mobile-field w-full rounded-xl border border-[#d8d2e8] bg-white px-4 py-3 text-[#304438] placeholder:text-[#8f88a1] focus:border-[#c084fc] focus:outline-none focus:ring-2 focus:ring-[#c084fc]/20 sm:text-sm"
                                    />
                                    <label className="grid gap-2 text-sm font-semibold text-[#304438]">
                                        <span>
                                            Genre <span className="ml-1 rounded-full bg-[#2a2350] px-2 py-0.5 text-[10px] uppercase tracking-[0.12em] text-white">obligatoire</span>
                                        </span>
                                        <select
                                            name="genre"
                                            defaultValue=""
                                            required
                                            className="mobile-field w-full rounded-xl border border-[#d8d2e8] bg-white px-4 py-3 text-[#304438] focus:border-[#c084fc] focus:outline-none focus:ring-2 focus:ring-[#c084fc]/20 sm:text-sm"
                                        >
                                            <option value="" disabled>Choisir le ciel de ton récit…</option>
                                            {Object.entries(documentGenres).map(([value, label]) => (
                                                <option key={value} value={value}>{label}</option>
                                            ))}
                                        </select>
                                    </label>
                                    <div className="grid gap-4 lg:grid-cols-2">
                                        <div className="rounded-[1.25rem] border border-[#d8d2e8] bg-white/85 p-4 shadow-sm sm:rounded-[1.4rem]">
                                            <span className="mb-3 inline-flex rounded-full bg-[#f6efff] px-3 py-1 text-[11px] font-semibold text-[#7a669f]">Étape 1</span>
                                            <span className="block text-xs uppercase tracking-[0.24em] text-[#7a669f]">
                                                Manuscrit
                                            </span>
                                            <span className="mt-2 block text-lg font-semibold text-[#304438]">
                                                Choisir le fichier du livre
                                            </span>
                                            <span className="mt-1 block text-sm leading-6 text-[#6a6478]">
                                                PDF, TXT, DOC ou DOCX pour le texte principal.
                                            </span>
                                            <input
                                                type="file"
                                                name="document"
                                                accept=".pdf,.txt,.doc,.docx"
                                                className="mobile-file-input mt-4 w-full rounded-xl border border-[#d8d2e8] bg-[#faf8ff] px-3 py-3 text-[#304438] file:rounded-full file:border-0 file:bg-gradient-to-r file:from-[#7ecf9a] file:to-[#96c788] file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white"
                                                required
                                            />
                                        </div>

                                        <div className="rounded-[1.25rem] border border-[#eadfcb] bg-white/85 p-4 shadow-sm sm:rounded-[1.4rem]">
                                            <span className="mb-3 inline-flex rounded-full bg-[#fff7ed] px-3 py-1 text-[11px] font-semibold text-[#b46d46]">Étape 2</span>
                                            <span className="block text-xs uppercase tracking-[0.24em] text-[#b46d46]">
                                                Illustration
                                            </span>
                                            <span className="mt-2 block text-lg font-semibold text-[#304438]">
                                                Choisir l&apos;image du livre
                                            </span>
                                            <span className="mt-1 block text-sm leading-6 text-[#6a6478]">
                                                Couverture, affiche ou image d'ambiance de l'œuvre.
                                            </span>
                                            <input
                                                type="file"
                                                name="cover_image"
                                                accept="image/*"
                                                className="mobile-file-input mt-4 w-full rounded-xl border border-[#eadfcb] bg-[#fff9f2] px-3 py-3 text-[#304438] file:rounded-full file:border-0 file:bg-gradient-to-r file:from-[#ffb703] file:to-[#ff7b54] file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white"
                                                required
                                            />
                                        </div>
                                    </div>
                                    <label className="flex items-start gap-3 rounded-[1.2rem] border border-dashed border-[#c9b8ff] bg-white/70 p-4 text-sm leading-6 text-[#4a4270]">
                                        <input type="checkbox" name="rights" value="1" required className="mt-1 h-5 w-5 shrink-0 accent-[#5b4ba8]" />
                                        <span>
                                            <strong className="text-[#2a2350]">Je certifie être l&apos;auteur de ce texte</strong> et avoir le droit de le publier : pas de copie, ni d&apos;images ou de logos appartenant à d&apos;autres (y compris sur la couverture).
                                            {moderationEnabled && (
                                                <span className="mt-1 block text-xs text-[#5e5785]">Chaque nouvelle œuvre est relue par l&apos;équipe avant d&apos;apparaître sur le site.</span>
                                            )}
                                        </span>
                                    </label>
                                    <button
                                        type="submit"
                                        className="min-h-12 rounded-xl bg-gradient-to-r from-[#7a669f] via-[#c084fc] to-[#ffb703] px-4 py-3 text-base font-semibold text-white shadow-[0_18px_36px_rgba(192,132,252,0.22)] transition hover:-translate-y-0.5 sm:text-sm"
                                    >
                                        Ajouter l&apos;œuvre
                                    </button>
                                </form>
                            </section>
                        </div>

                        <section id="bibliotheque" className="mt-10">
                            <div className="flex items-center justify-between">
                                <h2 className="text-xl font-semibold text-[#21453f]">Ma bibliothèque</h2>
                                <span className="text-xs text-[#44645f]">
                                    {documents.length} {documents.length >= 2 ? 'œuvres' : 'œuvre'}
                                </span>
                            </div>

                            {untypedDocuments > 0 && (
                                <p className="mt-4 rounded-xl border border-[#c9b8ff] bg-[#f4efff] p-3 text-sm text-[#2a2350]">
                                    {untypedDocuments >= 2
                                        ? `${untypedDocuments} œuvres n’ont pas encore de genre : elles n’apparaissent dans aucun ciel ni royaume.`
                                        : '1 œuvre n’a pas encore de genre : elle n’apparaît dans aucun ciel ni royaume.'}
                                    {' '}Choisis-le ci-dessous.
                                </p>
                            )}

                            {documents.length === 0 && (
                                <p className="mt-4 text-sm text-[#44645f]">
                                    Vous n&apos;avez pas encore publié d&apos;œuvre.
                                </p>
                            )}

                            <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {documents.map((document) => (
                                    <article
                                        key={document.id}
                                        className={`overflow-hidden rounded-[1.5rem] border bg-white shadow-[0_16px_40px_rgba(45,212,191,0.12)] ${document.genre ? 'border-[#dbeee5]' : 'border-2 border-dashed border-[#7a669f]'}`}
                                    >
                                        <img
                                            src={document.coverImageUrl}
                                            alt={document.title}
                                            loading="lazy"
                                            className="aspect-[4/5] w-full object-cover"
                                        />
                                        <form method="POST" action={document.genreUpdateUrl} className="grid gap-1 px-4 pt-4">
                                            <input type="hidden" name="_token" value={csrfToken} />
                                            <input type="hidden" name="_method" value="PATCH" />
                                            <label htmlFor={`genre-${document.id}`} className="text-xs font-semibold uppercase tracking-[0.14em] text-[#5b4ba8]">
                                                Genre
                                            </label>
                                            <select
                                                id={`genre-${document.id}`}
                                                name="genre"
                                                required
                                                defaultValue={document.genre || ''}
                                                onChange={(event) => event.target.form.requestSubmit()}
                                                className="min-h-11 w-full rounded-xl border border-[#d8d2e8] bg-white px-3 text-sm text-[#2a2350] focus:border-[#c084fc] focus:outline-none focus:ring-2 focus:ring-[#c084fc]/20"
                                            >
                                                {!document.genre && <option value="" disabled>À choisir…</option>}
                                                {Object.entries(document.genreOptions || documentGenres).map(([value, label]) => (
                                                    <option key={value} value={value}>{label}</option>
                                                ))}
                                            </select>
                                        </form>
                                        <div className="space-y-3 p-4">
                                            <div className="flex items-center justify-between gap-3">
                                                <div className="min-w-0">
                                                    <p className="text-sm font-semibold text-[#21453f]">{document.title}</p>
                                                    {!document.isPublished && (
                                                        <span className="mt-1 inline-flex rounded-full bg-[#fcee0a] px-2 py-0.5 text-[11px] font-semibold text-[#0d0221]">
                                                            {document.statusLabel}
                                                        </span>
                                                    )}
                                                    {document.moderationNote && (
                                                        <p className="mt-1 text-xs text-[#9a3a3a]">Motif : {document.moderationNote}</p>
                                                    )}
                                                </div>
                                                <span className="rounded-full bg-[#eef9f3] px-3 py-1 text-[11px] font-semibold text-[#147d6d]">
                                                    {document.fileType}
                                                </span>
                                            </div>
                                            {document.description && (
                                                <p className="text-sm leading-6 text-[#44645f]">{document.description}</p>
                                            )}
                                            <div className="flex flex-wrap items-center justify-between gap-2 text-xs text-[#44645f]">
                                                <span>
                                                    {plural(document.viewCount, 'vue')} · {plural(document.likesCount, 'J’aime', 'J’aime')} ·{' '}
                                                    {document.allowDownload
                                                        ? plural(document.downloadCount, 'téléchargement')
                                                        : 'téléchargement désactivé'}
                                                </span>
                                                {document.fileSize > 0 && <span>{formatStorage(document.fileSize)}</span>}
                                            </div>
                                            {!document.hasText && (
                                                <p className="rounded-xl bg-[#f4efff] p-2 text-xs leading-5 text-[#2a2350]">
                                                    Pas encore de version texte : ajoute-la dans l&apos;éditeur pour les chapitres et les ambiances de lecture.
                                                </p>
                                            )}
                                            <div className="flex flex-wrap gap-2">
                                                <a href={document.readUrl} className="inline-flex min-h-11 items-center rounded-full border border-[#d8d2e8] px-4 text-xs font-semibold text-[#2a2350]">
                                                    Lire
                                                </a>
                                                <a href={document.editUrl} className="inline-flex min-h-11 items-center rounded-full bg-[#2a2350] px-4 text-xs font-semibold text-white">
                                                    Modifier le texte
                                                </a>
                                            </div>
                                            <form action={document.destroyUrl} method="POST">
                                                <input type="hidden" name="_token" value={csrfToken} />
                                                <input type="hidden" name="_method" value="DELETE" />
                                                <button
                                                    type="submit"
                                                    className="w-full rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:border-red-300 hover:bg-red-100"
                                                >
                                                    Supprimer
                                                </button>
                                            </form>
                                        </div>
                                    </article>
                                ))}
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        );
    }

    // L’espace artiste est rendu en Blade (resources/views/dashboard/artist.blade.php).
    return null;
};

export default Dashboard;
