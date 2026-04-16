<x-guest-layout>
    <div x-data="{ method: 'google' }">
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <div class="text-center">
            <p class="text-sm uppercase tracking-[0.25em] text-[#ef476f]">Connexion</p>
            <h1 class="mt-2 text-2xl font-semibold text-[#2b183d]">Choisissez votre mode d acces</h1>
            <p class="mt-2 text-sm text-gray-600">
                Continuez avec Google ou utilisez votre adresse mail.
            </p>
        </div>

        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            <button
                type="button"
                @click="method = 'google'"
                :class="method === 'google' ? 'border-[#c084fc] bg-[#faf7ff] ring-2 ring-[#c084fc]/30' : 'border-gray-200 bg-white'"
                class="rounded-2xl border px-4 py-4 text-left shadow-sm transition"
            >
                <span class="block text-base font-semibold text-[#2b183d]">Google</span>
                <span class="mt-1 block text-sm text-gray-600">Connexion rapide avec votre compte Google.</span>
            </button>

            <button
                type="button"
                @click="method = 'mail'"
                :class="method === 'mail' ? 'border-[#ffb703] bg-[#fff9ef] ring-2 ring-[#ffb703]/30' : 'border-gray-200 bg-white'"
                class="rounded-2xl border px-4 py-4 text-left shadow-sm transition"
            >
                <span class="block text-base font-semibold text-[#2b183d]">Mail</span>
                <span class="mt-1 block text-sm text-gray-600">Connexion classique avec email et mot de passe.</span>
            </button>
        </div>

        <div class="mt-6" x-show="method === 'google'" x-cloak>
            <a
                href="{{ route('auth.google.redirect') }}"
                class="inline-flex w-full items-center justify-center gap-3 rounded-full border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-[#2b183d] shadow-sm transition hover:border-[#c084fc] hover:bg-[#faf7ff]"
            >
                <span>Continuer avec Google</span>
            </a>
        </div>

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4" x-show="method === 'mail'" x-cloak>
            @csrf

            <div>
                <x-input-label for="email" :value="'Email'" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" :value="'Mot de passe'" />
                <x-text-input id="password" class="block mt-1 w-full"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between gap-4">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-[#ef476f] shadow-sm focus:ring-[#ef476f]/30" name="remember">
                    <span class="ms-2 text-sm text-gray-600">Se souvenir de moi</span>
                </label>

                @if (Route::has('password.request'))
                    <a class="text-sm text-gray-600 underline transition hover:text-[#ef476f]" href="{{ route('password.request') }}">
                        Mot de passe oublie ?
                    </a>
                @endif
            </div>

            <x-primary-button class="w-full !justify-center !rounded-full !border-0 !bg-gradient-to-r !from-[#ef476f] !via-[#ff7b54] !to-[#ffb703] !px-6 !py-3 !text-white !shadow-lg !shadow-orange-200/60">
                Se connecter
            </x-primary-button>
        </form>

        <div class="mt-6 text-center text-sm text-gray-600">
            <a href="{{ route('register') }}" class="font-semibold text-[#ef476f] transition hover:text-[#c63d61]">
                Creer un compte
            </a>
        </div>
    </div>
</x-guest-layout>
