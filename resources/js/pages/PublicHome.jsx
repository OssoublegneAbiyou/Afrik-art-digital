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
        <div className="min-h-screen bg-[#fbf7ef] text-[#201a16]">
            <section className="border-b border-[#e8ddcf] bg-[#fffdf8]">
                <div className="mx-auto max-w-6xl px-6 py-14">
                    <div className="grid gap-10 lg:grid-cols-[1.15fr_0.85fr] lg:items-end">
                        <div className="flex flex-col gap-6">
                            <span className="inline-flex w-fit rounded-full border border-[#d8c7b5] bg-[#fbf7ef] px-4 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-[#9a4f2c]">
                                Afrik&apos;art Digital
                            </span>
                            <h1 className="max-w-2xl text-3xl font-extrabold leading-tight text-[#201a16] md:text-5xl">
                                Art visuel et littérature dans une même vitrine créative
                            </h1>
                            <p className="max-w-2xl text-base leading-8 text-[#6a5a4d] md:text-lg">
                                Chaque jour, la page d&apos;accueil met un talent en avant pour l&apos;image et un autre pour le texte.
                                Explorez ensuite tous les profils dans leurs espaces dédiés.
                            </p>
                            <div className="flex flex-wrap gap-3">
                                <a
                                    href={artistsIndexUrl}
                                    className="rounded-full bg-[#9a4f2c] px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#7e3f23]"
                                >
                                    Voir les illustrateurs
                                </a>
                                <a
                                    href={writersIndexUrl}
                                    className="rounded-full border border-[#2f6b4f]/30 bg-white px-6 py-3 text-sm font-bold text-[#2f6b4f] shadow-sm transition hover:bg-[#f3f7f1]"
                                >
                                    Voir les écrivains
                                </a>
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="rounded-2xl border border-[#e8ddcf] bg-white p-6 shadow-sm">
                                <p className="text-sm font-semibold text-[#9a4f2c]">Illustrateurs actifs</p>
                                <p className="mt-3 text-4xl font-extrabold text-[#201a16]">{totalArtists}</p>
                            </div>
                            <div className="rounded-2xl border border-[#d8e2d6] bg-white p-6 shadow-sm">
                                <p className="text-sm font-semibold text-[#2f6b4f]">Écrivains publiés</p>
                                <p className="mt-3 text-4xl font-extrabold text-[#201a16]">{totalWriters}</p>
                            </div>
                            <div className="rounded-2xl border border-[#e8ddcf] bg-[#201a16] p-6 shadow-sm sm:col-span-2">
                                <p className="text-sm font-semibold text-[#d7aa45]">Mise en avant quotidienne</p>
                                <p className="mt-3 text-xl font-bold text-[#fffdf8]">
                                    Un artiste du jour et un écrivain du jour pour faire tourner la lumière
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-6xl px-6 pb-4 pt-10">
                <div className="grid gap-6 lg:grid-cols-2">
                    <a
                        href={featuredArtist?.profileUrl || '#'}
                        className="block overflow-hidden rounded-2xl border border-[#e8ddcf] bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div className="grid gap-0 md:grid-cols-[0.92fr_1.08fr]">
                            <div className="min-h-[260px] bg-[#efe4d6]">
                                {featuredArtist?.highlightImageUrl ? (
                                    <img
                                        src={featuredArtist.highlightImageUrl}
                                        alt={featuredArtist.name}
                                        className="h-full w-full object-cover"
                                        loading="lazy"
                                    />
                                ) : (
                                    <div className="flex h-full items-center justify-center p-8 text-center text-sm font-semibold uppercase tracking-[0.22em] text-[#9a4f2c]">
                                        Artiste du jour
                                    </div>
                                )}
                            </div>
                            <div className="p-6">
                                <span className="inline-flex rounded-full border border-[#e8ddcf] bg-[#fbf7ef] px-3 py-1 text-xs uppercase tracking-[0.22em] text-[#9a4f2c]">
                                    Artiste du jour
                                </span>
                                <h2 className="mt-4 text-2xl font-bold text-[#201a16]">
                                    {featuredArtist?.name || 'Illustrateur à découvrir'}
                                </h2>
                                <p className="mt-3 text-sm leading-7 text-[#6a5a4d]">
                                    {featuredArtist?.bio || "Chaque jour, un profil visuel prend la scène sur la page d'accueil."}
                                </p>
                                <div className="mt-6 flex items-center justify-between gap-4">
                                    <span className="rounded-full bg-[#fbf7ef] px-3 py-2 text-xs text-[#6a5a4d]">
                                        {featuredArtist?.illustrationsCount || 0} illustration(s)
                                    </span>
                                    <span className="text-sm font-semibold text-[#9a4f2c]">Voir le profil</span>
                                </div>
                            </div>
                        </div>
                    </a>

                    <a
                        href={featuredWriter?.profileUrl || '#'}
                        className="block overflow-hidden rounded-2xl border border-[#d8e2d6] bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div className="grid gap-0 md:grid-cols-[1.02fr_0.98fr]">
                            <div className="p-6">
                                <span className="inline-flex rounded-full border border-[#d8e2d6] bg-[#f3f7f1] px-3 py-1 text-xs uppercase tracking-[0.22em] text-[#2f6b4f]">
                                    Écrivain du jour
                                </span>
                                <h2 className="mt-4 text-2xl font-semibold text-[#201a16]">
                                    {featuredWriter?.name || 'Auteur à découvrir'}
                                </h2>
                                <p className="mt-3 text-sm leading-7 text-[#53665a]">
                                    {featuredWriter?.bio || 'Chaque jour, une plume prend la lumière avec un texte et un univers à explorer.'}
                                </p>
                                <div className="mt-6 flex items-center justify-between gap-4">
                                    <span className="rounded-full bg-[#f3f7f1] px-3 py-2 text-xs text-[#53665a]">
                                        {featuredWriter?.documentsCount || 0} œuvre(s)
                                    </span>
                                    <span className="text-sm font-semibold text-[#2f6b4f]">Voir le profil</span>
                                </div>
                            </div>
                            <div className="min-h-[260px] bg-[#edf3ea]">
                                {featuredWriter?.highlight?.coverImageUrl ? (
                                    <img
                                        src={featuredWriter.highlight.coverImageUrl}
                                        alt={featuredWriter.highlight.title || featuredWriter.name}
                                        className="h-full w-full object-cover"
                                        loading="lazy"
                                    />
                                ) : (
                                    <div className="flex h-full items-center justify-center p-8 text-center text-sm font-semibold uppercase tracking-[0.22em] text-[#2f6b4f]">
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
                        <h2 className="text-2xl font-bold text-[#201a16]">Illustrateurs</h2>
                        <p className="mt-2 text-sm text-[#6a5a4d]">
                            Galeries, matières, couleurs et identités graphiques fortes.
                        </p>
                    </div>
                    <a
                        href={artistsIndexUrl}
                        className="rounded-full border border-[#d8c7b5] bg-white px-4 py-2 text-sm font-semibold text-[#6a5a4d] shadow-sm transition hover:bg-[#fbf7ef]"
                    >
                        Voir tous les illustrateurs
                    </a>
                </div>

                {artists.length === 0 && (
                    <div className="mt-10 rounded-2xl border border-[#e8ddcf] bg-white p-8 text-center text-[#6a5a4d] shadow-sm">
                        Aucun illustrateur pour l&apos;instant. Soyez le premier à rejoindre la scène.
                    </div>
                )}

                <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {artists.map((artist) => (
                        <a
                            key={artist.id}
                            href={artist.profileUrl}
                            className="group rounded-2xl border border-[#e8ddcf] bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                        >
                            <div className="text-lg font-semibold text-[#201a16] transition group-hover:text-[#9a4f2c]">
                                {artist.name}
                            </div>
                            <p className="mt-2 text-sm text-[#6a5a4d]">
                                {artist.bio || 'Une voix visuelle en construction sur Afrik art Digital.'}
                            </p>

                            <div className="mt-4 grid grid-cols-2 gap-3">
                                {artist.illustrations.map((illustration) => (
                                    <div
                                        key={illustration.id}
                                        className="overflow-hidden rounded-xl bg-[#efe4d6]"
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
                        <h2 className="text-2xl font-semibold text-[#201a16]">Écrivains</h2>
                        <p className="mt-2 text-sm text-[#53665a]">
                            Textes, récits, essais et poésie avec une illustration de référence.
                        </p>
                    </div>
                    <a
                        href={writersIndexUrl}
                        className="rounded-full border border-[#d8e2d6] bg-white px-4 py-2 text-sm font-semibold text-[#53665a] shadow-sm transition hover:bg-[#f3f7f1]"
                    >
                        Voir tous les écrivains
                    </a>
                </div>

                {writers.length === 0 && (
                    <div className="mt-10 rounded-2xl border border-[#d8e2d6] bg-white p-8 text-center text-[#53665a] shadow-sm">
                        Aucun écrivain pour l&apos;instant. Ouvrez la bibliothèque du projet.
                    </div>
                )}

                <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {writers.map((writer) => (
                        <a
                            key={writer.id}
                            href={writer.profileUrl}
                            className="group overflow-hidden rounded-2xl border border-[#d8e2d6] bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
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
                                    <div className="text-lg font-semibold text-[#201a16] transition group-hover:text-[#2f6b4f]">
                                        {writer.name}
                                    </div>
                                    {writer.highlight?.fileType && (
                                        <span className="rounded-full bg-[#f3f7f1] px-3 py-1 text-xs font-semibold text-[#2f6b4f]">
                                            {writer.highlight.fileType}
                                        </span>
                                    )}
                                </div>
                                <p className="text-sm text-[#53665a]">
                                    {writer.bio || 'Une plume à découvrir, entre récit personnel et imaginaire.'}
                                </p>
                                <div className="flex items-center justify-between text-sm text-[#53665a]">
                                    <span>{writer.documentsCount} œuvre(s)</span>
                                    <span className="font-semibold text-[#2f6b4f]">Voir le profil</span>
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
