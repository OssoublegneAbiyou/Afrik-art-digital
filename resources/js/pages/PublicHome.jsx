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
        <div className="min-h-screen bg-[linear-gradient(180deg,#fff3ea_0%,#fffaf4_28%,#eef7f2_62%,#f6efe8_100%)] text-[#31263d]">
            <div className="relative overflow-hidden">
                <div className="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(239,71,111,0.16),_transparent_36%),radial-gradient(circle_at_78%_18%,_rgba(20,184,166,0.18),_transparent_26%),radial-gradient(circle_at_18%_78%,_rgba(255,183,3,0.14),_transparent_24%),linear-gradient(135deg,rgba(255,255,255,0.2),rgba(255,248,239,0.08))]" />
                <div className="relative mx-auto max-w-6xl px-6 py-16">
                    <div className="grid gap-10 lg:grid-cols-[1.15fr_0.85fr] lg:items-end">
                        <div className="flex flex-col gap-6">
                            <span className="inline-flex w-fit items-center gap-2 rounded-full border border-orange-200 bg-white/70 px-4 py-1 text-xs uppercase tracking-[0.2em] text-[#a03d5c] shadow-sm backdrop-blur">
                                Afrik&apos;art Digital
                            </span>
                            <h1 className="max-w-2xl text-xl font-semibold leading-tight text-[#2b183d] md:text-4xl lg:text-[2.9rem]">
                                Art visuel et litterature dans une meme vitrine creative
                            </h1>
                            <p className="max-w-2xl text-lg text-[#5d4a67]">
                                Chaque jour, la page d&apos;accueil met un talent en avant pour l&apos;image et un autre pour le texte.
                                Explorez ensuite tous les profils dans leurs espaces dedies.
                            </p>
                            <div className="flex flex-wrap gap-4">
                                <a
                                    href={artistsIndexUrl}
                                    className="rounded-full bg-gradient-to-r from-[#ef476f] via-[#ff7b54] to-[#ffb703] px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-orange-200/60 transition hover:-translate-y-0.5"
                                >
                                    Voir les illustrateurs
                                </a>
                                <a
                                    href={writersIndexUrl}
                                    className="rounded-full border border-[#97d9bf] bg-white/75 px-6 py-3 text-sm font-semibold text-[#246057] transition hover:-translate-y-0.5 hover:bg-white"
                                >
                                    Voir les ecrivains
                                </a>
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="rounded-[1.75rem] border border-white/60 bg-white/75 p-6 shadow-[0_18px_50px_rgba(255,161,90,0.18)]">
                                <p className="text-sm text-[#8a5d4b]">Illustrateurs actifs</p>
                                <p className="mt-3 text-4xl font-semibold text-[#2b183d]">{totalArtists}</p>
                            </div>
                            <div className="rounded-[1.75rem] border border-white/60 bg-gradient-to-br from-[#eefdf9] to-[#fff8ef] p-6 shadow-[0_18px_50px_rgba(45,212,191,0.15)]">
                                <p className="text-sm text-[#44645f]">Ecrivains publies</p>
                                <p className="mt-3 text-4xl font-semibold text-[#21453f]">{totalWriters}</p>
                            </div>
                            <div className="rounded-[1.75rem] border border-white/60 bg-gradient-to-br from-[#fff0f3] to-[#fff7df] p-6 shadow-[0_18px_50px_rgba(239,71,111,0.12)] sm:col-span-2">
                                <p className="text-sm text-[#8a5d4b]">Mise en avant quotidienne</p>
                                <p className="mt-3 text-2xl font-semibold text-[#2b183d]">
                                    Un artiste du jour et un ecrivain du jour pour faire tourner la lumiere
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
                        className="block overflow-hidden rounded-[2rem] border border-white/70 bg-[linear-gradient(135deg,#fff4ec_0%,#fff7ef_52%,#fff1da_100%)] shadow-[0_20px_60px_rgba(255,161,90,0.18)] transition hover:-translate-y-1"
                    >
                        <div className="grid gap-0 md:grid-cols-[0.92fr_1.08fr]">
                            <div className="min-h-[260px] bg-[#f6ddd2]">
                                {featuredArtist?.highlightImageUrl ? (
                                    <img
                                        src={featuredArtist.highlightImageUrl}
                                        alt={featuredArtist.name}
                                        className="h-full w-full object-cover"
                                        loading="lazy"
                                    />
                                ) : (
                                    <div className="flex h-full items-center justify-center bg-[radial-gradient(circle_at_top,_rgba(239,71,111,0.18),_transparent_32%),linear-gradient(180deg,#ffe6d6_0%,#fff4e8_100%)] p-8 text-center text-sm font-semibold uppercase tracking-[0.26em] text-[#a03d5c]">
                                        Artiste du jour
                                    </div>
                                )}
                            </div>
                            <div className="p-6">
                                <span className="inline-flex rounded-full border border-orange-200 bg-white/80 px-3 py-1 text-xs uppercase tracking-[0.26em] text-[#a03d5c]">
                                    Artiste du jour
                                </span>
                                <h2 className="mt-4 text-2xl font-semibold text-[#2b183d]">
                                    {featuredArtist?.name || 'Illustrateur a decouvrir'}
                                </h2>
                                <p className="mt-3 text-sm leading-7 text-[#6f5c75]">
                                    {featuredArtist?.bio || 'Chaque jour, un profil visuel prend la scene sur la page d accueil.'}
                                </p>
                                <div className="mt-6 flex items-center justify-between gap-4">
                                    <span className="rounded-full bg-white/80 px-3 py-2 text-xs text-[#8a5d4b] shadow-sm">
                                        {featuredArtist?.illustrationsCount || 0} illustration(s)
                                    </span>
                                    <span className="text-sm font-semibold text-[#ef476f]">Voir le profil</span>
                                </div>
                            </div>
                        </div>
                    </a>

                    <a
                        href={featuredWriter?.profileUrl || '#'}
                        className="block overflow-hidden rounded-[2rem] border border-[#dbeee5] bg-[linear-gradient(135deg,#f7fff8_0%,#eef9f3_52%,#fff7ef_100%)] shadow-[0_20px_60px_rgba(45,212,191,0.14)] transition hover:-translate-y-1"
                    >
                        <div className="grid gap-0 md:grid-cols-[1.02fr_0.98fr]">
                            <div className="p-6">
                                <span className="inline-flex rounded-full border border-[#cfe8d8] bg-white/80 px-3 py-1 text-xs uppercase tracking-[0.26em] text-[#147d6d]">
                                    Ecrivain du jour
                                </span>
                                <h2 className="mt-4 text-2xl font-semibold text-[#21453f]">
                                    {featuredWriter?.name || 'Auteur a decouvrir'}
                                </h2>
                                <p className="mt-3 text-sm leading-7 text-[#44645f]">
                                    {featuredWriter?.bio || 'Chaque jour, une plume prend la lumiere avec un texte et un univers a explorer.'}
                                </p>
                                <div className="mt-6 flex items-center justify-between gap-4">
                                    <span className="rounded-full bg-white/80 px-3 py-2 text-xs text-[#44645f] shadow-sm">
                                        {featuredWriter?.documentsCount || 0} oeuvre(s)
                                    </span>
                                    <span className="text-sm font-semibold text-[#147d6d]">Voir le profil</span>
                                </div>
                            </div>
                            <div className="min-h-[260px] bg-[#dff2e7]">
                                {featuredWriter?.highlight?.coverImageUrl ? (
                                    <img
                                        src={featuredWriter.highlight.coverImageUrl}
                                        alt={featuredWriter.highlight.title || featuredWriter.name}
                                        className="h-full w-full object-cover"
                                        loading="lazy"
                                    />
                                ) : (
                                    <div className="flex h-full items-center justify-center bg-[radial-gradient(circle_at_top,_rgba(20,184,166,0.14),_transparent_32%),linear-gradient(180deg,#eef9f3_0%,#fff7ef_100%)] p-8 text-center text-sm font-semibold uppercase tracking-[0.26em] text-[#147d6d]">
                                        Ecrivain du jour
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
                        <h2 className="text-2xl font-semibold text-[#2b183d]">Illustrateurs</h2>
                        <p className="mt-2 text-sm text-[#7b627f]">
                            Galleries, matieres, couleurs et identites graphiques fortes.
                        </p>
                    </div>
                    <a
                        href={artistsIndexUrl}
                        className="rounded-full bg-white/70 px-4 py-2 text-sm font-semibold text-[#7b627f] shadow-sm transition hover:bg-white"
                    >
                        Voir tous les illustrateurs
                    </a>
                </div>

                {artists.length === 0 && (
                    <div className="mt-10 rounded-[1.75rem] border border-orange-100 bg-white/75 p-8 text-center text-[#6b5a6f] shadow-[0_18px_50px_rgba(255,161,90,0.12)]">
                        Aucun illustrateur pour l&apos;instant. Soyez le premier a rejoindre la scene.
                    </div>
                )}

                <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {artists.map((artist) => (
                        <a
                            key={artist.id}
                            href={artist.profileUrl}
                            className="group rounded-[1.75rem] border border-white/60 bg-white/75 p-6 shadow-[0_18px_50px_rgba(121,91,255,0.08)] backdrop-blur transition hover:-translate-y-1 hover:shadow-[0_22px_60px_rgba(239,71,111,0.14)]"
                        >
                            <div className="text-lg font-semibold text-[#2b183d] transition group-hover:text-[#ef476f]">
                                {artist.name}
                            </div>
                            <p className="mt-2 text-sm text-[#6f5c75]">
                                {artist.bio || 'Une voix visuelle en construction sur Afrik art Digital.'}
                            </p>

                            <div className="mt-4 grid grid-cols-2 gap-3">
                                {artist.illustrations.map((illustration) => (
                                    <div
                                        key={illustration.id}
                                        className="overflow-hidden rounded-2xl bg-gradient-to-br from-[#ffe3d3] to-[#fef6df]"
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
                        <h2 className="text-2xl font-semibold text-[#21453f]">Ecrivains</h2>
                        <p className="mt-2 text-sm text-[#44645f]">
                            Textes, recits, essais et poesie avec une illustration de reference.
                        </p>
                    </div>
                    <a
                        href={writersIndexUrl}
                        className="rounded-full bg-white/80 px-4 py-2 text-sm font-semibold text-[#44645f] shadow-sm transition hover:bg-white"
                    >
                        Voir tous les ecrivains
                    </a>
                </div>

                {writers.length === 0 && (
                    <div className="mt-10 rounded-[1.75rem] border border-[#dbeee5] bg-white/80 p-8 text-center text-[#44645f] shadow-[0_18px_50px_rgba(45,212,191,0.12)]">
                        Aucun ecrivain pour l&apos;instant. Ouvrez la bibliotheque du projet.
                    </div>
                )}

                <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {writers.map((writer) => (
                        <a
                            key={writer.id}
                            href={writer.profileUrl}
                            className="group overflow-hidden rounded-[1.75rem] border border-[#dbeee5] bg-[linear-gradient(135deg,#f7fff8_0%,#eef9f3_52%,#fff7ef_100%)] shadow-[0_18px_50px_rgba(45,212,191,0.12)] transition hover:-translate-y-1"
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
                                    <div className="text-lg font-semibold text-[#21453f] transition group-hover:text-[#147d6d]">
                                        {writer.name}
                                    </div>
                                    {writer.highlight?.fileType && (
                                        <span className="rounded-full bg-white px-3 py-1 text-xs font-semibold text-[#147d6d] shadow-sm">
                                            {writer.highlight.fileType}
                                        </span>
                                    )}
                                </div>
                                <p className="text-sm text-[#44645f]">
                                    {writer.bio || 'Une plume a decouvrir, entre recit personnel et imaginaire.'}
                                </p>
                                <div className="flex items-center justify-between text-sm text-[#44645f]">
                                    <span>{writer.documentsCount} oeuvre(s)</span>
                                    <span className="font-semibold text-[#9a5a40]">Voir le profil</span>
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
