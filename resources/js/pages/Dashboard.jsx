import React from 'react';

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

const Dashboard = ({
    accountType = 'artist',
    userName = '',
    bio = '',
    bannerUrl = null,
    illustrations = [],
    illustrationsCount = 0,
    remaining = 20,
    portfolios = [],
    documents = [],
    followedArtists = [],
    favoriteIllustrations = [],
    bookmarkedDocuments = [],
    storageLimitBytes = 0,
    storageUsedBytes = 0,
    uploadAction = '',
    documentUploadAction = '',
    profileAction = '',
    accountSettingsUrl = '',
    portfolioUrl = '',
    csrfToken = '',
    quotaError = '',
    storageError = '',
    uploadError = '',
    successMessage = '',
    social = {},
    theme = {},
}) => {
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
    const quotaReached = illustrationsCount >= 20;
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
                        </div>

                        <div className="mt-10 grid gap-6 xl:grid-cols-3">
                            <section className="rounded-[1.8rem] border border-white/70 bg-white/85 p-6 shadow-[0_18px_50px_rgba(255,161,90,0.12)]">
                                <div className="flex items-center justify-between gap-4">
                                    <h2 className="text-xl font-semibold text-[#2b183d]">Artistes suivis</h2>
                                    <span className="rounded-full bg-[#fffaf4] px-4 py-2 text-xs text-[#7b627f]">
                                        {followedArtists.length} suivi(s)
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
                                                    {artist.illustrationsCount} illustration(s)
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
                                        {favoriteIllustrations.length} favori(s)
                                    </span>
                                </div>
                                <div className="mt-6 grid gap-4 sm:grid-cols-2">
                                    {favoriteIllustrations.length === 0 && (
                                        <p className="sm:col-span-2 rounded-[1.2rem] border border-dashed border-[#dbeee5] bg-[#f7fff8] p-4 text-sm text-[#44645f]">
                                            Vous n avez pas encore ajoute d illustration en favoris.
                                        </p>
                                    )}
                                    {favoriteIllustrations.map((illustration) => (
                                        <article key={illustration.id} className="overflow-hidden rounded-[1.4rem] border border-[#dbeee5] bg-[#f7fff8]">
                                            <img src={illustration.imageUrl} alt={illustration.title} className="h-40 w-full object-cover" />
                                            <div className="space-y-3 p-4">
                                                <div>
                                                    <p className="text-base font-semibold text-[#21453f]">{illustration.title}</p>
                                                    <p className="mt-1 text-sm text-[#44645f]">{illustration.artistName}</p>
                                                </div>
                                                <div className="flex flex-wrap gap-3">
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
                                    <h2 className="text-xl font-semibold text-[#5e4b8b]">Livres marques</h2>
                                    <span className="rounded-full bg-[#faf7ff] px-4 py-2 text-xs text-[#6d5aa8]">
                                        {bookmarkedDocuments.length} marque-page(s)
                                    </span>
                                </div>
                                <div className="mt-6 grid gap-4">
                                    {bookmarkedDocuments.length === 0 && (
                                        <p className="rounded-[1.2rem] border border-dashed border-[#e7dcff] bg-[#faf7ff] p-4 text-sm text-[#6d5aa8]">
                                            Vous n avez encore ajoute aucun livre a vos marque-pages.
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
                    <div className="relative mx-auto max-w-6xl px-6 py-12">
                        <div className="relative rounded-[2rem] border border-white/40 bg-[linear-gradient(135deg,rgba(255,250,245,0.84),rgba(246,236,255,0.68))] p-8 shadow-[0_28px_90px_rgba(89,57,138,0.24)] backdrop-blur" style={panelStyle}>
                            <span className="inline-flex rounded-full border bg-white/75 px-4 py-1 text-xs uppercase tracking-[0.28em]" style={accentStyle}>
                                Espace auteur
                            </span>
                            <h1 className="mt-5 text-4xl font-semibold tracking-[-0.04em] text-[#304438] md:text-5xl">
                                Le sanctuaire des recits de {userName || 'l auteur'}
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

                        <div className="mt-10 grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
                            <section className="rounded-[2rem] border border-[#e7d5f5] bg-[linear-gradient(180deg,rgba(255,251,246,0.92),rgba(250,243,255,0.9))] p-6 shadow-[0_18px_56px_rgba(122,102,159,0.14)]">
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
                                    Espace utilisé: {storageRatio}% sur 1 Go.
                                </p>

                                {storageError && (
                                    <div className="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                                        {storageError}
                                    </div>
                                )}

                                {successMessage && (
                                    <div className="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">
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
                                        <span>Ajoutez une image large qui apparaitra sur votre page publique.</span>
                                        <input
                                            type="file"
                                            name="banner_image"
                                            accept="image/*"
                                            className="w-full rounded-xl border border-[#d8d2e8] bg-white px-3 py-2 text-sm text-[#304438] file:mr-3 file:rounded-full file:border-0 file:bg-gradient-to-r file:from-[#7a669f] file:via-[#c084fc] file:to-[#ffb703] file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white"
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

                            <section className="relative overflow-hidden rounded-[2rem] border border-[#d9d2eb] bg-[linear-gradient(180deg,rgba(255,255,255,0.92),rgba(248,244,255,0.92))] p-6 shadow-[0_18px_56px_rgba(122,102,159,0.16)]">
                                <div className="absolute -right-6 top-6 h-28 w-28 rounded-full bg-[#c084fc]/12 blur-2xl" />
                                <div className="absolute left-8 bottom-6 h-20 w-20 rounded-full bg-[#ffb703]/10 blur-xl" />
                                <DragonGlyph className="absolute right-8 top-14 hidden h-16 w-16 opacity-45 lg:block" />
                                <FireballGlyph className="absolute left-6 top-20 hidden h-14 w-14 opacity-55 lg:block" />
                                <div className="flex items-center justify-between">
                                    <h2 className="text-lg font-semibold">Publier une œuvre</h2>
                                    <span className="rounded-full bg-white px-3 py-1 text-xs text-[#7a669f] shadow-sm">
                                        PDF, TXT, DOC, DOCX
                                    </span>
                                </div>
                                <p className="mt-2 max-w-2xl text-sm leading-6 text-[#5b6f63]">
                                    Chaque œuvre comporte un fichier texte et une illustration de référence.
                                </p>

                                <form
                                    method="POST"
                                    action={documentUploadAction}
                                    encType="multipart/form-data"
                                    className="mt-6 grid gap-4 rounded-[1.6rem] border border-[#ded5ef] bg-[linear-gradient(180deg,rgba(255,255,255,0.82),rgba(249,244,255,0.82))] p-5"
                                >
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <input
                                        type="text"
                                        name="title"
                                        placeholder="Titre de l'œuvre"
                                        className="w-full rounded-xl border border-[#d8d2e8] bg-white px-3 py-2 text-sm text-[#304438] placeholder:text-[#8f88a1] focus:border-[#c084fc] focus:outline-none focus:ring-2 focus:ring-[#c084fc]/20"
                                        required
                                    />
                                    <textarea
                                        name="description"
                                        rows="4"
                                        placeholder="Résumé, note d'intention ou extrait..."
                                        className="w-full rounded-xl border border-[#d8d2e8] bg-white px-3 py-2 text-sm text-[#304438] placeholder:text-[#8f88a1] focus:border-[#c084fc] focus:outline-none focus:ring-2 focus:ring-[#c084fc]/20"
                                    />
                                    <div className="grid gap-4 lg:grid-cols-2">
                                        <div className="rounded-[1.4rem] border border-[#d8d2e8] bg-white/85 p-4 shadow-sm">
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
                                                className="mt-4 w-full rounded-xl border border-[#d8d2e8] bg-[#faf8ff] px-3 py-2 text-sm text-[#304438] file:mr-3 file:rounded-full file:border-0 file:bg-gradient-to-r file:from-[#7ecf9a] file:to-[#96c788] file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white"
                                                required
                                            />
                                        </div>

                                        <div className="rounded-[1.4rem] border border-[#eadfcb] bg-white/85 p-4 shadow-sm">
                                            <span className="block text-xs uppercase tracking-[0.24em] text-[#b46d46]">
                                                Illustration
                                            </span>
                                            <span className="mt-2 block text-lg font-semibold text-[#304438]">
                                                Choisir l image du livre
                                            </span>
                                            <span className="mt-1 block text-sm leading-6 text-[#6a6478]">
                                                Couverture, affiche ou image d'ambiance de l'œuvre.
                                            </span>
                                            <input
                                                type="file"
                                                name="cover_image"
                                                accept="image/*"
                                                className="mt-4 w-full rounded-xl border border-[#eadfcb] bg-[#fff9f2] px-3 py-2 text-sm text-[#304438] file:mr-3 file:rounded-full file:border-0 file:bg-gradient-to-r file:from-[#ffb703] file:to-[#ff7b54] file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white"
                                                required
                                            />
                                        </div>
                                    </div>
                                    <button
                                        type="submit"
                                        className="rounded-xl bg-gradient-to-r from-[#7a669f] via-[#c084fc] to-[#ffb703] px-4 py-2 text-sm font-semibold text-white shadow-[0_18px_36px_rgba(192,132,252,0.22)] transition hover:-translate-y-0.5"
                                    >
                                        Ajouter l&apos;œuvre
                                    </button>
                                </form>
                            </section>
                        </div>

                        <section id="bibliotheque" className="mt-10">
                            <div className="flex items-center justify-between">
                                <h2 className="text-xl font-semibold text-[#21453f]">Ma bibliothèque</h2>
                                <span className="text-xs text-[#44645f]">{documents.length} œuvre(s)</span>
                            </div>

                            {documents.length === 0 && (
                                <p className="mt-4 text-sm text-[#44645f]">
                                    Vous n&apos;avez pas encore publié d&apos;œuvre.
                                </p>
                            )}

                            <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {documents.map((document) => (
                                    <article
                                        key={document.id}
                                        className="overflow-hidden rounded-[1.5rem] border border-[#dbeee5] bg-white shadow-[0_16px_40px_rgba(45,212,191,0.12)]"
                                    >
                                        <img
                                            src={document.coverImageUrl}
                                            alt={document.title}
                                            className="h-48 w-full object-cover"
                                        />
                                        <div className="space-y-3 p-4">
                                            <div className="flex items-center justify-between gap-3">
                                                <p className="text-sm font-semibold text-[#21453f]">{document.title}</p>
                                                <span className="rounded-full bg-[#eef9f3] px-3 py-1 text-[11px] font-semibold text-[#147d6d]">
                                                    {document.fileType}
                                                </span>
                                            </div>
                                            {document.description && (
                                                <p className="text-sm leading-6 text-[#44645f]">{document.description}</p>
                                            )}
                                            <div className="flex items-center justify-between gap-3 text-xs text-[#44645f]">
                                                <span>{formatStorage(document.fileSize)}</span>
                                                <a href={document.readUrl || document.fileUrl} className="font-semibold text-[#9a5a40]">
                                                    Lire
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

    return (
        <div className="min-h-screen text-[#181818]" style={rootStyle}>
            <div className="relative overflow-hidden">
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
                    <div className="relative rounded-[2rem] border border-black/10 bg-white/88 p-8 shadow-[0_28px_80px_rgba(0,0,0,0.08)] backdrop-blur">
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
                        <span className="inline-flex w-fit items-center gap-3 rounded-full border border-black/10 bg-[#f6f6f6] px-4 py-1 text-xs uppercase tracking-[0.28em] text-[#4b4b4b]">
                            Espace artiste
                        </span>
                        <h1 className="text-4xl font-semibold uppercase tracking-[-0.04em] text-[#141414] md:text-5xl">
                            Studio visuel de {userName || 'l artiste'}
                        </h1>
                        <p className="max-w-2xl text-sm leading-7 text-[#525252]">
                            Gérez votre bio, vos illustrations et votre présence en ligne.
                        </p>
                        {accountSettingsUrl && (
                            <div className="mt-6">
                                <a
                                    href={accountSettingsUrl}
                                    className="inline-flex rounded-full bg-[#181818] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5"
                                >
                                    Modifier mon profil
                                </a>
                            </div>
                        )}
                    </div>

                    {portfolioUrl && (
                        <div className="mt-6 flex flex-wrap gap-3">
                            <a
                                href={portfolioUrl}
                                className="rounded-full bg-[#181818] px-5 py-3 text-sm font-semibold text-white shadow-[0_16px_34px_rgba(0,0,0,0.14)] transition hover:-translate-y-0.5"
                            >
                                Creer mon portfolio
                            </a>
                        </div>
                    )}

                    <div className="mt-10 grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                    <section className="rounded-[1.9rem] border border-black/10 bg-white p-6 shadow-[0_18px_56px_rgba(0,0,0,0.06)]" style={panelStyle}>
                            <div className="flex items-center justify-between">
                                <h2 className="text-lg font-semibold uppercase tracking-[0.12em] text-[#181818]">Profil artiste</h2>
                                <span className="rounded-full border border-black/10 bg-[#f5f5f5] px-3 py-1 text-xs text-[#4b4b4b]">
                                    {illustrationsCount} / 20
                                </span>
                            </div>
                            <div className="mt-4 h-3 overflow-hidden rounded-full bg-[#ececec]">
                                <div
                                    className="h-full rounded-full bg-[#181818]"
                                    style={{ width: `${storageRatio}%` }}
                                />
                            </div>
                            <p className="mt-2 text-sm text-[#6f5c75]">
                                {remaining} illustration(s) restantes. Stockage: {formatStorage(storageUsedBytes)} / {formatStorage(storageLimitBytes)}.
                            </p>

                            {quotaError && (
                                <div className="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                                    {quotaError}
                                </div>
                            )}

                            {storageError && (
                                <div className="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                                    {storageError}
                                </div>
                            )}

                            {uploadError && (
                                <div className="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                                    {uploadError}
                                </div>
                            )}

                            {successMessage && (
                                <div className="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">
                                    {successMessage}
                                </div>
                            )}

                            <form method="POST" action={profileAction} encType="multipart/form-data" className="mt-6 grid gap-3 rounded-[1.5rem] border border-black/10 bg-[#f9f9f9] p-4">
                                <input type="hidden" name="_token" value={csrfToken} />
                                <input type="hidden" name="_method" value="PATCH" />
                                {bannerUrl && (
                                    <div className="overflow-hidden rounded-[1.2rem] border border-black/10 bg-white">
                                        <img src={bannerUrl} alt="Bannière artiste" className="h-32 w-full object-cover" />
                                    </div>
                                )}
                                <label className="grid gap-2 rounded-[1.2rem] border border-dashed border-black/10 bg-white p-4 text-sm text-[#5e5e5e]">
                                    <span className="font-semibold text-[#181818]">Bannière du profil</span>
                                    <span>Ajoutez une image hero visible quand quelqu un ouvre votre profil.</span>
                                    <input
                                        type="file"
                                        name="banner_image"
                                        accept="image/*"
                                        className="w-full rounded-xl border border-black/10 bg-[#fafafa] px-3 py-2 text-sm text-[#181818] file:mr-3 file:rounded-full file:border-0 file:bg-[#181818] file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white"
                                    />
                                </label>
                                <textarea
                                    name="bio"
                                    defaultValue={bio || ''}
                                    rows="5"
                                    placeholder="Décrivez votre univers, vos influences, vos techniques..."
                                    className="w-full rounded-[1.25rem] border border-black/10 bg-white px-4 py-3 text-sm text-[#181818] placeholder:text-[#8a8a8a] focus:border-[#181818] focus:outline-none focus:ring-2 focus:ring-black/5"
                                />
                                <input
                                    type="text"
                                    name="instagram"
                                    defaultValue={social.instagram || ''}
                                    placeholder="Instagram"
                                    className="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm text-[#181818] placeholder:text-[#8a8a8a] focus:border-[#ef476f] focus:outline-none focus:ring-2 focus:ring-[#ef476f]/15"
                                />
                                <input
                                    type="text"
                                    name="twitter"
                                    defaultValue={social.twitter || ''}
                                    placeholder="X (Twitter)"
                                    className="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm text-[#181818] placeholder:text-[#8a8a8a] focus:border-[#ffb703] focus:outline-none focus:ring-2 focus:ring-[#ffb703]/15"
                                />
                                <input
                                    type="text"
                                    name="facebook"
                                    defaultValue={social.facebook || ''}
                                    placeholder="Facebook"
                                    className="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm text-[#181818] placeholder:text-[#8a8a8a] focus:border-[#14b8a6] focus:outline-none focus:ring-2 focus:ring-[#14b8a6]/15"
                                />
                                <input
                                    type="text"
                                    name="youtube"
                                    defaultValue={social.youtube || ''}
                                    placeholder="YouTube"
                                    className="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm text-[#181818] placeholder:text-[#8a8a8a] focus:border-[#ef476f] focus:outline-none focus:ring-2 focus:ring-[#ef476f]/15"
                                />
                                <input
                                    type="text"
                                    name="behance"
                                    defaultValue={social.behance || ''}
                                    placeholder="Behance"
                                    className="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm text-[#181818] placeholder:text-[#8a8a8a] focus:border-[#14b8a6] focus:outline-none focus:ring-2 focus:ring-[#14b8a6]/15"
                                />
                                <input
                                    type="text"
                                    name="website"
                                    defaultValue={social.website || ''}
                                    placeholder="Site web"
                                    className="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm text-[#181818] placeholder:text-[#8a8a8a] focus:border-[#ffb703] focus:outline-none focus:ring-2 focus:ring-[#ffb703]/15"
                                />
                                <button
                                    type="submit"
                                    className="rounded-xl bg-[#181818] px-4 py-2 text-sm font-semibold text-white shadow-[0_16px_34px_rgba(0,0,0,0.14)] transition hover:-translate-y-0.5"
                                >
                                    Enregistrer le profil
                                </button>
                            </form>
                        </section>

                        <section className="relative overflow-hidden rounded-[1.9rem] border border-black/10 bg-white p-6 shadow-[0_18px_56px_rgba(0,0,0,0.06)]">
                            <div className="absolute right-4 top-4 hidden rounded-full border border-black/10 bg-[#181818] px-3 py-1 lg:block">
                                <span className="text-[10px] font-black uppercase tracking-[0.28em] text-[#ffb703]">Babi Art Line</span>
                            </div>
                            <div className="absolute bottom-6 right-6 hidden -rotate-6 rounded-[1rem] border border-black/10 bg-[#f5f5f5] px-3 py-2 lg:block">
                                <span className="text-[10px] font-black uppercase tracking-[0.28em] text-[#ef476f]">Garba Energy</span>
                            </div>
                            <div className="flex items-center justify-between gap-4">
                                <h2 className="text-lg font-semibold uppercase tracking-[0.12em] text-[#181818]">Ajouter une illustration</h2>
                                <div className="flex gap-2">
                                    <span className="h-3 w-3 rounded-full bg-[#ef476f]" />
                                    <span className="h-3 w-3 rounded-full bg-[#ffb703]" />
                                    <span className="h-3 w-3 rounded-full bg-[#14b8a6]" />
                                </div>
                            </div>
                            <p className="mt-2 text-sm leading-6 text-[#525252]">
                                Une galerie expressive, avec une limite de 20 visuels, 20 Mo par image et 1 Go de stockage au total.
                            </p>

                            {!quotaReached ? (
                                <form
                                    method="POST"
                                    action={uploadAction}
                                    encType="multipart/form-data"
                                    className="mt-6 grid gap-3"
                                >
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <input
                                        type="text"
                                        name="title"
                                        placeholder="Titre de l'illustration"
                                        className="w-full rounded-xl border border-black/10 bg-[#fafafa] px-3 py-2 text-sm text-[#181818] placeholder:text-[#8a8a8a] focus:border-[#181818] focus:outline-none focus:ring-2 focus:ring-black/5"
                                        required
                                    />
                                    <input
                                        type="file"
                                        name="image"
                                        accept="image/*"
                                        className="w-full rounded-xl border border-black/10 bg-[#fafafa] px-3 py-2 text-sm text-[#181818] file:mr-3 file:rounded-full file:border-0 file:bg-[#181818] file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white"
                                        required
                                    />
                                    <button
                                        type="submit"
                                        className="rounded-xl bg-gradient-to-r from-[#181818] via-[#2c2c2c] to-[#444444] px-4 py-2 text-sm font-semibold text-white transition hover:-translate-y-0.5"
                                    >
                                        Ajouter une illustration
                                    </button>
                                    <div className="mt-3 grid gap-3 md:grid-cols-3">
                                        <div className="rounded-[1.2rem] border border-black/10 bg-[#181818] px-4 py-4 text-white shadow-[0_14px_28px_rgba(0,0,0,0.12)]">
                                            <p className="text-[10px] font-black uppercase tracking-[0.34em] text-[#ffb703]">Abidjan Signal</p>
                                            <p className="mt-2 text-lg font-black uppercase leading-none">Libere ta creativite</p>
                                            <p className="mt-2 text-xs uppercase tracking-[0.22em] text-[#f3b0c1]">Fais vibrer ta ville</p>
                                        </div>
                                        <div className="rounded-[1.2rem] border border-black/10 bg-white px-4 py-4 shadow-[0_14px_28px_rgba(0,0,0,0.08)]">
                                            <p className="text-[10px] font-black uppercase tracking-[0.34em] text-[#14b8a6]">Babi Motion</p>
                                            <p className="mt-2 text-lg font-black uppercase leading-none text-[#181818]">Dessine plus grand</p>
                                            <p className="mt-2 text-xs uppercase tracking-[0.22em] text-[#6c6c6c]">Ton trait merite la lumiere</p>
                                        </div>
                                        <div className="rounded-[1.2rem] border border-black/10 bg-[#fff4df] px-4 py-4 shadow-[0_14px_28px_rgba(0,0,0,0.08)]">
                                            <p className="text-[10px] font-black uppercase tracking-[0.34em] text-[#ff7b54]">Garba District</p>
                                            <p className="mt-2 text-lg font-black uppercase leading-none text-[#181818]">Paume comme vrai ziguehi</p>
                                        </div>
                                    </div>
                                </form>
                            ) : (
                                <div className="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
                                    Quota atteint. Supprimez une illustration pour en ajouter une nouvelle.
                                </div>
                            )}
                        </section>
                    </div>

                    <section className="mt-10 rounded-[1.9rem] border border-black/10 bg-white p-6 shadow-[0_18px_56px_rgba(0,0,0,0.06)]">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 className="text-xl font-semibold uppercase tracking-[0.08em] text-[#181818]">Mes portfolios</h2>
                                <p className="mt-2 text-sm text-[#525252]">Chaque portfolio est une visite immersive composee scene par scene.</p>
                            </div>
                            {portfolioUrl && (
                                <a href={portfolioUrl} className="rounded-full bg-[#181818] px-5 py-3 text-sm font-semibold text-white">
                                    Creer mon portfolio
                                </a>
                            )}
                        </div>

                        {portfolios.length === 0 && (
                            <p className="mt-5 rounded-[1.2rem] border border-dashed border-orange-200 bg-[#fffaf4] p-4 text-sm text-[#6f5c75]">
                                Aucun portfolio créé pour le moment.
                            </p>
                        )}

                        <div className="mt-5 grid gap-4 md:grid-cols-2">
                            {portfolios.map((portfolio) => (
                                <article key={portfolio.id} className="rounded-[1.4rem] border border-black/10 bg-[#fafafa] p-5">
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <h3 className="text-lg font-semibold text-[#181818]">{portfolio.title}</h3>
                                            <p className="mt-2 text-sm leading-6 text-[#525252]">
                                                {portfolio.description || 'Portfolio immersif sans description generale.'}
                                            </p>
                                        </div>
                                        <span className="shrink-0 rounded-full border border-black/10 bg-white px-3 py-1 text-xs text-[#4b4b4b]">
                                            {portfolio.itemsCount} scene(s)
                                        </span>
                                    </div>
                                    <div className="mt-4 flex flex-wrap gap-3">
                                        <a href={portfolio.showUrl} className="rounded-full bg-[#181818] px-4 py-2 text-xs font-semibold text-white">
                                            Voir en immersion
                                        </a>
                                        <a href={portfolio.editUrl} className="rounded-full border border-black/10 bg-white px-4 py-2 text-xs font-semibold text-[#181818]">
                                            Modifier
                                        </a>
                                    </div>
                                </article>
                            ))}
                        </div>
                    </section>

                    <section className="mt-10">
                        <div className="flex items-center justify-between">
                            <h2 className="text-xl font-semibold uppercase tracking-[0.08em] text-[#181818]">Mes illustrations</h2>
                            <span className="rounded-full border border-black/10 bg-white px-4 py-2 text-xs text-[#4b4b4b]">
                                {illustrations.length} visuel(s)
                            </span>
                        </div>

                        {illustrations.length === 0 && (
                            <p className="mt-4 text-sm text-[#525252]">
                                Vous n&apos;avez pas encore d&apos;illustrations.
                            </p>
                        )}

                        <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {illustrations.map((illustration) => (
                                <div
                                    key={illustration.id}
                                    className="overflow-hidden rounded-[1.55rem] border border-black/10 bg-white shadow-[0_16px_42px_rgba(0,0,0,0.06)]"
                                >
                                    <img
                                        src={illustration.imageUrl}
                                        alt={illustration.title}
                                        className="h-36 w-full object-cover"
                                    />
                                    <p className="mt-3 text-sm font-semibold text-[#181818]">
                                        {illustration.title}
                                    </p>
                                    <form
                                        action={illustration.destroyUrl}
                                        method="POST"
                                        className="mt-3"
                                    >
                                        <input type="hidden" name="_token" value={csrfToken} />
                                        <input type="hidden" name="_method" value="DELETE" />
                                        <button
                                            type="submit"
                                            className="w-full rounded-xl border border-black/10 bg-[#f6f6f6] px-3 py-2 text-xs font-semibold text-[#181818] transition hover:bg-[#ececec]"
                                        >
                                            Supprimer
                                        </button>
                                    </form>
                                </div>
                            ))}
                        </div>
                    </section>
                </div>
            </div>
        </div>
    );
};

export default Dashboard;
