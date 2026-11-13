@php
    use App\Support\Fr;
    use App\Support\RichText;
    use App\Support\Settings;

    // Réglages du collectif (admin › Réglages) : texte, photo, contacts, réseaux, adhésion.
    $contact = $collectif['contact'];
    $socials = $collectif['reseaux'];
    $aboutText = RichText::clean(Settings::text('presentation.a_propos'));
    $hasContact = $contact['email'] || $contact['telephone'] || $contact['whatsapp_url'] || $contact['adresse'] || count($socials);
    $joinIntro = setting('adhesion.intro');

    // Message de confirmation : première phrase mise en avant, la suite en dessous (comme avant les réglages).
    $joinSuccess = session('join_success');
    $confirmation = is_string($joinSuccess) && $joinSuccess !== '' ? $joinSuccess : (string) setting('adhesion.message_confirmation');
    [$confirmationLead, $confirmationRest] = array_pad(preg_split('/(?<=[.!?…])\s+/u', trim($confirmation), 2), 2, null);
@endphp

<x-world-layout world="pont" title="À propos" :description="'Qui sommes-nous ? Le collectif '.$collectif['nom'].' réunit des illustrateurs et des écrivains. Découvrez comment nous rejoindre ou nous contacter.'">
    <div class="ph">
        <div class="ph-halos w-deco" aria-hidden="true">
            <div class="pont-halo" style="width:520px;height:520px;top:-120px;right:-160px;background:rgba(255,46,136,.2)"></div>
            <div class="pont-halo" style="width:460px;height:460px;top:520px;left:-200px;background:rgba(46,242,255,.12)"></div>
            <div class="pont-halo" style="width:440px;height:440px;bottom:80px;right:-140px;background:rgba(201,184,255,.16)"></div>
        </div>

        <section class="w-container pab-hero" aria-labelledby="pab-title">
            <p class="ph-kicker">Le collectif</p>
            <h1 id="pab-title" class="ph-h2 mt-3">
                <span class="font-bebas">À propos</span>
                <span class="font-cormorant ph-h2__italic">{{ Fr::de($collectif['nom']) }}</span>
            </h1>
            @if ($aboutText !== '')
                <div class="pab-body pab-richtext">
                    {!! $aboutText !!}
                </div>
            @endif
            @if ($collectif['annee_creation'])
                <p class="pab-founded">Collectif fondé en {{ $collectif['annee_creation'] }}.</p>
            @endif
            @if ($collectif['photo_groupe_url'])
                <figure class="pab-photo">
                    <img src="{{ $collectif['photo_groupe_url'] }}" alt="Les membres du collectif {{ $collectif['nom'] }}" loading="lazy">
                </figure>
            @endif

            <div class="pab-cards">
                <article class="glass-card pab-card pab-card--rose">
                    <span class="pab-card__deco w-deco" aria-hidden="true">絵</span>
                    <h2>Illustrateurs <em>la rue des néons</em></h2>
                    <p>Des galeries personnelles, des portfolios immersifs avec ambiance sonore et des œuvres à suivre et à mettre en favoris.</p>
                    <a href="{{ route('public.artists') }}" class="pont-btn pont-btn--rose">Voir les illustrateurs</a>
                </article>
                <article class="glass-card pab-card pab-card--lavande">
                    <span class="pab-card__deco w-deco" aria-hidden="true">物語</span>
                    <h2>Écrivains <em>le ciel des mots</em></h2>
                    <p>Romans, nouvelles, contes et poèmes à lire en ligne, à marquer et à télécharger.</p>
                    <a href="{{ route('public.writers') }}" class="pont-btn pont-btn--ghost">Voir les écrivains</a>
                </article>
            </div>
        </section>

        <section id="rejoindre" class="w-container scroll-mt-24 {{ $hasContact ? '' : 'pb-20' }}" aria-labelledby="pab-join-title">
            <div class="glass-card pab-panel">
                <h2 id="pab-join-title">
                    <span class="font-bebas">Rejoindre le collectif</span>
                    <span class="font-cormorant ph-h2__italic">tu dessines ou tu écris ?</span>
                </h2>

                @if (! $collectif['adhesion_ouverte'])
                    <p class="pab-closed">{{ setting('adhesion.message_ferme') }}</p>
                @else
                    @if (filled($joinIntro))
                        <p>{{ $joinIntro }}</p>
                    @endif
                @endif

                @if (! $collectif['adhesion_ouverte'])
                    {{-- Adhésions fermées : pas de formulaire. --}}
                @elseif ($joinSuccess)
                    <div class="pab-success" role="status">
                        <p class="font-cormorant">{{ $confirmationLead }}</p>
                        @if (filled($confirmationRest))
                            <p>{{ $confirmationRest }}</p>
                        @endif
                    </div>
                @else
                    @php($joinErrors = $errors->getBag('join'))
                    @if ($joinErrors->any())
                        <div class="pab-errors" role="alert">
                            <p>Il manque quelque chose :</p>
                            <ul>
                                @foreach ($joinErrors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('join-requests.store') }}" class="pab-form" novalidate>
                        @csrf
                        <div class="pab-form__grid">
                            <div class="pab-field">
                                <label for="join-name">Nom ou nom d'artiste</label>
                                <input id="join-name" name="name" type="text" value="{{ old('name', is_string(request()->query('nom')) ? mb_substr(request()->query('nom'), 0, 120) : '') }}" maxlength="120" required autocomplete="name"
                                    @if ($joinErrors->has('name')) aria-invalid="true" @endif>
                            </div>
                            <div class="pab-field">
                                <label for="join-email">E-mail</label>
                                <input id="join-email" name="email" type="email" value="{{ old('email', is_string(request()->query('email')) ? mb_substr(request()->query('email'), 0, 255) : '') }}" maxlength="255" required autocomplete="email"
                                    @if ($joinErrors->has('email')) aria-invalid="true" @endif>
                            </div>
                        </div>

                        <fieldset class="pab-field">
                            <legend>Discipline</legend>
                            <div class="pab-choices">
                                @foreach (\App\Models\JoinRequest::DISCIPLINES as $value => $label)
                                    <label class="pab-choice">
                                        <input type="radio" name="discipline" value="{{ $value }}" @checked(old('discipline') === $value) required>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <div class="pab-field">
                            <label for="join-link">Lien vers ton travail</label>
                            <input id="join-link" name="portfolio_url" type="url" value="{{ old('portfolio_url') }}" maxlength="255" required
                                placeholder="https://instagram.com/ton-compte" inputmode="url"
                                @if ($joinErrors->has('portfolio_url')) aria-invalid="true" @endif>
                            <small>Instagram, Behance, blog, dossier Drive… tout lien qui montre ce que tu fais.</small>
                        </div>

                        <div class="pab-field">
                            <label for="join-message">Message <span class="pab-optional">facultatif</span></label>
                            <textarea id="join-message" name="message" rows="4" maxlength="2000" placeholder="Présente-toi en quelques lignes, tes projets, ton univers…">{{ old('message') }}</textarea>
                        </div>

                        {{-- Champ piège anti-robots (« site_web ») : invisible, hors tabulation et ignoré par les lecteurs d'écran. --}}
                        <div class="pab-trap" aria-hidden="true">
                            <label for="join-site">Ne pas remplir</label>
                            <input id="join-site" name="site_web" type="text" value="" tabindex="-1" autocomplete="off" aria-hidden="true">
                        </div>

                        <button type="submit" class="pont-btn pont-btn--rose">Envoyer ma demande</button>
                        <p class="pab-legal">Tes informations servent uniquement à traiter ta demande. <a href="{{ route('public.legal') }}">Mentions légales</a></p>
                    </form>
                @endif
            </div>
        </section>

        @if ($hasContact)
            <section id="contact" class="w-container scroll-mt-24 pb-20" aria-labelledby="pab-contact-title">
                <div class="glass-card pab-panel">
                    <h2 id="pab-contact-title">
                        <span class="font-bebas">Contact</span>
                        <span class="font-cormorant ph-h2__italic">une lanterne allumée</span>
                    </h2>
                    @if ($contact['email'] || $contact['telephone'] || $contact['whatsapp_url'] || count($socials))
                        <div class="pab-chips">
                            @if ($contact['email'])
                                <a href="mailto:{{ $contact['email'] }}" class="pab-chip">{{ $contact['email'] }}</a>
                            @endif
                            @if ($contact['telephone'])
                                <a href="tel:{{ preg_replace('/[^\d+]+/', '', $contact['telephone']) }}" class="pab-chip">{{ $contact['telephone'] }}</a>
                            @endif
                            @if ($contact['whatsapp_url'])
                                <a href="{{ $contact['whatsapp_url'] }}" target="_blank" rel="noopener" class="pab-chip">WhatsApp</a>
                            @endif
                            @foreach ($socials as $social)
                                <a href="{{ $social['url'] }}" target="_blank" rel="noopener" class="pab-chip">{{ $social['label'] }}</a>
                            @endforeach
                        </div>
                    @endif
                    @if ($contact['adresse'])
                        <address class="pab-address">{!! nl2br(e($contact['adresse'])) !!}</address>
                    @endif
                </div>
            </section>
        @endif
    </div>
</x-world-layout>
