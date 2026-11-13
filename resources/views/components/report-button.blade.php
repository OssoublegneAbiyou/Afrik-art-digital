@props(['work'])

{{-- « Signaler » : visiteurs et membres peuvent alerter l'équipe (droits d'auteur, contenu, spam). --}}
@php
    $type = match (true) {
        $work instanceof \App\Models\Document => 'document',
        $work instanceof \App\Models\Comic => 'comic',
        $work instanceof \App\Models\ComicChapter => 'comic_chapter',
        default => 'illustration',
    };
    $errorsBag = $errors->getBag('report');
@endphp

@unless ($work->isOwnedBy(auth()->user()))
    <details {{ $attributes->class('report') }} @if ($errorsBag->any()) open @endif>
        <summary class="report__toggle">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 21V4m0 0h11l-1.5 4L15 12H4"/></svg>
            Signaler
        </summary>

        @if (session('report_sent'))
            <p class="report__done" role="status">Merci, l'équipe va examiner ce signalement.</p>
        @else
            <form method="POST" action="{{ route('reports.store', [$type, $work->id]) }}" class="report__form">
                @csrf
                @if ($errorsBag->any())
                    <ul class="report__errors" role="alert">
                        @foreach ($errorsBag->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
                <fieldset>
                    <legend>Pourquoi signaler cette œuvre ?</legend>
                    @foreach (\App\Models\Report::REASONS as $value => $label)
                        <label class="report__choice">
                            <input type="radio" name="reason" value="{{ $value }}" required @checked(old('reason') === $value)>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </fieldset>
                <label class="report__field" for="report-message-{{ $type }}-{{ $work->id }}">
                    Précisions <small>(facultatif, sauf pour « Autre raison »)</small>
                    <textarea id="report-message-{{ $type }}-{{ $work->id }}" name="message" rows="3" maxlength="1000">{{ old('message') }}</textarea>
                </label>
                @guest
                    <label class="report__field" for="report-email-{{ $type }}-{{ $work->id }}">
                        Ton e-mail <small>(facultatif, si l'équipe doit te répondre)</small>
                        <input id="report-email-{{ $type }}-{{ $work->id }}" type="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email">
                    </label>
                @endguest
                <div class="report__trap" aria-hidden="true">
                    <label>Ne pas remplir <input type="text" name="site_web" tabindex="-1" autocomplete="off"></label>
                </div>
                <button type="submit" class="report__submit">Envoyer le signalement</button>
            </form>
        @endif
    </details>
@endunless
