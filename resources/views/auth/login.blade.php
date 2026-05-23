<x-guest-layout>
    <div>
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <div class="text-center">
            <p class="text-sm uppercase tracking-[0.25em] text-[#ef476f]">Connexion</p>
            <h1 class="mt-2 text-2xl font-semibold text-[#2b183d]">Acces a votre espace</h1>
            <p class="mt-2 text-sm text-gray-600">
                Utilisez l'adresse mail et le mot de passe fournis par l'administrateur.
            </p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
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

        <p class="mt-6 text-center text-sm text-gray-600">
            Les comptes sont crees uniquement par l'administrateur.
        </p>
    </div>
</x-guest-layout>
