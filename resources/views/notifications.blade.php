@php
    use App\Notifications\MemberActivity;

    $icons = [
        MemberActivity::NEW_FOLLOWER => '＋',
        MemberActivity::FAVORITED => '★',
        MemberActivity::FEATURED => '☀',
        MemberActivity::TO_REVIEW => '✓',
        MemberActivity::REPORTED => '⚑',
        MemberActivity::APPROVED => '✓',
        MemberActivity::REJECTED => '✕',
    ];
@endphp

<x-world-layout world="pont" title="Notifications" :description="'Tes notifications sur '.$collectif['nom'].'.'" robots-noindex>
    <div class="ph pc">
        <section class="w-container pc-hero" aria-labelledby="pn-title">
            <p class="ph-kicker">Mon espace</p>
            <h1 id="pn-title" class="ph-h2 mt-3">
                <span class="font-bebas">Notifications</span>
                <span class="font-cormorant ph-h2__italic">ce qui s'est passé</span>
            </h1>
            <p class="pc-lead">Nouveaux abonnés, œuvres mises en favori et passages en « du jour ».</p>

            @if (session('success'))
                <p class="pc-flash" role="status">{{ session('success') }}</p>
            @endif
        </section>

        <section class="w-container pc-section pb-20" aria-label="Liste des notifications">
            @if ($notifications->isEmpty())
                <p class="pc-empty">Aucune notification pour l'instant. Elles apparaîtront ici quand quelqu'un suivra ton studio ou mettra une de tes œuvres en favori.</p>
            @else
                <ul class="pn-list" role="list">
                    @foreach ($notifications as $notification)
                        @php($data = $notification->data)
                        @php($unread = in_array($notification->id, $unreadIds, true))
                        <li class="pn-item {{ $unread ? 'pn-item--unread' : '' }}">
                            @if (! empty($data['thumbnail']))
                                <img src="{{ $data['thumbnail'] }}" alt="" class="pn-item__thumb" loading="lazy">
                            @else
                                <span class="pn-item__icon" aria-hidden="true">{{ $icons[$data['kind'] ?? ''] ?? '•' }}</span>
                            @endif
                            <div class="pn-item__body">
                                <a href="{{ $data['url'] ?? '#' }}" class="pn-item__message">{{ $data['message'] ?? '' }}</a>
                                <p class="pn-item__date">
                                    @if ($unread)
                                        <span class="pn-item__new">Nouveau</span>
                                    @endif
                                    {{ $notification->created_at->diffInSeconds(now()) < 60 ? 'à l’instant' : $notification->created_at->locale('fr')->diffForHumans() }}
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{ $notifications->links('pagination.afrikart') }}

                <form method="POST" action="{{ route('notifications.clear') }}" class="mt-8" onsubmit="return confirm('Effacer toutes les notifications ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="pc-remove">Tout effacer</button>
                </form>
            @endif
        </section>
    </div>
</x-world-layout>
