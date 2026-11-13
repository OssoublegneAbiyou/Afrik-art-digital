<x-guest-layout title="Connexion" :description="'Connexion à l\'espace des membres du collectif '.$collectif['nom'].'.'">
    <p class="pa-kicker">Connexion</p>
    <h1 class="pa-title">
        <span class="font-bebas">Accès à votre espace</span>
        <span class="font-cormorant">bon retour parmi nous</span>
    </h1>
    <p class="pa-intro">Utilisez l'adresse e-mail et le mot de passe fournis par l'équipe.</p>

    <x-auth-session-status class="mt-4 text-center" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="'E-mail'" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="'Mot de passe'" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-x-4">
            <label for="remember_me" class="inline-flex min-h-[44px] cursor-pointer items-center gap-2">
                <input id="remember_me" type="checkbox" name="remember">
                <span>Se souvenir de moi</span>
            </label>

            @if (Route::has('password.request'))
                <a class="pa-small inline-flex min-h-[44px] items-center font-semibold underline underline-offset-4" href="{{ route('password.request') }}">Mot de passe oublié ?</a>
            @endif
        </div>

        <button type="submit" class="pa-btn">Se connecter</button>
    </form>

    <p class="pa-small mt-5 text-center">
        Les comptes sont créés par l'équipe.<br>
        <a href="{{ route('public.about') }}#rejoindre">Demander à rejoindre le collectif</a>
    </p>
</x-guest-layout>
