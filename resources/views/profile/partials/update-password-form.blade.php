@php($hasPassword = $user->hasPassword())
<section>
    <header>
        <h2 class="text-lg font-semibold text-[#201a16]">
            {{ $hasPassword ? 'Mot de passe' : 'Choisir un mot de passe' }}
        </h2>

        <p class="mt-1 text-sm text-[#6a5a4d]">
            @if ($hasPassword)
                Changez votre mot de passe avec un nouveau mot de passe sécurisé.
            @else
                Ton compte a été créé avec Google. Choisis un mot de passe pour pouvoir aussi te connecter avec ton e-mail.
            @endif
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('put')

        @if ($hasPassword)
            <div>
                <x-input-label for="update_password_current_password" value="Mot de passe actuel" />
                <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
            </div>
        @endif

        <div>
            <x-input-label for="update_password_password" value="Nouveau mot de passe" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" value="Confirmer le nouveau mot de passe" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="rounded-full bg-[#201a16] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#9a4f2c]">
                {{ $hasPassword ? 'Changer le mot de passe' : 'Enregistrer le mot de passe' }}
            </button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-emerald-700"
                >Mot de passe mis à jour.</p>
            @endif
        </div>
    </form>

    @if (session('status') === 'password-set')
        <p class="mt-4 text-sm text-emerald-700" role="status">Mot de passe enregistré : tu peux maintenant te connecter aussi avec ton e-mail.</p>
    @endif
</section>
