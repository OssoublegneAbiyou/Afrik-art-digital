<x-app-layout title="Mon compte">
    <section class="mx-auto max-w-4xl px-6 py-12">
        <div class="rounded-2xl border border-[#e8ddcf] bg-white p-8 shadow-sm">
            <span class="inline-flex rounded-full border border-[#d8c7b5] bg-[#fbf7ef] px-4 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-[#9a4f2c]">
                Profil
            </span>
            <h1 class="mt-4 text-3xl font-bold text-[#201a16]">Mon profil</h1>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-[#6a5a4d]">
                Modifiez votre nom, votre email ou votre mot de passe. Ces informations concernent votre compte de connexion.
            </p>
        </div>

        <div class="mt-8 grid gap-6">
            <div class="rounded-2xl border border-[#e8ddcf] bg-white p-6 shadow-sm">
                @include('profile.partials.update-profile-information-form')
            </div>

            @if ($user->hasGoogle() || ! $user->hasPassword() || \App\Support\GoogleAuth::enabled())
                <div class="rounded-2xl border border-[#e8ddcf] bg-white p-6 shadow-sm">
                    @include('profile.partials.google-connection')
                </div>
            @endif

            <div class="rounded-2xl border border-[#e8ddcf] bg-white p-6 shadow-sm">
                @include('profile.partials.update-password-form')
            </div>

            @if ($user->isVisitor())
                @if (setting('adhesion.ouverte', true))
                    <p class="rounded-2xl border border-dashed border-[#d8c7b5] bg-[#fbf7ef] p-5 text-sm leading-7 text-[#4f4238]">
                        Envie de publier tes dessins ou tes textes ?
                        <a href="{{ \App\Support\MemberAccounts::joinUrl($user) }}" class="inline-flex min-h-[44px] items-center font-semibold text-[#9a4f2c] underline underline-offset-4">Demander à rejoindre le collectif</a>
                    </p>
                @endif

                {{-- Les comptes créateurs (œuvres publiées) sont supprimés par l'équipe, sur demande. --}}
                <div class="rounded-2xl border border-[#e8ddcf] bg-white p-6 shadow-sm">
                    @include('profile.partials.delete-user-form')
                </div>
            @endif
        </div>
    </section>
</x-app-layout>
