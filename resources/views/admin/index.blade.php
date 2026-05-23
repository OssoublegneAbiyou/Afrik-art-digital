<x-app-layout>
    <section class="mx-auto max-w-7xl px-6 py-12">
        <div class="rounded-[2rem] border border-orange-100 bg-[linear-gradient(135deg,#fff4ec_0%,#fffaf4_48%,#eef7f2_100%)] p-8 shadow-[0_20px_60px_rgba(255,161,90,0.16)]">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full border border-orange-200 bg-white/80 px-4 py-1 text-xs uppercase tracking-[0.28em] text-[#a03d5c]">
                        Panneau admin
                    </span>
                    <h1 class="mt-4 text-3xl font-semibold text-[#2b183d] md:text-4xl">Pilotage de la plateforme</h1>
                    <p class="mt-3 max-w-3xl text-sm leading-7 text-[#6f5c75]">
                        Gere les comptes, la mise en avant quotidienne, les contenus publies et les options principales du site.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3 text-sm">
                    <a href="#mise-en-avant" class="rounded-full bg-white/80 px-4 py-2 font-semibold text-[#2b183d] shadow-sm">Mise en avant</a>
                    <a href="#comptes" class="rounded-full bg-white/80 px-4 py-2 font-semibold text-[#2b183d] shadow-sm">Comptes</a>
                    <a href="#contenus" class="rounded-full bg-white/80 px-4 py-2 font-semibold text-[#2b183d] shadow-sm">Contenus</a>
                    <a href="#options" class="rounded-full bg-white/80 px-4 py-2 font-semibold text-[#2b183d] shadow-sm">Options</a>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <section class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-[1.6rem] border border-white/70 bg-white/85 p-6 shadow-[0_18px_50px_rgba(121,91,255,0.08)]">
                <p class="text-sm text-[#8a5d4b]">Utilisateurs</p>
                <p class="mt-3 text-4xl font-semibold text-[#2b183d]">{{ $stats['users'] }}</p>
                <p class="mt-2 text-xs uppercase tracking-[0.2em] text-[#7b627f]">{{ $stats['admins'] }} admin(s)</p>
            </article>
            <article class="rounded-[1.6rem] border border-white/70 bg-white/85 p-6 shadow-[0_18px_50px_rgba(121,91,255,0.08)]">
                <p class="text-sm text-[#8a5d4b]">Illustrateurs</p>
                <p class="mt-3 text-4xl font-semibold text-[#2b183d]">{{ $stats['artists'] }}</p>
                <p class="mt-2 text-xs uppercase tracking-[0.2em] text-[#7b627f]">{{ $stats['illustrations'] }} visuel(s)</p>
            </article>
            <article class="rounded-[1.6rem] border border-white/70 bg-white/85 p-6 shadow-[0_18px_50px_rgba(121,91,255,0.08)]">
                <p class="text-sm text-[#44645f]">Ecrivains</p>
                <p class="mt-3 text-4xl font-semibold text-[#21453f]">{{ $stats['writers'] }}</p>
                <p class="mt-2 text-xs uppercase tracking-[0.2em] text-[#44645f]">{{ $stats['documents'] }} oeuvre(s)</p>
            </article>
            <article class="rounded-[1.6rem] border border-white/70 bg-white/85 p-6 shadow-[0_18px_50px_rgba(121,91,255,0.08)]">
                <p class="text-sm text-[#6d5aa8]">Visiteurs</p>
                <p class="mt-3 text-4xl font-semibold text-[#5e4b8b]">{{ $stats['visitors'] }}</p>
                <p class="mt-2 text-xs uppercase tracking-[0.2em] text-[#6d5aa8]">comptes de veille</p>
            </article>
            <article class="rounded-[1.6rem] border border-white/70 bg-white/85 p-6 shadow-[0_18px_50px_rgba(121,91,255,0.08)]">
                <p class="text-sm text-[#44645f]">Stockage utilise</p>
                <p class="mt-3 text-4xl font-semibold text-[#21453f]">{{ $stats['storageUsedGb'] }}</p>
                <p class="mt-2 text-xs uppercase tracking-[0.2em] text-[#44645f]">Go sur la plateforme</p>
            </article>
        </section>

        <section id="mise-en-avant" class="mt-10 grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
            <div class="rounded-[1.8rem] border border-orange-100 bg-white/85 p-6 shadow-[0_18px_50px_rgba(255,161,90,0.12)]">
                <h2 class="text-2xl font-semibold text-[#2b183d]">Mise en avant du jour</h2>
                <p class="mt-2 text-sm text-[#6f5c75]">Choisis le profil artiste et le profil ecrivain a mettre en lumiere aujourd hui.</p>

                <form method="POST" action="{{ route('admin.featured.update') }}" class="mt-6 grid gap-4">
                    @csrf
                    <div>
                        <label for="artist_id" class="text-sm font-semibold text-[#2b183d]">Artiste du jour</label>
                        <select id="artist_id" name="artist_id" class="mt-2 w-full rounded-2xl border border-orange-100 bg-[#fffaf4] px-4 py-3 text-sm text-[#2b183d]">
                            <option value="">Selection automatique</option>
                            @foreach ($artists as $artist)
                                <option value="{{ $artist->id }}" @selected(optional($todayFeatured)->artist_id === $artist->id)>
                                    {{ $artist->user?->name ?? 'Artiste' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="writer_id" class="text-sm font-semibold text-[#21453f]">Ecrivain du jour</label>
                        <select id="writer_id" name="writer_id" class="mt-2 w-full rounded-2xl border border-[#dbeee5] bg-[#f7fff8] px-4 py-3 text-sm text-[#21453f]">
                            <option value="">Selection automatique</option>
                            @foreach ($writers as $writer)
                                <option value="{{ $writer->id }}" @selected(optional($todayFeatured)->writer_id === $writer->id)>
                                    {{ $writer->user?->name ?? 'Ecrivain' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="rounded-full bg-gradient-to-r from-[#ef476f] via-[#ff7b54] to-[#14b8a6] px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-orange-200/60">
                        Enregistrer la mise en avant
                    </button>
                </form>
            </div>

            <div class="rounded-[1.8rem] border border-[#dbeee5] bg-white/85 p-6 shadow-[0_18px_50px_rgba(45,212,191,0.12)]">
                <h2 class="text-2xl font-semibold text-[#21453f]">Selection actuelle</h2>
                <div class="mt-6 grid gap-4">
                    <div class="rounded-[1.3rem] border border-orange-100 bg-[#fffaf4] p-4">
                        <p class="text-xs uppercase tracking-[0.26em] text-[#a03d5c]">Artiste du jour</p>
                        <p class="mt-2 text-lg font-semibold text-[#2b183d]">{{ optional(optional($todayFeatured)->artist)->user?->name ?? 'Automatique' }}</p>
                    </div>
                    <div class="rounded-[1.3rem] border border-[#dbeee5] bg-[#f7fff8] p-4">
                        <p class="text-xs uppercase tracking-[0.26em] text-[#147d6d]">Ecrivain du jour</p>
                        <p class="mt-2 text-lg font-semibold text-[#21453f]">{{ optional(optional($todayFeatured)->writer)->user?->name ?? 'Automatique' }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="comptes" class="mt-10 rounded-[1.8rem] border border-white/70 bg-white/85 p-6 shadow-[0_18px_50px_rgba(121,91,255,0.08)]">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold text-[#2b183d]">Comptes</h2>
                    <p class="mt-2 text-sm text-[#6f5c75]">Change le type de compte ou donne les droits admin.</p>
                </div>
                <span class="rounded-full bg-[#fffaf4] px-4 py-2 text-sm text-[#7b627f] shadow-sm">{{ $users->count() }} compte(s)</span>
            </div>

            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-orange-100 text-sm">
                    <thead>
                        <tr class="text-left text-[#7b627f]">
                            <th class="px-3 py-3 font-semibold">Nom</th>
                            <th class="px-3 py-3 font-semibold">Email</th>
                            <th class="px-3 py-3 font-semibold">Type</th>
                            <th class="px-3 py-3 font-semibold">Admin</th>
                            <th class="px-3 py-3 font-semibold">Stockage</th>
                            <th class="px-3 py-3 font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-orange-50">
                        @foreach ($users as $user)
                            <tr>
                                <td class="px-3 py-4 font-semibold text-[#2b183d]">{{ $user->name }}</td>
                                <td class="px-3 py-4 text-[#6f5c75]">{{ $user->email }}</td>
                                <td class="px-3 py-4">
                                    <select name="account_type" form="user-form-{{ $user->id }}" class="rounded-xl border border-orange-100 bg-[#fffaf4] px-3 py-2 text-sm text-[#2b183d]">
                                            <option value="artist" @selected($user->account_type === 'artist')>Illustrateur</option>
                                            <option value="writer" @selected($user->account_type === 'writer')>Ecrivain</option>
                                            <option value="visitor" @selected($user->account_type === 'visitor')>Visiteur</option>
                                    </select>
                                </td>
                                <td class="px-3 py-4">
                                    <input type="hidden" form="user-form-{{ $user->id }}" name="is_admin" value="0">
                                    <label class="inline-flex items-center gap-2 text-[#6f5c75]">
                                        <input type="checkbox" form="user-form-{{ $user->id }}" name="is_admin" value="1" @checked($user->is_admin) class="rounded border-orange-200 text-[#ef476f] focus:ring-[#ef476f]/30">
                                        <span>Oui</span>
                                    </label>
                                </td>
                                <td class="px-3 py-4 text-[#6f5c75]">{{ number_format($user->storageUsedBytes() / 1024 / 1024, 1) }} Mo</td>
                                <td class="px-3 py-4">
                                    <form id="user-form-{{ $user->id }}" method="POST" action="{{ route('admin.users.update', $user) }}">
                                        @csrf
                                        @method('PATCH')
                                    </form>
                                        <button type="submit" form="user-form-{{ $user->id }}" class="rounded-full bg-[#181818] px-4 py-2 text-xs font-semibold text-white">
                                            Mettre a jour
                                        </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section id="contenus" class="mt-10 grid gap-6 lg:grid-cols-2">
            <div class="rounded-[1.8rem] border border-white/70 bg-white/85 p-6 shadow-[0_18px_50px_rgba(121,91,255,0.08)]">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-semibold text-[#2b183d]">Dernieres illustrations</h2>
                        <p class="mt-2 text-sm text-[#6f5c75]">Controle rapide des visuels publies.</p>
                    </div>
                    <span class="rounded-full bg-[#fffaf4] px-4 py-2 text-sm text-[#7b627f] shadow-sm">{{ $latestIllustrations->count() }} element(s)</span>
                </div>

                <div class="mt-6 grid gap-4">
                    @forelse ($latestIllustrations as $illustration)
                        <article class="flex gap-4 rounded-[1.4rem] border border-orange-100 bg-[#fffaf4] p-4">
                            <img src="{{ asset('storage/' . $illustration->image_path) }}" alt="{{ $illustration->title }}" class="h-20 w-20 rounded-2xl object-cover">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-[#2b183d]">{{ $illustration->title }}</p>
                                <p class="mt-1 text-sm text-[#6f5c75]">{{ $illustration->artist?->user?->name ?? 'Artiste' }}</p>
                                <form method="POST" action="{{ route('illustrations.destroy', $illustration) }}" class="mt-3">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-full border border-red-200 bg-red-50 px-4 py-2 text-xs font-semibold text-red-700">
                                        Supprimer
                                    </button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <p class="text-sm text-[#6f5c75]">Aucune illustration recente.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-[1.8rem] border border-[#dbeee5] bg-white/85 p-6 shadow-[0_18px_50px_rgba(45,212,191,0.12)]">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-semibold text-[#21453f]">Dernieres oeuvres</h2>
                        <p class="mt-2 text-sm text-[#44645f]">Controle rapide des textes publies.</p>
                    </div>
                    <span class="rounded-full bg-[#f7fff8] px-4 py-2 text-sm text-[#44645f] shadow-sm">{{ $latestDocuments->count() }} element(s)</span>
                </div>

                <div class="mt-6 grid gap-4">
                    @forelse ($latestDocuments as $document)
                        <article class="flex gap-4 rounded-[1.4rem] border border-[#dbeee5] bg-[#f7fff8] p-4">
                            <img src="{{ asset('storage/' . $document->cover_image_path) }}" alt="{{ $document->title }}" class="h-20 w-20 rounded-2xl object-cover">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-[#21453f]">{{ $document->title }}</p>
                                <p class="mt-1 text-sm text-[#44645f]">{{ $document->writer?->user?->name ?? 'Ecrivain' }}</p>
                                <form method="POST" action="{{ route('documents.destroy', $document) }}" class="mt-3">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-full border border-red-200 bg-red-50 px-4 py-2 text-xs font-semibold text-red-700">
                                        Supprimer
                                    </button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <p class="text-sm text-[#44645f]">Aucune oeuvre recente.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section id="options" class="mt-10 rounded-[1.8rem] border border-white/70 bg-white/85 p-6 shadow-[0_18px_50px_rgba(121,91,255,0.08)]">
            <h2 class="text-2xl font-semibold text-[#2b183d]">Options</h2>
            <p class="mt-2 text-sm text-[#6f5c75]">Resume des regles principales actuellement appliquees sur la plateforme.</p>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-[1.4rem] border border-orange-100 bg-[#fffaf4] p-5">
                    <p class="text-xs uppercase tracking-[0.24em] text-[#a03d5c]">Stockage</p>
                    <p class="mt-3 text-xl font-semibold text-[#2b183d]">{{ $platformOptions['storagePerAccount'] }}</p>
                    <p class="mt-2 text-sm text-[#6f5c75]">Par compte, quel que soit le type.</p>
                </article>
                <article class="rounded-[1.4rem] border border-orange-100 bg-[#fffaf4] p-5">
                    <p class="text-xs uppercase tracking-[0.24em] text-[#a03d5c]">Quota artiste</p>
                    <p class="mt-3 text-xl font-semibold text-[#2b183d]">{{ $platformOptions['artistLimit'] }}</p>
                    <p class="mt-2 text-sm text-[#6f5c75]">Limite actuelle par profil illustrateur.</p>
                </article>
                <article class="rounded-[1.4rem] border border-[#dbeee5] bg-[#f7fff8] p-5">
                    <p class="text-xs uppercase tracking-[0.24em] text-[#147d6d]">Formats ecrivain</p>
                    <p class="mt-3 text-xl font-semibold text-[#21453f]">{{ $platformOptions['writerFormats'] }}</p>
                    <p class="mt-2 text-sm text-[#44645f]">Formats acceptes pour les manuscrits.</p>
                </article>
                <article class="rounded-[1.4rem] border border-[#dbeee5] bg-[#f7fff8] p-5">
                    <p class="text-xs uppercase tracking-[0.24em] text-[#147d6d]">Formats image</p>
                    <p class="mt-3 text-xl font-semibold text-[#21453f]">{{ $platformOptions['illustrationFormats'] }}</p>
                    <p class="mt-2 text-sm text-[#44645f]">Formats acceptes pour les visuels.</p>
                </article>
            </div>
        </section>
    </section>
</x-app-layout>
