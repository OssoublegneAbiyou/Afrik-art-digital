<x-guest-layout>
    <form method="POST" action="{{ route('account-type.update') }}" class="space-y-5">
        @csrf
        @method('PATCH')

        <div class="text-center">
            <p class="text-sm uppercase tracking-[0.25em] text-[#ef476f]">Derniere etape</p>
            <h1 class="mt-2 text-2xl font-semibold text-[#2b183d]">Choisissez votre type de compte</h1>
            <p class="mt-2 text-sm text-gray-600">
                Votre profil va s adapter a votre usage du site.
            </p>
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
                        <span class="mt-1 block text-sm text-gray-600">Publiez vos visuels, ajoutez une banniere et construisez votre galerie.</span>
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
                        <span class="block text-base font-semibold text-[#2b183d]">Ecrivain</span>
                        <span class="mt-1 block text-sm text-gray-600">Publiez vos textes, vos couvertures et votre univers d auteur.</span>
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

        <x-primary-button class="w-full !justify-center !rounded-full !border-0 !bg-gradient-to-r !from-[#ef476f] !via-[#ff7b54] !to-[#ffb703] !px-6 !py-3 !text-white !shadow-lg !shadow-orange-200/60">
            Continuer
        </x-primary-button>
    </form>
</x-guest-layout>
