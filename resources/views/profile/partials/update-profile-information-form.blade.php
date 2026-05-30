<section>
    <header>
        <h2 class="text-lg font-semibold text-[#201a16]">
            Informations du compte
        </h2>

        <p class="mt-1 text-sm text-[#6a5a4d]">
            Mettez à jour votre nom affiché et votre adresse email.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="Nom" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="mt-2 text-sm text-[#6a5a4d]">
                        Votre adresse email n'est pas encore vérifiée.

                        <button form="send-verification" class="rounded-md text-sm font-semibold text-[#9a4f2c] underline hover:text-[#7e3f23] focus:outline-none focus:ring-2 focus:ring-[#9a4f2c]/25">
                            Renvoyer l'email de vérification
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm font-medium text-emerald-700">
                            Un nouveau lien de vérification a été envoyé.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="rounded-full bg-[#201a16] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#9a4f2c]">
                Enregistrer
            </button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-emerald-700"
                >Profil mis à jour.</p>
            @endif
        </div>
    </form>
</section>
