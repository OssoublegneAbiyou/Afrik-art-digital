<x-guest-layout title="Confirmer le mot de passe">
    <p class="pa-kicker">Zone protégée</p>
    <h1 class="pa-title">
        <span class="font-bebas">Confirmez</span>
        <span class="font-cormorant">votre mot de passe</span>
    </h1>
    <p class="pa-intro">Cette partie de votre espace est protégée. Merci de confirmer votre mot de passe pour continuer.</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-input-label for="password" :value="'Mot de passe'" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <button type="submit" class="pa-btn">Confirmer</button>
    </form>
</x-guest-layout>
