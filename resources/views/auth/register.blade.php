<x-guest-layout>
    <div x-data="{ method: 'google' }">
        <div class="text-center">
            <p class="text-sm uppercase tracking-[0.25em] text-[#ef476f]">Rejoindre Afrik'art Digital</p>
            <h1 class="mt-2 text-2xl font-semibold text-[#2b183d]">Choisissez votre mode d inscription</h1>
            <p class="mt-2 text-sm text-gray-600">
                Continuez avec Google ou creez votre compte par mail.
            </p>
        </div>

        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            <button
                type="button"
                @click="method = 'google'"
                :class="method === 'google' ? 'border-[#c084fc] bg-[#faf7ff] ring-2 ring-[#c084fc]/30' : 'border-gray-200 bg-white'"
                class="rounded-2xl border px-4 py-4 text-left shadow-sm transition"
            >
                <span class="flex items-center gap-3 text-base font-semibold text-[#2b183d]">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#f3ecff] text-xl">G</span>
                    Google
                </span>
                <span class="mt-2 block text-sm text-gray-600">Inscription rapide avec votre compte Google.</span>
            </button>

            <button
                type="button"
                @click="method = 'mail'"
                :class="method === 'mail' ? 'border-[#ffb703] bg-[#fff9ef] ring-2 ring-[#ffb703]/30' : 'border-gray-200 bg-white'"
                class="rounded-2xl border px-4 py-4 text-left shadow-sm transition"
            >
                <span class="flex items-center gap-3 text-base font-semibold text-[#2b183d]">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#fff1d8] text-xl">✉</span>
                    Mail
                </span>
                <span class="mt-2 block text-sm text-gray-600">Inscription classique avec nom, email et mot de passe.</span>
            </button>
        </div>

        <div class="mt-6" x-show="method === 'google'" x-cloak>
            <a
                href="{{ route('auth.google.redirect') }}"
                class="inline-flex w-full items-center justify-center gap-3 rounded-full border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-[#2b183d] shadow-sm transition hover:border-[#c084fc] hover:bg-[#faf7ff]"
            >
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#f3ecff] text-sm font-bold text-[#7a57c8]">G</span>
                <span>Continuer avec Google</span>
            </a>
        </div>

        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-5" x-show="method === 'mail'" x-cloak>
            @csrf

            <div>
                <x-input-label for="name" :value="'Nom'" />
                <x-text-input id="name" class="mt-1 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="email" :value="'Email'" />
                <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" :value="'Mot de passe'" />
                <x-text-input id="password" class="mt-1 block w-full"
                              type="password"
                              name="password"
                              required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" :value="'Confirmation du mot de passe'" />
                <x-text-input id="password_confirmation" class="mt-1 block w-full"
                              type="password"
                              name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div class="rounded-2xl border border-orange-100 bg-[#fffaf4] px-4 py-4 text-sm text-[#6f5c75]">
                Vous choisirez ensuite votre type de compte : Illustrateur, Ecrivain ou Visiteur.
            </div>

            <div class="flex items-center justify-between gap-4 pt-2">
                <a class="text-sm text-gray-600 underline transition hover:text-[#ef476f]" href="{{ route('login') }}">
                    Deja inscrit ?
                </a>

                <x-primary-button class="ms-4 !rounded-full !border-0 !bg-gradient-to-r !from-[#ef476f] !via-[#ff7b54] !to-[#ffb703] !px-6 !py-3 !text-white !shadow-lg !shadow-orange-200/60">
                    S'inscrire
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
