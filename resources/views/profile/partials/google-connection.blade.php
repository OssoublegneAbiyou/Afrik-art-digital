{{-- « Connexion avec Google » : état de la liaison, relier / délier, définir un mot de passe. --}}
<section aria-labelledby="google-connection-title">
    <header>
        <h2 id="google-connection-title" class="text-lg font-semibold text-[#201a16]">Connexion avec Google</h2>
        <p class="mt-1 text-sm text-[#6a5a4d]">
            @if ($user->hasGoogle())
                Ton compte est relié à Google{{ $user->google_email ? ' ('.$user->google_email.')' : '' }} : tu peux te connecter en un clic avec « Continuer avec Google ».
            @else
                Ton compte n’est pas relié à Google.
            @endif
        </p>
    </header>

    @if (session('google_status'))
        <p class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800" role="status">{{ session('google_status') }}</p>
    @endif
    @if (session('google_error'))
        <p class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert">{{ session('google_error') }}</p>
    @endif

    <div class="mt-5 flex flex-wrap items-center gap-3">
        @if ($user->hasGoogle())
            @if ($user->hasPassword())
                <form method="POST" action="{{ route('google.unlink') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="min-h-[44px] rounded-full border border-[#201a16] px-5 py-2 text-sm font-semibold text-[#201a16] transition hover:bg-[#f6efe6] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#9a4f2c]">
                        Délier mon compte Google
                    </button>
                </form>
            @else
                <p class="text-sm text-[#6a5a4d]">Pour délier Google, définis d’abord un mot de passe : sans lui, tu ne pourrais plus te connecter.</p>
            @endif
        @elseif (\App\Support\GoogleAuth::enabled())
            <x-google-button :href="route('google.link')" label="Relier mon compte Google" class="gbtn--inline" />
        @endif
    </div>

    @unless ($user->hasPassword())
        <div class="mt-6 rounded-xl border border-[#e8ddcf] bg-[#fbf7ef] p-4">
            <p class="text-sm text-[#4f4238]">Ton compte n’a pas de mot de passe : tu te connectes avec Google. Pour pouvoir aussi te connecter avec ton e-mail, reçois un lien pour en définir un.</p>
            <form method="POST" action="{{ route('password.set-link') }}" class="mt-3">
                @csrf
                <button type="submit" class="min-h-[44px] rounded-full bg-[#201a16] px-5 py-2 text-sm font-semibold text-white transition hover:bg-[#9a4f2c] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#9a4f2c]">
                    Définir un mot de passe
                </button>
            </form>
            @if (session('status') === 'password-link-sent')
                <p class="mt-3 text-sm text-emerald-700" role="status">Lien envoyé à {{ $user->email }} : ouvre-le pour choisir ton mot de passe.</p>
            @elseif (session('status') === 'password-link-failed')
                <p class="mt-3 text-sm text-red-700" role="alert">Le lien n’a pas pu être envoyé. Réessaie dans quelques minutes.</p>
            @endif
        </div>
    @endunless
</section>
