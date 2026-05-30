import React from 'react';

const PublicHome = ({
    artists = [],
    writers = [],
    totalArtists = 0,
    totalWriters = 0,
    artistsIndexUrl = '#artistes',
    writersIndexUrl = '#auteurs',
    featuredArtist = null,
    featuredWriter = null,
}) => {
    return (
        <div className="min-h-screen bg-[linear-gradient(180deg,#fff8ea_0%,#fffdf7_36%,#f3efe2_72%,#fff8ea_100%)] text-[#17110d]">
            <div className="relative overflow-hidden">
                <div className="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(245,183,0,0.20),_transparent_34%),radial-gradient(circle_at_78%_18%,_rgba(31,122,92,0.18),_transparent_25%),radial-gradient(circle_at_18%_78%,_rgba(200,76,49,0.16),_transparent_24%),linear-gradient(135deg,rgba(255,248,234,0.34),rgba(36,59,107,0.06))]" />
                <div className="relative mx-auto max-w-6xl px-6 py-16">
                    <div className="grid gap-10 lg:grid-cols-[1.15fr_0.85fr] lg:items-end">
                        <div className="flex flex-col gap-6">
                            <span className="inline-flex w-fit items-center gap-2 rounded-full border border-[#f5b700]/35 bg-[#fff8ea]/80 px-4 py-1 text-xs uppercase tracking-[0.2em] text-[#c84c31] shadow-sm backdrop-blur">
                                Afrik&apos;art Digital
                            </span>
                            <h1 className="max-w-2xl text-xl font-extrabold leading-tight text-[#17110d] md:text-4xl lg:text-[2.9rem]">
                                Art visuel et littérature dans une même vitrine créative
                            </h1>
                            <p className="max-w-2xl text-lg leading-8 text-[#594234]">
                                Chaque jour, la page d&apos;accueil met un talent en avant pour l&apos;image et un autre pour le texte.
                                Explorez ensuite tous les profils dans leurs espaces dédiés.
                            </p>
                            <div className="flex flex-wrap gap-4">
                                <a
                                    href={artistsIndexUrl}
                                    className="rounded-full bg-gradient-to-r from-[#f5b700] via-[#c84c31] to-[#e85d75] px-6 py-3 text-sm font-bold text-[#17110d] shadow-lg shadow-[#c84c31]/20 transition hover:-translate-y-0.5"
                                >
                                    Voir les illustrateurs
                                </a>
                                <a
                                    href={writersIndexUrl}
                                    className="rounded-full border border-[#1f7a5c]/30 bg-[#fff8ea]/80 px-6 py-3 text-sm font-bold text-[#1f7a5c] transition hover:-translate-y-0.5 hover:bg-white"
                                >
                                    Voir les écrivains
                                </a>
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="rounded-[1.75rem] border border-[#f5b700]/25 bg-[#fff8ea]/85 p-6 shadow-[0_18px_50px_rgba(200,76,49,0.12)]">
                                <p className="text-sm font-semibold text-[#c84c31]">Illustrateurs actifs</p>
                                <p className="mt-3 text-4xl font-extrabold text-[#17110d]">{totalArtists}</p>
                            </div>
                            <div className="rounded-[1.75rem] border border-[#1f7a5c]/20 bg-gradient-to-br from-[#eef7ed] to-[#fff8ea] p-6 shadow-[0_18px_50px_rgba(31,122,92,0.12)]">
                                <p className="text-sm text-[#335247]">Écrivains publiés</p>
                                <p className="mt-3 text-4xl font-extrabold text-[#1f7a5c]">{totalWriters}</p>
                            </div>
                            <div className="rounded-[1.75rem] border border-[#243b6b]/15 bg-gradient-to-br from-[#17110d] via-[#243b6b] to-[#1f7a5c] p-6 shadow-[0_18px_50px_rgba(36,59,107,0.18)] sm:col-span-2">
                                <p className="text-sm font-semibold text-[#f5b700]">Mise en avant quotidienne</p>
                                <p className="mt-3 text-2xl font-bold text-[#fff8ea]">
                                    Un artiste du jour et un écrivain du jour pour faire tourner la lumière
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <section className="mx-auto max-w-6xl px-6 pb-4 pt-2">
                <div className="grid gap-6 lg:grid-cols-2">
                    <a
                        href={featuredArtist?.profileUrl || '#'}
                        className="block overflow-hidden rounded-[2rem] border border-[#f5b700]/25 bg-[linear-gradient(135deg,#fff8ea_0%,#fffdf7_50%,#f6e3c0_100%)] shadow-[0_20px_60px_rgba(200,76,49,0.16)] transition hover:-translate-y-1"
                    >
                        <div className="grid gap-0 md:grid-cols-[0.92fr_1.08fr]">
                            <div className="min-h-[260px] bg-[#c84c31]/20">
                                {featuredArtist?.highlightImageUrl ? (
                                    <img
                                        src={featuredArtist.highlightImageUrl}
                                        alt={featuredArtist.name}
                                        className="h-full w-full object-cover"
                                        loading="lazy"
                                    />
                                ) : (
                                    <div className="flex h-full items-center justify-center bg-[radial-gradient(circle_at_top,_rgba(245,183,0,0.26),_transparent_32%),linear-gradient(180deg,#c84c31_0%,#fff8ea_100%)] p-8 text-center text-sm font-semibold uppercase tracking-[0.26em] text-[#c84c31]">
                                        Artiste du jour
                                    </div>
                                )}
                            </div>
                            <div className="p-6">
                                <span className="inline-flex rounded-full border border-[#f5b700]/30 bg-white/80 px-3 py-1 text-xs uppercase tracking-[0.26em] text-[#c84c31]">
                                    Artiste du jour
                                </span>
                                <h2 className="mt-4 text-2xl font-bold text-[#17110d]">
                                    {featuredArtist?.name || 'Illustrateur à découvrir'}
                                </h2>
                                <p className="mt-3 text-sm leading-7 text-[#594234]">
                                    {featuredArtist?.bio || "Chaque jour, un profil visuel prend la scène sur la page d'accueil."}
                                </p>
                                <div className="mt-6 flex items-center justify-between gap-4">
                                    <span className="rounded-full bg-white/80 px-3 py-2 text-xs text-[#8a5d4b] shadow-sm">
                                        {featuredArtist?.illustrationsCount || 0} illustration(s)
                                    </span>
                                    <span className="text-sm font-semibold text-[#c84c31]">Voir le profil</span>
                                </div>
                            </div>
                        </div>
                    </a>

                    <a
                        href={featuredWriter?.profileUrl || '#'}
                        className="block overflow-hidden rounded-[2rem] border border-[#1f7a5c]/20 bg-[linear-gradient(135deg,#eef7ed_0%,#fff8ea_58%,#f3efe2_100%)] shadow-[0_20px_60px_rgba(31,122,92,0.14)] transition hover:-translate-y-1"
                    >
                        <div className="grid gap-0 md:grid-cols-[1.02fr_0.98fr]">
                            <div className="p-6">
                                <span className="inline-flex rounded-full border border-[#1f7a5c]/25 bg-white/80 px-3 py-1 text-xs uppercase tracking-[0.26em] text-[#1f7a5c]">
                                    Écrivain du jour
                                </span>
                                <h2 className="mt-4 text-2xl font-semibold text-[#1f7a5c]">
                                    {featuredWriter?.name || 'Auteur à découvrir'}
                                </h2>
                                <p className="mt-3 text-sm leading-7 text-[#335247]">
                                    {featuredWriter?.bio || 'Chaque jour, une plume prend la lumière avec un texte et un univers à explorer.'}
                                </p>
                                <div className="mt-6 flex items-center justify-between gap-4">
                                    <span className="rounded-full bg-white/80 px-3 py-2 text-xs text-[#335247] shadow-sm">
                                        {featuredWriter?.documentsCount || 0} œuvre(s)
                                    </span>
                                    <span className="text-sm font-semibold text-[#1f7a5c]">Voir le profil</span>
                                </div>
                            </div>
                            <div className="min-h-[260px] bg-[#1f7a5c]/15">
                                {featuredWriter?.highlight?.coverImageUrl ? (
                                    <img
                                        src={featuredWriter.highlight.coverImageUrl}
                                        alt={featuredWriter.highlight.title || featuredWriter.name}
                                        className="h-full w-full object-cover"
                                        loading="lazy"
                                    />
                                ) : (
                                    <div className="flex h-full items-center justify-center bg-[radial-gradient(circle_at_top,_rgba(31,122,92,0.18),_transparent_32%),linear-gradient(180deg,#eef7ed_0%,#fff8ea_100%)] p-8 text-center text-sm font-semibold uppercase tracking-[0.26em] text-[#1f7a5c]">
                                        Écrivain du jour
                                    </div>
                                )}
                            </div>
                        </div>
                    </a>
                </div>
            </section>

            <section id="artistes" className="mx-auto max-w-6xl px-6 pb-10 pt-8">
                <div className="flex items-end justify-between gap-4">
                    <div>
                        <h2 className="text-2xl font-bold text-[#17110d]">Illustrateurs</h2>
                        <p className="mt-2 text-sm text-[#594234]">
                            Galeries, matières, couleurs et identités graphiques fortes.
                        </p>
                    </div>
                    <a
                        href={artistsIndexUrl}
                        className="rounded-full border border-[#f5b700]/25 bg-[#fff8ea]/85 px-4 py-2 text-sm font-semibold text-[#594234] shadow-sm transition hover:bg-white"
                    >
                        Voir tous les illustrateurs
                    </a>
                </div>

                {artists.length === 0 && (
                    <div className="mt-10 rounded-[1.75rem] border border-[#f5b700]/25 bg-white/75 p-8 text-center text-[#594234] shadow-[0_18px_50px_rgba(200,76,49,0.12)]">
                        Aucun illustrateur pour l&apos;instant. Soyez le premier à rejoindre la scène.
                    </div>
                )}

                <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {artists.map((artist) => (
                        <a
                            key={artist.id}
                            href={artist.profileUrl}
                            className="group rounded-[1.75rem] border border-[#f5b700]/20 bg-[#fff8ea]/85 p-6 shadow-[0_18px_50px_rgba(36,59,107,0.10)] backdrop-blur transition hover:-translate-y-1 hover:shadow-[0_22px_60px_rgba(200,76,49,0.16)]"
                        >
                            <div className="text-lg font-semibold text-[#17110d] transition group-hover:text-[#c84c31]">
                                {artist.name}
                            </div>
                            <p className="mt-2 text-sm text-[#594234]">
                                {artist.bio || 'Une voix visuelle en construction sur Afrik art Digital.'}
                            </p>

                            <div className="mt-4 grid grid-cols-2 gap-3">
                                {artist.illustrations.map((illustration) => (
                                    <div
                                        key={illustration.id}
                                        className="overflow-hidden rounded-2xl bg-gradient-to-br from-[#c84c31]/25 to-[#f5b700]/20"
                                    >
                                        <img
                                            src={illustration.imageUrl}
                                            alt={illustration.title}
                                            className="h-28 w-full object-cover transition duration-300 group-hover:scale-105"
                                            loading="lazy"
                                        />
                                    </div>
                                ))}
                            </div>
                        </a>
                    ))}
                </div>
            </section>

            <section id="auteurs" className="mx-auto max-w-6xl px-6 pb-20 pt-8">
                <div className="flex items-end justify-between gap-4">
                    <div>
                        <h2 className="text-2xl font-semibold text-[#1f7a5c]">Écrivains</h2>
                        <p className="mt-2 text-sm text-[#335247]">
                            Textes, récits, essais et poésie avec une illustration de référence.
                        </p>
                    </div>
                    <a
                        href={writersIndexUrl}
                        className="rounded-full bg-white/80 px-4 py-2 text-sm font-semibold text-[#335247] shadow-sm transition hover:bg-white"
                    >
                        Voir tous les écrivains
                    </a>
                </div>

                {writers.length === 0 && (
                    <div className="mt-10 rounded-[1.75rem] border border-[#dbeee5] bg-white/80 p-8 text-center text-[#335247] shadow-[0_18px_50px_rgba(31,122,92,0.12)]">
                        Aucun écrivain pour l&apos;instant. Ouvrez la bibliothèque du projet.
                    </div>
                )}

                <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {writers.map((writer) => (
                        <a
                            key={writer.id}
                            href={writer.profileUrl}
                            className="group overflow-hidden rounded-[1.75rem] border border-[#1f7a5c]/20 bg-[linear-gradient(135deg,#eef7ed_0%,#fff8ea_58%,#f3efe2_100%)] shadow-[0_18px_50px_rgba(31,122,92,0.12)] transition hover:-translate-y-1"
                        >
                            {writer.highlight && (
                                <img
                                    src={writer.highlight.coverImageUrl}
                                    alt={writer.highlight.title}
                                    className="h-52 w-full object-cover"
                                    loading="lazy"
                                />
                            )}
                            <div className="space-y-3 p-6">
                                <div className="flex items-center justify-between gap-3">
                                    <div className="text-lg font-semibold text-[#1f7a5c] transition group-hover:text-[#c84c31]">
                                        {writer.name}
                                    </div>
                                    {writer.highlight?.fileType && (
                                        <span className="rounded-full bg-white px-3 py-1 text-xs font-semibold text-[#1f7a5c] shadow-sm">
                                            {writer.highlight.fileType}
                                        </span>
                                    )}
                                </div>
                                <p className="text-sm text-[#335247]">
                                    {writer.bio || 'Une plume à découvrir, entre récit personnel et imaginaire.'}
                                </p>
                                <div className="flex items-center justify-between text-sm text-[#335247]">
                                    <span>{writer.documentsCount} œuvre(s)</span>
                                    <span className="font-semibold text-[#c84c31]">Voir le profil</span>
                                </div>
                            </div>
                        </a>
                    ))}
                </div>
            </section>
        </div>
    );
};

export default PublicHome;
