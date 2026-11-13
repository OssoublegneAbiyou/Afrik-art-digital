{{-- Suppression du compte : mot de passe demandé ; pour un compte créé avec Google (sans mot de passe), saisie de l'e-mail. --}}
@php($deletionErrors = $errors->getBag('userDeletion'))
<section aria-labelledby="delete-account-title">
    <header>
        <h2 id="delete-account-title" class="text-lg font-semibold text-[#201a16]">Supprimer mon compte</h2>
        <p class="mt-1 text-sm text-[#6a5a4d]">
            Tes j’aime, favoris, abonnements et marque-pages seront effacés définitivement. Cette action ne peut pas être annulée.
        </p>
    </header>

    <details class="mt-5" @if ($deletionErrors->any()) open @endif>
        <summary class="inline-flex min-h-[44px] cursor-pointer items-center rounded-full border border-red-700 px-5 text-sm font-semibold text-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-700">
            Supprimer mon compte
        </summary>

        <form method="POST" action="{{ route('profile.destroy') }}" class="mt-4 space-y-4">
            @csrf
            @method('DELETE')

            @if ($user->hasPassword())
                <div>
                    <x-input-label for="delete_password" value="Ton mot de passe, pour confirmer" />
                    <x-text-input id="delete_password" name="password" type="password" class="mt-1 block w-full" autocomplete="current-password" required />
                    <x-input-error :messages="$deletionErrors->get('password')" class="mt-2" />
                </div>
            @else
                <div>
                    <x-input-label for="delete_confirm_email" :value="'Recopie ton adresse e-mail ('.$user->email.') pour confirmer'" />
                    <x-text-input id="delete_confirm_email" name="confirm_email" type="email" class="mt-1 block w-full" autocomplete="off" required />
                    <x-input-error :messages="$deletionErrors->get('confirm_email')" class="mt-2" />
                </div>
            @endif

            <button type="submit" class="min-h-[44px] rounded-full bg-red-700 px-5 py-2 text-sm font-semibold text-white transition hover:bg-red-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-700">
                Supprimer définitivement mon compte
            </button>
        </form>
    </details>
</section>
