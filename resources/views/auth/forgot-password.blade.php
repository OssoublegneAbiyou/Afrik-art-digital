<x-guest-layout title="Mot de passe oublié">
    <p class="pa-kicker">Mot de passe oublié</p>
    <h1 class="pa-title">
        <span class="font-bebas">Retrouver la clé</span>
        <span class="font-cormorant">de votre atelier</span>
    </h1>
    <p class="pa-intro">Indiquez votre adresse e-mail : nous vous enverrons un lien pour choisir un nouveau mot de passe.</p>

    <x-auth-session-status class="mt-4 text-center" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="'E-mail'" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <button type="submit" class="pa-btn">Envoyer le lien</button>
    </form>

    <p class="pa-small mt-4 text-center">
        <a href="{{ route('login') }}">← Retour à la connexion</a>
    </p>
</x-guest-layout>
