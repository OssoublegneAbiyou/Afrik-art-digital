<x-guest-layout title="Vérification de l'adresse e-mail">
    <p class="pa-kicker">Dernière vérification</p>
    <h1 class="pa-title">
        <span class="font-bebas">Vérifiez</span>
        <span class="font-cormorant">votre adresse e-mail</span>
    </h1>
    <p class="pa-intro">Avant de commencer, cliquez sur le lien que nous venons de vous envoyer par e-mail. Vous ne l'avez pas reçu ? Nous pouvons vous en envoyer un autre.</p>

    @if (session('status') == 'verification-link-sent')
        <p class="mt-4 text-center text-sm font-semibold text-green-600">Un nouveau lien de vérification vient d'être envoyé à votre adresse e-mail.</p>
    @endif

    <div class="mt-6 space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="pa-btn">Renvoyer l'e-mail de vérification</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="pa-btn pa-btn--ghost">Se déconnecter</button>
        </form>
    </div>
</x-guest-layout>
