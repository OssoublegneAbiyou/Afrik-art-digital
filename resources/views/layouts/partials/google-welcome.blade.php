{{-- Bienvenue après la création d'un compte visiteur avec Google (affiché une fois, sur la page d'origine). --}}
@if (session(\App\Support\GoogleAuth::WELCOME_SESSION_KEY))
    <div class="ga-welcome" role="status" x-data="{ open: true }" x-show="open">
        <div class="w-container ga-welcome__inner">
            <p>{{ session(\App\Support\GoogleAuth::WELCOME_SESSION_KEY) }}</p>
            <button type="button" x-on:click="open = false" aria-label="Fermer le message de bienvenue"><span aria-hidden="true">×</span></button>
        </div>
    </div>
@endif
