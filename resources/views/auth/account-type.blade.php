<x-guest-layout>
    <form method="POST" action="{{ route('account-type.update') }}" class="space-y-5">
        @csrf
        @method('PATCH')

        <div>
            <p class="pa-kicker">Dernière étape</p>
            <h1 class="pa-title">
                <span class="font-bebas">Votre type de compte</span>
                <span class="font-cormorant">choisissez votre monde</span>
            </h1>
            <p class="pa-intro">Votre profil va s'adapter à votre usage du site.</p>
        </div>

        <div class="grid gap-3">
            <div>
                <input
                    id="account_type_artist"
                    type="radio"
                    name="account_type"
                    value="artist"
                    class="peer sr-only"
                    @checked(old('account_type') === 'artist')
                >
                <label for="account_type_artist" class="flex cursor-pointer items-start gap-4 rounded-2xl border border-orange-100 bg-gradient-to-br from-[#fff8ef] to-white p-4 shadow-sm transition hover:border-[#ffb703] peer-checked:border-[#ffb703] peer-checked:ring-2 peer-checked:ring-[#ffb703]/40">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#fff1d8] text-2xl">🎨</span>
                    <span class="block">
                        <span class="block text-base font-semibold text-[#2b183d]">Illustrateur</span>
                        <span class="mt-1 block text-sm text-gray-600">Publiez vos visuels, ajoutez une bannière et construisez votre galerie.</span>
                    </span>
                </label>
            </div>

            <div>
                <input
                    id="account_type_writer"
                    type="radio"
                    name="account_type"
                    value="writer"
                    class="peer sr-only"
                    @checked(old('account_type') === 'writer')
                >
                <label for="account_type_writer" class="flex cursor-pointer items-start gap-4 rounded-2xl border border-emerald-100 bg-gradient-to-br from-[#f2fff9] to-white p-4 shadow-sm transition hover:border-[#2dd4bf] peer-checked:border-[#2dd4bf] peer-checked:ring-2 peer-checked:ring-[#2dd4bf]/35">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#e9fff4] text-2xl">📚</span>
                    <span class="block">
                        <span class="block text-base font-semibold text-[#2b183d]">Écrivain</span>
                        <span class="mt-1 block text-sm text-gray-600">Publiez vos textes, vos couvertures et votre univers d'auteur.</span>
                    </span>
                </label>
            </div>

            <div>
                <input
                    id="account_type_visitor"
                    type="radio"
                    name="account_type"
                    value="visitor"
                    class="peer sr-only"
                    @checked(old('account_type', 'visitor') === 'visitor')
                >
                <label for="account_type_visitor" class="flex cursor-pointer items-start gap-4 rounded-2xl border border-[#e7dcff] bg-gradient-to-br from-[#f8f4ff] to-white p-4 shadow-sm transition hover:border-[#c084fc] peer-checked:border-[#c084fc] peer-checked:ring-2 peer-checked:ring-[#c084fc]/35">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f1e8ff] text-2xl">✨</span>
                    <span class="block">
                        <span class="block text-base font-semibold text-[#2b183d]">Visiteur</span>
                        <span class="mt-1 block text-sm text-gray-600">Suivez des artistes, gardez des favoris et explorez librement la plateforme.</span>
                    </span>
                </label>
            </div>
        </div>

        <x-input-error :messages="$errors->get('account_type')" class="mt-2" />

        <button type="submit" class="pa-btn">Continuer</button>
    </form>
</x-guest-layout>
