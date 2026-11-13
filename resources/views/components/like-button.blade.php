@props(['item'])

{{-- « J'aime » avec compteur réel. Sans JavaScript, le formulaire est envoyé normalement. --}}
@php
    $type = match (true) {
        $item instanceof \App\Models\Document => 'document',
        $item instanceof \App\Models\Comic => 'comic',
        $item instanceof \App\Models\Animation => 'animation',
        default => 'illustration',
    };
    $count = $item->likesTotal();
    $liked = $item->isLikedBy(auth()->user());
@endphp

<div {{ $attributes->class('like') }}>
    @auth
        <form method="POST" action="{{ route('likes.toggle', [$type, $item->id]) }}"
            x-data="{
                liked: @js($liked),
                count: {{ $count }},
                busy: false,
                async toggle(form) {
                    if (this.busy) return;
                    this.busy = true;
                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        });
                        if (! response.ok) throw new Error(response.status);
                        const data = await response.json();
                        this.liked = data.liked;
                        this.count = data.count;
                    } catch (e) {
                        form.submit();
                    } finally {
                        this.busy = false;
                    }
                },
            }"
            x-on:submit.prevent="toggle($el)">
            @csrf
            <button type="submit" class="like__btn" aria-pressed="{{ $liked ? 'true' : 'false' }}" x-bind:aria-pressed="liked.toString()" x-bind:disabled="busy">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 7.9 3.6 4.5 7 4.5c2 0 3.6 1.1 5 2.9 1.4-1.8 3-2.9 5-2.9 3.4 0 5.6 3.4 4.3 6.8-1.8 4.6-9.3 9.2-9.3 9.2Z"/></svg>
                <span x-text="liked ? 'Tu aimes' : 'J’aime'">{{ $liked ? 'Tu aimes' : 'J’aime' }}</span>
                <span class="like__count" x-text="count">{{ $count }}</span>
            </button>
        </form>
    @else
        <a href="{{ \App\Support\GoogleAuth::loginUrl() }}" class="like__btn" title="Connecte-toi pour aimer cette œuvre">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 7.9 3.6 4.5 7 4.5c2 0 3.6 1.1 5 2.9 1.4-1.8 3-2.9 5-2.9 3.4 0 5.6 3.4 4.3 6.8-1.8 4.6-9.3 9.2-9.3 9.2Z"/></svg>
            <span>J’aime</span>
            <span class="like__count">{{ $count }}</span>
            <span class="sr-only">(connexion requise)</span>
        </a>
    @endauth
</div>
